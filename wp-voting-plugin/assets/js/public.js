/**
 * WP Voting Plugin — Public / Frontend JavaScript
 *
 * Handles: ballot form AJAX submission, drag-and-drop ranking for
 * RCV/STV/Condorcet, keyboard accessibility for sortable lists.
 *
 * Depends: jQuery.
 * Localized data available via `wpvp_public` global.
 */
(function ($) {
    'use strict';

    /* ------------------------------------------------------------------
     *  Ballot form submission (AJAX)
     * ----------------------------------------------------------------*/

    $(document).on('submit', '.wpvp-ballot__form', function (e) {
        e.preventDefault();

        var $form   = $(this);
        var $btn    = $form.find('.wpvp-ballot__submit-btn');
        var $status = $form.find('.wpvp-ballot__status');
        var $ballot = $form.closest('.wpvp-ballot');
        var type    = $ballot.data('vote-type');
        var voteId  = $ballot.data('vote-id');

        // Gather ballot data based on voting type.
        var ballotData;

        if (type === 'consent') {
            // Consent: hidden input always has value "Object".
            ballotData = $form.find('input[name="ballot_choice"]').val();
        } else if (type === 'singleton' || type === 'disciplinary') {
            ballotData = $form.find('input[name="ballot_choice"]:checked').val();
            if (!ballotData) {
                $status.text(wpvp_public.i18n.select_option).removeClass('success').addClass('error');
                return;
            }
        } else if (type === 'rcv' || type === 'stv' || type === 'condorcet') {
            // Collect ranked items in order.
            var ranked = [];
            $form.find('.wpvp-ballot__sortable-item').each(function () {
                ranked.push($(this).data('value'));
            });
            if (ranked.length === 0) {
                $status.text(wpvp_public.i18n.rank_required).removeClass('success').addClass('error');
                return;
            }
            ballotData = ranked;
        } else {
            ballotData = $form.find('input[name="ballot_choice"]:checked').val();
        }

        // Confirm submission.
        var allowRevote = $ballot.data('allow-revote') === 1;
        var isRevote = $ballot.find('.wpvp-notice--info').length > 0;
        var confirmMsg = (isRevote || allowRevote) ? wpvp_public.i18n.confirm_revote : wpvp_public.i18n.confirm_submit;
        if (!confirm(confirmMsg)) {
            return;
        }

        // Disable button and show submitting state.
        $btn.prop('disabled', true);
        $status.text(wpvp_public.i18n.submitting).removeClass('success error');

        // Get notification opt-in preference.
        var sendConfirmation = $form.find('input[name="send_confirmation"]').is(':checked') ? '1' : '0';

        // Get selected voting role (from dropdown or hidden input).
        var votingRole = $form.find('select[name="voting_role"]').val() || $form.find('input[name="voting_role"]').val() || '';

        $.ajax({
            url:  wpvp_public.ajax_url,
            type: 'POST',
            data: {
                action:            'wpvp_cast_ballot',
                nonce:             wpvp_public.nonce,
                vote_id:           voteId,
                ballot_data:       JSON.stringify(ballotData),
                voting_role:       votingRole,
                send_confirmation: sendConfirmation
            },
            dataType: 'json'
        })
        .done(function (response) {
            if (response.success) {
                var allowRevote = response.data.allow_revote;
                var statusMsg = response.data.message;

                // Show success message
                $status.text(statusMsg).removeClass('error').addClass('success');

                // If revoting is allowed, keep form visible and update button
                if (allowRevote) {
                    $btn.text('Change Vote').removeClass('wpvp-btn--primary').addClass('wpvp-btn--secondary');
                    $btn.prop('disabled', false);

                    // Add info message about being able to change vote
                    if (!$form.find('.wpvp-revote-notice').length) {
                        $status.after(
                            '<p class="wpvp-revote-notice" style="margin-top: 8px; font-size: 13px; color: #646970;">' +
                            'You can change your vote at any time before voting closes.' +
                            '</p>'
                        );
                    }
                } else {
                    // No revoting allowed, hide the form
                    $btn.text(wpvp_public.i18n.success);
                    $form.find('fieldset, .wpvp-ballot__ranked').fadeOut(300);
                    setTimeout(function () {
                        $btn.hide();
                    }, 300);
                }
            } else {
                $status.text(response.data.message || wpvp_public.i18n.error).removeClass('success').addClass('error');
                $btn.prop('disabled', false);
            }
        })
        .fail(function () {
            $status.text(wpvp_public.i18n.error).removeClass('success').addClass('error');
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     *  Drag-and-drop ranking (HTML5 Drag API)
     * ----------------------------------------------------------------*/

    var dragItem = null;

    $(document).on('dragstart', '.wpvp-ballot__sortable-item', function (e) {
        dragItem = this;
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        // Required for Firefox.
        e.originalEvent.dataTransfer.setData('text/plain', '');
    });

    $(document).on('dragend', '.wpvp-ballot__sortable-item', function () {
        $(this).removeClass('dragging');
        dragItem = null;
        // Clean up all drag-over classes.
        $('.wpvp-ballot__sortable-item').removeClass('drag-over');
    });

    $(document).on('dragover', '.wpvp-ballot__sortable-item', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';

        if (this !== dragItem) {
            $(this).addClass('drag-over');
        }
    });

    $(document).on('dragleave', '.wpvp-ballot__sortable-item', function () {
        $(this).removeClass('drag-over');
    });

    $(document).on('drop', '.wpvp-ballot__sortable-item', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');

        if (dragItem && this !== dragItem) {
            var $list = $(this).parent();
            var $dragItem = $(dragItem);
            var $dropTarget = $(this);

            // Determine if we should insert before or after.
            var items = $list.children().toArray();
            var dragIndex = items.indexOf(dragItem);
            var dropIndex = items.indexOf(this);

            if (dragIndex < dropIndex) {
                $dropTarget.after($dragItem);
            } else {
                $dropTarget.before($dragItem);
            }

            updateRankNumbers($list);
        }
    });

    /**
     * Update rank numbers after reorder.
     */
    function updateRankNumbers($list) {
        $list.children('.wpvp-ballot__sortable-item').each(function (i) {
            var rank = i + 1;
            $(this).find('.wpvp-ballot__rank-number').text(rank);
            $(this).attr('aria-label',
                $(this).find('.wpvp-ballot__sortable-text').text() + ' — Rank ' + rank
            );
        });
    }

    /* ------------------------------------------------------------------
     *  Keyboard-accessible ranking (up/down buttons)
     * ----------------------------------------------------------------*/

    $(document).on('click', '.wpvp-ballot__rank-up', function () {
        var $item = $(this).closest('.wpvp-ballot__sortable-item');
        var $prev = $item.prev('.wpvp-ballot__sortable-item');
        if ($prev.length) {
            $prev.before($item);
            updateRankNumbers($item.parent());
            $item.find('.wpvp-ballot__rank-up').focus();
        }
    });

    $(document).on('click', '.wpvp-ballot__rank-down', function () {
        var $item = $(this).closest('.wpvp-ballot__sortable-item');
        var $next = $item.next('.wpvp-ballot__sortable-item');
        if ($next.length) {
            $next.after($item);
            updateRankNumbers($item.parent());
            $item.find('.wpvp-ballot__rank-down').focus();
        }
    });

    // Keyboard: Arrow keys on sortable items.
    $(document).on('keydown', '.wpvp-ballot__sortable-item', function (e) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            var $item = $(this);

            if (e.key === 'ArrowUp') {
                var $prev = $item.prev('.wpvp-ballot__sortable-item');
                if ($prev.length) {
                    $prev.before($item);
                    updateRankNumbers($item.parent());
                    $item.focus();
                }
            } else {
                var $next = $item.next('.wpvp-ballot__sortable-item');
                if ($next.length) {
                    $next.after($item);
                    updateRankNumbers($item.parent());
                    $item.focus();
                }
            }
        }
    });

    /* ------------------------------------------------------------------
     *  Radio option highlighting
     * ----------------------------------------------------------------*/

    $(document).on('change', '.wpvp-ballot__radio', function () {
        var $fieldset = $(this).closest('.wpvp-ballot__fieldset');
        $fieldset.find('.wpvp-ballot__option').removeClass('wpvp-ballot__option--selected');
        $(this).closest('.wpvp-ballot__option').addClass('wpvp-ballot__option--selected');
    });

    /* ------------------------------------------------------------------
     *  Vote lightbox / modal
     * ----------------------------------------------------------------*/

    // Create modal HTML on page load
    if ($('#wpvp-modal').length === 0) {
        $('body').append(
            '<div id="wpvp-modal" class="wpvp-modal" style="display:none;">' +
                '<div class="wpvp-modal__overlay"></div>' +
                '<div class="wpvp-modal__container">' +
                    '<button class="wpvp-modal__close" aria-label="Close">&times;</button>' +
                    '<div class="wpvp-modal__content"></div>' +
                '</div>' +
            '</div>'
        );
    }

    // Open lightbox when clicking vote card title or "View" buttons with data-lightbox attribute
    $(document).on('click', '[data-lightbox-url]', function (e) {
        e.preventDefault();
        var url = $(this).data('lightbox-url');
        openLightbox(url);
    });

    // Close lightbox
    $(document).on('click', '.wpvp-modal__close, .wpvp-modal__overlay', function () {
        closeLightbox();
    });

    // ESC key closes lightbox
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#wpvp-modal').is(':visible')) {
            closeLightbox();
        }
    });

    function openLightbox(url) {
        var $modal = $('#wpvp-modal');
        var $content = $modal.find('.wpvp-modal__content');

        $content.html('<div class="wpvp-modal__loading">Loading...</div>');
        $modal.fadeIn(200);
        $('body').css('overflow', 'hidden');

        // Load content via AJAX
        $.get(url, function (html) {
            // Extract just the content we need (vote details or results)
            var $temp = $('<div>').html(html);
            var content = $temp.find('.wpvp-ballot, .wpvp-results-wrap, .wpvp-vote-detail').first().html();

            if (content) {
                $content.html(content);
            } else {
                $content.html('<p>Content could not be loaded.</p>');
            }
        }).fail(function () {
            $content.html('<p>Error loading content. Please try again.</p>');
        });
    }

    function closeLightbox() {
        $('#wpvp-modal').fadeOut(200);
        $('body').css('overflow', '');
    }

})(jQuery);
