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
        var isRevote = $ballot.find('.wpvp-notice--info').length > 0;
        var confirmMsg = isRevote ? wpvp_public.i18n.confirm_revote : wpvp_public.i18n.confirm_submit;
        if (!confirm(confirmMsg)) {
            return;
        }

        // Disable button and show submitting state.
        $btn.prop('disabled', true);
        $status.text(wpvp_public.i18n.submitting).removeClass('success error');

        $.ajax({
            url:  wpvp_public.ajax_url,
            type: 'POST',
            data: {
                action:      'wpvp_cast_ballot',
                nonce:       wpvp_public.nonce,
                vote_id:     voteId,
                ballot_data: JSON.stringify(ballotData)
            },
            dataType: 'json'
        })
        .done(function (response) {
            if (response.success) {
                $status.text(response.data.message).removeClass('error').addClass('success');
                $btn.text(wpvp_public.i18n.success);

                // If this was a first vote, replace form with success message.
                if (!response.data.revoted) {
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

})(jQuery);
