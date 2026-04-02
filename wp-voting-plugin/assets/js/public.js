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
        } else if (type === 'rcv' || type === 'stv' || type === 'condorcet' || type === 'sequential_rcv') {
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

        // Get optional voter comment.
        var voterComment = $form.find('textarea[name="voter_comment"]').val() || '';

        $.ajax({
            url:  wpvp_public.ajax_url,
            type: 'POST',
            data: {
                action:            'wpvp_cast_ballot',
                nonce:             wpvp_public.nonce,
                vote_id:           voteId,
                ballot_data:       JSON.stringify(ballotData),
                voting_role:       votingRole,
                send_confirmation: sendConfirmation,
                voter_comment:     voterComment
            },
            dataType: 'json'
        })
        .done(function (response) {
            if (response.success) {
                // Consent→FPTP conversion: reload so the user sees the new ballot form.
                if (response.data.converted) {
                    $status.text(response.data.message).removeClass('error').addClass('success');
                    $btn.prop('disabled', true);
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                    return;
                }

                var allowRevote = response.data.allow_revote;
                var statusMsg = response.data.message;

                // Show success message
                $status.text(statusMsg).removeClass('error').addClass('success');

                // If revoting is allowed, keep form visible and update button
                if (allowRevote) {
                    $btn.text(wpvp_public.i18n.change_vote).removeClass('wpvp-btn--primary').addClass('wpvp-btn--secondary');
                    $btn.prop('disabled', false);

                    // Add info message about being able to change vote
                    if (!$form.find('.wpvp-revote-notice').length) {
                        $status.after(
                            '<p class="wpvp-revote-notice" style="margin-top: 8px; font-size: 13px; color: #646970;">' +
                            wpvp_public.i18n.revote_notice +
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
                wpvp_public.i18n.rank_aria.replace('%1$s', $(this).find('.wpvp-ballot__sortable-text').text()).replace('%2$d', rank)
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
     *  Title search / filter
     * ----------------------------------------------------------------*/

    function wpvpGetCheckedValues($dropdown) {
        var vals = [];
        $dropdown.find('input:checked').each(function () {
            vals.push($(this).val());
        });
        return vals;
    }

    function wpvpUpdateToggleLabel($dropdown) {
        var checked = wpvpGetCheckedValues($dropdown);
        var $toggle = $dropdown.find('.wpvp-dropdown__toggle');
        if (checked.length === 0) {
            $toggle.text(wpvp_public.i18n.all || 'All');
        } else {
            $toggle.text(checked.length + ' selected');
        }
    }

    function wpvpApplyFilters($list) {
        var query = $list.find('.wpvp-vote-search__input').val().toLowerCase();
        var $table = $list.find('.wpvp-vote-table');

        var selectedProposers = wpvpGetCheckedValues($list.find('[data-filter="proposed_by"]'));
        var selectedClassifications = wpvpGetCheckedValues($list.find('[data-filter="classification"]'));
        var selectedOutcomes = wpvpGetCheckedValues($list.find('[data-filter="outcome"]'));

        // Lowercase for comparison.
        var lcProposers = [];
        for (var p = 0; p < selectedProposers.length; p++) {
            lcProposers.push(selectedProposers[p].toLowerCase());
        }
        var lcClassifications = [];
        for (var c = 0; c < selectedClassifications.length; c++) {
            lcClassifications.push(selectedClassifications[c].toLowerCase());
        }

        $table.find('tbody tr').each(function () {
            var $row = $(this);

            // Search only matches title.
            var titleText = $row.find('.wpvp-vote-table__title').text().toLowerCase();
            var matchesSearch = !query || titleText.indexOf(query) !== -1;

            // Proposed By filter (exact match on data attribute).
            var matchesProposer = lcProposers.length === 0;
            if (!matchesProposer) {
                var rowProposer = ($row.data('proposed-by') || '').toLowerCase();
                matchesProposer = lcProposers.indexOf(rowProposer) !== -1;
            }

            var matchesClassification = lcClassifications.length === 0;
            if (!matchesClassification) {
                var rowType = $row.find('.wpvp-vote-table__type').text().toLowerCase();
                for (var i = 0; i < lcClassifications.length; i++) {
                    if (rowType.indexOf(lcClassifications[i]) !== -1) {
                        matchesClassification = true;
                        break;
                    }
                }
            }

            var matchesOutcome = selectedOutcomes.length === 0;
            if (!matchesOutcome) {
                var rowOutcome = $row.data('outcome') || '';
                matchesOutcome = selectedOutcomes.indexOf(rowOutcome) !== -1;
            }

            if (matchesSearch && matchesProposer && matchesClassification && matchesOutcome) {
                $row.removeClass('wpvp-search-hidden');
            } else {
                $row.addClass('wpvp-search-hidden');
            }
        });

        wpvpPageState($table).page = 1;
        wpvpApplyPagination($table);
    }

    // Toggle dropdown open/close.
    $(document).on('click', '.wpvp-dropdown__toggle', function (e) {
        e.stopPropagation();
        var $dropdown = $(this).closest('.wpvp-dropdown');
        var wasOpen = $dropdown.hasClass('wpvp-dropdown--open');
        $('.wpvp-dropdown').removeClass('wpvp-dropdown--open');
        if (!wasOpen) {
            $dropdown.addClass('wpvp-dropdown--open');
        }
    });

    // Checkbox change triggers filter.
    $(document).on('change', '.wpvp-dropdown__item input', function (e) {
        e.stopPropagation();
        var $dropdown = $(this).closest('.wpvp-dropdown');
        var $list = $(this).closest('.wpvp-vote-list');
        wpvpUpdateToggleLabel($dropdown);

        // Server-side mode: submit the form.
        if ($list.find('.wpvp-server-paged').length) {
            var $form = $('#wpvp-filter-form');
            if ($form.length) {
                $form.submit();
                return;
            }
        }

        wpvpApplyFilters($list);
        wpvpToggleClearButton($list);
    });

    // Close dropdowns on outside click.
    $(document).on('click', function () {
        $('.wpvp-dropdown').removeClass('wpvp-dropdown--open');
    });

    // Prevent clicks inside dropdown menu from closing it.
    $(document).on('click', '.wpvp-dropdown__menu', function (e) {
        e.stopPropagation();
    });

    $(document).on('input', '.wpvp-vote-search__input', function () {
        var $list = $(this).closest('.wpvp-vote-list');
        // Server-side: no live filtering, Enter submits via form attribute.
        if ($list.find('.wpvp-server-paged').length) {
            return;
        }
        wpvpApplyFilters($list);
        wpvpToggleClearButton($list);
    });

    // Clear all filters.
    $(document).on('click', '.wpvp-clear-filters', function () {
        var $list = $(this).closest('.wpvp-vote-list');

        // Server-side: use the Clear All link instead.
        if ($list.find('.wpvp-server-paged').length) {
            var $clearLink = $list.find('.wpvp-filter-clear');
            if ($clearLink.length) {
                window.location.href = $clearLink.attr('href');
                return;
            }
        }

        $list.find('.wpvp-vote-search__input').val('');
        $list.find('.wpvp-dropdown__item input:checked').prop('checked', false);
        $list.find('.wpvp-dropdown').each(function () {
            wpvpUpdateToggleLabel($(this));
        });
        wpvpApplyFilters($list);
        wpvpToggleClearButton($list);
    });

    function wpvpToggleClearButton($list) {
        var hasQuery = $list.find('.wpvp-vote-search__input').val().length > 0;
        var hasChecked = $list.find('.wpvp-dropdown__item input:checked').length > 0;
        $list.find('.wpvp-clear-filters').toggle(hasQuery || hasChecked);
    }

    /* ------------------------------------------------------------------
     *  Sortable table columns
     * ----------------------------------------------------------------*/

    function wpvpSortTable($table, col, asc) {
        var $tbody = $table.find('tbody');
        var rows   = $tbody.find('tr').get();

        rows.sort(function (a, b) {
            var $cellA = $(a).children('td').eq(col);
            var $cellB = $(b).children('td').eq(col);
            var valA   = $cellA.attr('data-sort-value');
            var valB   = $cellB.attr('data-sort-value');

            if (valA === undefined) { valA = $cellA.text().trim().toLowerCase(); }
            if (valB === undefined) { valB = $cellB.text().trim().toLowerCase(); }

            var numA = parseFloat(valA);
            var numB = parseFloat(valB);
            if (!isNaN(numA) && !isNaN(numB)) {
                return asc ? numA - numB : numB - numA;
            }

            if (valA < valB) { return asc ? -1 : 1; }
            if (valA > valB) { return asc ? 1 : -1; }
            return 0;
        });

        $.each(rows, function (i, row) { $tbody.append(row); });

        // Update header indicators.
        $table.find('.wpvp-sortable__col').removeClass('wpvp-sortable__col--asc wpvp-sortable__col--desc');
        $table.find('.wpvp-sortable__col[data-col="' + col + '"]')
            .addClass(asc ? 'wpvp-sortable__col--asc' : 'wpvp-sortable__col--desc');
    }

    $(document).on('click', '.wpvp-sortable__col', function () {
        var $th    = $(this);
        var col    = parseInt($th.data('col'), 10);
        var $table = $th.closest('table');
        var asc    = !$th.hasClass('wpvp-sortable__col--asc');

        wpvpSortTable($table, col, asc);

        // Go to page 1 of sorted results.
        wpvpPageState($table).page = 1;
        wpvpApplyPagination($table);
    });

    /* ------------------------------------------------------------------
     *  Pagination
     * ----------------------------------------------------------------*/

    var _wpvpPageState = {};

    function wpvpTableKey($table) {
        // Stable key: use the index of the .wpvp-vote-list container.
        return $table.closest('.wpvp-vote-list').index('.wpvp-vote-list');
    }

    function wpvpPageState($table) {
        var key = wpvpTableKey($table);
        if (!_wpvpPageState[key]) {
            _wpvpPageState[key] = { page: 1 };
        }
        return _wpvpPageState[key];
    }

    function wpvpApplyPagination($table) {
        var perPage     = parseInt($table.data('per-page'), 10) || 20;
        var state       = wpvpPageState($table);
        var $allRows    = $table.find('tbody tr');
        var $activeRows = $allRows.filter(':not(.wpvp-search-hidden)');
        var total       = $activeRows.length;
        var totalPages  = Math.max(1, Math.ceil(total / perPage));

        // Clamp page.
        if (state.page > totalPages) { state.page = totalPages; }
        if (state.page < 1)          { state.page = 1; }

        var start = (state.page - 1) * perPage;
        var end   = state.page * perPage;

        // Hide all, show the current page slice of active rows.
        $allRows.addClass('wpvp-page-hidden');
        $activeRows.slice(start, end).removeClass('wpvp-page-hidden');

        // Build pagination controls.
        var $list       = $table.closest('.wpvp-vote-list');
        var $pagination = $list.find('.wpvp-pagination');
        $pagination.empty();

        if (totalPages <= 1 && total <= perPage) { return; }

        var showing = total === 0
            ? '0'
            : (Math.min(start + 1, total)) + '–' + Math.min(end, total);
        var html = '<div class="wpvp-pagination__info">Showing ' + showing + ' of ' + total + '</div>';
        html += '<div class="wpvp-pagination__controls">';

        // Prev.
        html += '<button class="wpvp-pagination__btn" data-page="' + (state.page - 1) + '"' +
            (state.page <= 1 ? ' disabled' : '') + '>&lsaquo; Prev</button>';

        // Page numbers with ellipsis.
        var startP = Math.max(1, state.page - 2);
        var endP   = Math.min(totalPages, state.page + 2);

        if (startP > 1) {
            html += '<button class="wpvp-pagination__btn" data-page="1">1</button>';
            if (startP > 2) { html += '<span class="wpvp-pagination__ellipsis">&hellip;</span>'; }
        }
        for (var p = startP; p <= endP; p++) {
            var activeClass = (p === state.page) ? ' wpvp-pagination__btn--active' : '';
            html += '<button class="wpvp-pagination__btn' + activeClass + '" data-page="' + p + '">' + p + '</button>';
        }
        if (endP < totalPages) {
            if (endP < totalPages - 1) { html += '<span class="wpvp-pagination__ellipsis">&hellip;</span>'; }
            html += '<button class="wpvp-pagination__btn" data-page="' + totalPages + '">' + totalPages + '</button>';
        }

        // Next.
        html += '<button class="wpvp-pagination__btn" data-page="' + (state.page + 1) + '"' +
            (state.page >= totalPages ? ' disabled' : '') + '>Next &rsaquo;</button>';

        html += '</div>';
        $pagination.html(html);
    }

    // Page button clicks.
    $(document).on('click', '.wpvp-pagination__btn:not([disabled])', function () {
        var page   = parseInt($(this).data('page'), 10);
        var $table = $(this).closest('.wpvp-vote-list').find('.wpvp-vote-table');
        wpvpPageState($table).page = page;
        wpvpApplyPagination($table);
        // Scroll to top of list.
        var top = $table.closest('.wpvp-vote-list').offset().top - 60;
        $('html, body').animate({ scrollTop: top }, 200);
    });

    // Per-page selector.
    $(document).on('change', '.wpvp-per-page-select', function () {
        var perPage = parseInt($(this).val(), 10);
        var $table  = $(this).closest('.wpvp-vote-list').find('.wpvp-vote-table');
        $table.data('per-page', perPage);
        wpvpPageState($table).page = 1;
        wpvpApplyPagination($table);
    });

    // Initialize: apply default sort then paginate (JS mode only — skip server-paged tables).
    $(document).ready(function () {
        $('.wpvp-vote-table.wpvp-sortable').each(function () {
            var $table     = $(this);
            var defaultCol = parseInt($table.data('default-sort-col'), 10);
            var defaultDir = $table.data('default-sort-dir');
            if (!isNaN(defaultCol)) {
                wpvpSortTable($table, defaultCol, defaultDir !== 'desc');
            }
            wpvpApplyPagination($table);
            $table.addClass('wpvp-initialized');
        });
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
                    '<button class="wpvp-modal__close" aria-label="' + wpvp_public.i18n.close + '">&times;</button>' +
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

        $content.html('<div class="wpvp-modal__loading">' + wpvp_public.i18n.loading + '</div>');
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
                $content.html('<p>' + wpvp_public.i18n.content_not_loaded + '</p>');
            }
        }).fail(function () {
            $content.html('<p>' + wpvp_public.i18n.error_loading + '</p>');
        });
    }

    function closeLightbox() {
        $('#wpvp-modal').fadeOut(200);
        $('body').css('overflow', '');
    }

})(jQuery);
