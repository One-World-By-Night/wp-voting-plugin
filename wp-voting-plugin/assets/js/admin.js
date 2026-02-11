/**
 * WP Voting Plugin — Admin JavaScript
 *
 * Handles: vote editor dynamic behavior (add/remove options, type switching,
 * disciplinary auto-populate, num-winners toggle), Select2 init, settings
 * connection test.
 *
 * Depends: jQuery, Select2 (registered in class-admin.php).
 * Localized data available via `wpvp` global.
 */
(function ($) {
    'use strict';

    /* ------------------------------------------------------------------
     *  Vote Editor
     * ----------------------------------------------------------------*/

    var $form    = $('#wpvp-vote-form');
    var $optList = $('#wpvp-options-list');
    var $typeSelect = $('#wpvp-voting-type');

    /**
     * Add a new option row.
     */
    function addOptionRow() {
        var index = $optList.children('.wpvp-option-row').length;
        var row = $(
            '<div class="wpvp-option-row" data-index="' + index + '">' +
                '<input type="text" name="voting_options[' + index + '][text]" ' +
                    'placeholder="Option text" class="regular-text" required> ' +
                '<input type="text" name="voting_options[' + index + '][description]" ' +
                    'placeholder="Description (optional)" class="regular-text"> ' +
                '<button type="button" class="button wpvp-remove-option">&times;</button>' +
            '</div>'
        );
        $optList.append(row);
        updateRemoveButtons();
        row.find('input:first').focus();
    }

    /**
     * Remove an option row.
     */
    function removeOptionRow(e) {
        $(e.target).closest('.wpvp-option-row').remove();
        reindexOptions();
        updateRemoveButtons();
    }

    /**
     * Re-index option rows after removal.
     */
    function reindexOptions() {
        $optList.children('.wpvp-option-row').each(function (i) {
            $(this)
                .attr('data-index', i)
                .find('input').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/voting_options\[\d+\]/, 'voting_options[' + i + ']'));
                    }
                });
        });
    }

    /**
     * Disable remove button if only 2 options remain (except disciplinary).
     */
    function updateRemoveButtons() {
        var count = $optList.children('.wpvp-option-row').length;
        var type = $typeSelect.val();
        $optList.find('.wpvp-remove-option').prop('disabled', count <= 2 && type !== 'disciplinary' && type !== 'consent');
    }

    /**
     * Handle voting type change.
     */
    function onTypeChange() {
        var type = $typeSelect.val();
        var typeData = wpvp.vote_types[type] || {};

        // Update description.
        $('#wpvp-type-description').text(typeData.description || '');

        // Show/hide num winners (STV only).
        if (type === 'stv') {
            $('#wpvp-num-winners').show();
        } else {
            $('#wpvp-num-winners').hide();
        }

        // Consent: hide options section (no custom options needed).
        if (type === 'consent') {
            $('#wpvp-options-box').hide();
        } else {
            $('#wpvp-options-box').show();
        }

        // Disciplinary: auto-populate punishment levels.
        if (type === 'disciplinary') {
            autoPopulateDisciplinary();
        }

        updateRemoveButtons();
    }

    /**
     * Auto-populate disciplinary options.
     */
    function autoPopulateDisciplinary() {
        var punishments = [
            { text: 'Permanent Ban', description: 'Permanent removal from the organization' },
            { text: 'Indefinite Ban / 3 Strikes', description: 'Indefinite ban equivalent to 3 strikes' },
            { text: 'Temporary Ban', description: 'Temporary ban for a defined period' },
            { text: '2 Strikes', description: 'Second formal strike' },
            { text: '1 Strike', description: 'First formal strike' },
            { text: 'Probation', description: 'Placed on probationary status' },
            { text: 'Censure', description: 'Formal censure on record' },
            { text: 'Condemnation', description: 'Formal condemnation statement' }
        ];

        // Only auto-populate if current options are empty or generic.
        var hasCustom = false;
        $optList.find('input[name$="[text]"]').each(function () {
            if ($(this).val().trim() !== '') {
                hasCustom = true;
                return false;
            }
        });

        if (hasCustom && !confirm('Replace current options with standard disciplinary levels?')) {
            return;
        }

        $optList.empty();
        $.each(punishments, function (i, p) {
            var row = $(
                '<div class="wpvp-option-row" data-index="' + i + '">' +
                    '<input type="text" name="voting_options[' + i + '][text]" ' +
                        'value="' + escAttr(p.text) + '" class="regular-text" required> ' +
                    '<input type="text" name="voting_options[' + i + '][description]" ' +
                        'value="' + escAttr(p.description) + '" class="regular-text"> ' +
                    '<button type="button" class="button wpvp-remove-option">&times;</button>' +
                '</div>'
            );
            $optList.append(row);
        });
        updateRemoveButtons();
    }

    /**
     * Escape a string for use in an HTML attribute.
     */
    function escAttr(str) {
        return $('<span>').text(str).html();
    }

    // Bind vote editor events.
    if ($form.length) {
        $('#wpvp-add-option').on('click', addOptionRow);
        $optList.on('click', '.wpvp-remove-option', removeOptionRow);
        $typeSelect.on('change', onTypeChange);

        // Trigger initial state.
        onTypeChange();

        // Visibility → show/hide roles section.
        $('#visibility').on('change', function () {
            if ($(this).val() === 'restricted') {
                $('#wpvp-roles-section').show();
            } else {
                $('#wpvp-roles-section').hide();
            }
        });

        // Voting eligibility → show/hide voting roles section.
        $('#voting_eligibility').on('change', function () {
            if ($(this).val() === 'restricted') {
                $('#wpvp-voting-roles-section').show();
            } else {
                $('#wpvp-voting-roles-section').hide();
            }
        });
    }

    /* ------------------------------------------------------------------
     *  Select2 Init
     * ----------------------------------------------------------------*/

    function initSelect2() {
        $('.wpvp-select2-roles').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: wpvp.i18n.select_roles || 'Select roles...',
            allowClear: true,
            width: '100%'
        });

        $('.wpvp-select2-voting-roles').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: wpvp.i18n.select_roles || 'Select roles...',
            allowClear: true,
            width: '100%'
        });
    }

    if ($.fn.select2) {
        initSelect2();
    }

    /* ------------------------------------------------------------------
     *  Role Template Loader
     * ----------------------------------------------------------------*/

    $(document).on('click', '.wpvp-apply-template', function () {
        var $container = $(this).closest('.wpvp-template-loader');
        var templateId = $container.find('.wpvp-template-select').val();
        var target     = $container.find('.wpvp-template-select').data('target');

        if (!templateId) {
            return;
        }

        var $select2;
        if (target === 'allowed_roles') {
            $select2 = $('.wpvp-select2-roles');
        } else {
            $select2 = $('.wpvp-select2-voting-roles');
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(wpvp.ajax_url, {
            action: 'wpvp_get_template_roles',
            nonce:  wpvp.nonce,
            template_id: templateId
        })
        .done(function (response) {
            if (response.success && response.data.roles) {
                $.each(response.data.roles, function (i, role) {
                    if (!$select2.find('option[value="' + role + '"]').length) {
                        $select2.append(new Option(role, role, true, true));
                    } else {
                        $select2.find('option[value="' + role + '"]').prop('selected', true);
                    }
                });
                $select2.trigger('change');
            }
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     *  Settings: Connection Test
     * ----------------------------------------------------------------*/

    $('#wpvp-test-connection').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wpvp-connection-result');

        $btn.prop('disabled', true);
        $result.text('Testing...').removeClass('success error');

        $.post(wpvp.ajax_url, {
            action: 'wpvp_test_connection',
            nonce:  wpvp.nonce
        })
        .done(function (response) {
            if (response.success) {
                $result
                    .text(response.data.message + ' (' + response.data.role_count + ' roles)')
                    .addClass('success');
            } else {
                $result.text(response.data || 'Connection failed.').addClass('error');
            }
        })
        .fail(function () {
            $result.text('Request failed.').addClass('error');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    // Show/hide AccessSchema remote fields based on mode.
    $('input[name="wpvp_accessschema_mode"]').on('change', function () {
        if ($(this).val() === 'remote') {
            $('.wpvp-asc-remote-fields').show();
        } else {
            $('.wpvp-asc-remote-fields').hide();
        }
    });

    /* ------------------------------------------------------------------
     *  Settings: Process Closed Votes
     * ----------------------------------------------------------------*/

    $('#wpvp-process-closed').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wpvp-process-result');

        if (!confirm('Process all closed votes now?')) {
            return;
        }

        $btn.prop('disabled', true);
        $result.text('Processing...').removeClass('success error');

        $.post(wpvp.ajax_url, {
            action: 'wpvp_process_closed_votes',
            nonce:  wpvp.nonce
        })
        .done(function (response) {
            if (response.success) {
                $result.text(response.data.message).addClass('success');
            } else {
                $result.text(response.data || 'Failed.').addClass('error');
            }
        })
        .fail(function () {
            $result.text('Request failed.').addClass('error');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     *  Settings: Migration
     * ----------------------------------------------------------------*/

    $('#wpvp-check-migration').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wpvp-migration-result');
        var $runBtn = $('#wpvp-run-migration');

        $btn.prop('disabled', true);
        $result.text('Checking...').removeClass('success error');
        $runBtn.hide();

        $.post(wpvp.ajax_url, {
            action: 'wpvp_check_migration',
            nonce:  wpvp.nonce
        })
        .done(function (response) {
            if (response.success) {
                $result.text(response.data.message).addClass('success');
                if (response.data.votes > 0) {
                    $runBtn.show();
                }
            } else {
                $result.text(response.data || 'No data found.').addClass('error');
            }
        })
        .fail(function () {
            $result.text('Request failed.').addClass('error');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#wpvp-run-migration').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wpvp-migration-result');
        var $log    = $('#wpvp-migration-log');

        if (!confirm('Run the migration now? This will import v1 data into v2 tables.')) {
            return;
        }

        $btn.prop('disabled', true);
        $result.text('Migrating...').removeClass('success error');
        $log.hide().empty();

        $.post(wpvp.ajax_url, {
            action: 'wpvp_run_migration',
            nonce:  wpvp.nonce
        })
        .done(function (response) {
            if (response.success) {
                $result.text(response.data.message).addClass('success');
                if (response.data.log && response.data.log.length) {
                    var html = '';
                    $.each(response.data.log, function (i, entry) {
                        html += '<div>' + $('<span>').text(entry).html() + '</div>';
                    });
                    $log.html(html).show();
                }
            } else {
                $result.text(response.data || 'Migration failed.').addClass('error');
            }
        })
        .fail(function () {
            $result.text('Request failed.').addClass('error');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     *  Guide: Wizard accordion
     * ----------------------------------------------------------------*/

    var $wizard = $('#wpvp-wizard');

    if ($wizard.length) {
        // Toggle step on header click.
        $wizard.on('click', '.wpvp-wizard-step__header', function () {
            var $step = $(this).closest('.wpvp-wizard-step');
            var isOpen = $step.hasClass('is-open');

            if (isOpen) {
                $step.removeClass('is-open');
                $(this).attr('aria-expanded', 'false');
            } else {
                // Close all, open clicked.
                $wizard.find('.wpvp-wizard-step').removeClass('is-open')
                    .find('.wpvp-wizard-step__header').attr('aria-expanded', 'false');
                $step.addClass('is-open');
                $(this).attr('aria-expanded', 'true');

                // Scroll into view.
                var offset = $step.offset().top - 60;
                if ($(window).scrollTop() > offset) {
                    $('html, body').animate({ scrollTop: offset }, 200);
                }
            }
        });

        // Next / Prev buttons.
        $wizard.on('click', '.wpvp-wizard-next', function () {
            var $current = $(this).closest('.wpvp-wizard-step');
            var $next = $current.next('.wpvp-wizard-step');
            if ($next.length) {
                $current.removeClass('is-open')
                    .find('.wpvp-wizard-step__header').attr('aria-expanded', 'false');
                $next.addClass('is-open')
                    .find('.wpvp-wizard-step__header').attr('aria-expanded', 'true');

                var offset = $next.offset().top - 60;
                $('html, body').animate({ scrollTop: offset }, 200);
            }
        });

        $wizard.on('click', '.wpvp-wizard-prev', function () {
            var $current = $(this).closest('.wpvp-wizard-step');
            var $prev = $current.prev('.wpvp-wizard-step');
            if ($prev.length) {
                $current.removeClass('is-open')
                    .find('.wpvp-wizard-step__header').attr('aria-expanded', 'false');
                $prev.addClass('is-open')
                    .find('.wpvp-wizard-step__header').attr('aria-expanded', 'true');

                var offset = $prev.offset().top - 60;
                $('html, body').animate({ scrollTop: offset }, 200);
            }
        });
    }

    /* ------------------------------------------------------------------
     *  Guide: Interactive Vote Builder
     * ----------------------------------------------------------------*/

    var $guideForm = $('#wpvp-guide-builder-form');

    if ($guideForm.length) {
        // Add option button.
        $('#wpvp_gb_add_option').on('click', function () {
            var index = $('#wpvp_gb_options_list .wpvp-gb-option-row').length;
            var row = $(
                '<p class="wpvp-gb-option-row">' +
                    '<input type="text" name="voting_options[' + index + '][text]" class="regular-text" ' +
                        'placeholder="Option ' + (index + 1) + '" required> ' +
                    '<input type="text" name="voting_options[' + index + '][description]" class="regular-text" ' +
                        'placeholder="Description (optional)">' +
                '</p>'
            );
            $('#wpvp_gb_options_list').append(row);
        });

        // Show/hide options and num-winners based on type.
        $('#wpvp_gb_type').on('change', function () {
            var type = $(this).val();

            if (type === 'consent') {
                $('#wpvp_gb_options_section').hide();
            } else {
                $('#wpvp_gb_options_section').show();
            }

            if (type === 'stv') {
                $('#wpvp_gb_num_winners').show();
            } else {
                $('#wpvp_gb_num_winners').hide();
            }
        }).trigger('change');

        // Show/hide roles field based on visibility.
        $('#wpvp_gb_visibility').on('change', function () {
            if ($(this).val() === 'restricted') {
                $('#wpvp_gb_roles_section').show();
            } else {
                $('#wpvp_gb_roles_section').hide();
            }
        }).trigger('change');

        // Show/hide voting roles field based on voting eligibility.
        $('#wpvp_gb_voting_eligibility').on('change', function () {
            if ($(this).val() === 'restricted') {
                $('#wpvp_gb_voting_roles_section').show();
            } else {
                $('#wpvp_gb_voting_roles_section').hide();
            }
        }).trigger('change');

        // Form submission.
        $guideForm.on('submit', function (e) {
            e.preventDefault();

            var $btn = $('#wpvp_gb_submit');
            var $spinner = $('#wpvp_gb_spinner');
            var $message = $('#wpvp_gb_message');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $message.hide().removeClass('success error');

            // Collect form data.
            var formData = {
                action: 'wpvp_guide_create_vote',
                nonce: $('#wpvp_guide_nonce').val(),
                proposal_name: $('#wpvp_gb_title').val(),
                proposal_description: $('#wpvp_gb_description').val(),
                voting_type: $('#wpvp_gb_type').val(),
                voting_stage: $('#wpvp_gb_status').val(),
                opening_date: $('#wpvp_gb_open').val(),
                closing_date: $('#wpvp_gb_close').val(),
                visibility: $('#wpvp_gb_visibility').val(),
                allowed_roles: $('#wpvp_gb_roles').val(),
                voting_eligibility: $('#wpvp_gb_voting_eligibility').val(),
                voting_roles: $('#wpvp_gb_voting_roles').val(),
                number_of_winners: $('#wpvp_gb_winners').val(),
                voting_options: [],
                settings: {
                    allow_revote: $('input[name="settings[allow_revote]"]').is(':checked') ? '1' : '',
                    show_results_before_closing: $('input[name="settings[show_results_before_closing]"]').is(':checked') ? '1' : '',
                    anonymous_voting: $('input[name="settings[anonymous_voting]"]').is(':checked') ? '1' : ''
                }
            };

            // Collect voting options.
            $('#wpvp_gb_options_list .wpvp-gb-option-row').each(function () {
                var text = $(this).find('input[name*="[text]"]').val();
                var desc = $(this).find('input[name*="[description]"]').val();
                if (text) {
                    formData.voting_options.push({
                        text: text,
                        description: desc || ''
                    });
                }
            });

            $.post(wpvp.ajax_url, formData)
                .done(function (response) {
                    if (response.success) {
                        $message
                            .text(response.data.message + ' Redirecting...')
                            .addClass('success')
                            .show();

                        // Redirect to edit page after 1 second.
                        setTimeout(function () {
                            window.location.href = response.data.edit_url;
                        }, 1000);
                    } else {
                        $message
                            .text(response.data || 'Failed to create vote.')
                            .addClass('error')
                            .show();
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                })
                .fail(function () {
                    $message
                        .text('Request failed. Please try again.')
                        .addClass('error')
                        .show();
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

})(jQuery);
