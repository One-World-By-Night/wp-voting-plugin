jQuery(document).ready(function($) {
    var optionIndex = $('.wpvp-option-row').length;
    
    // Function to initialize permission Select2 fields
    function initializePermissionSelect2() {
        // Initialize Select2 for voting permissions
        if ($('.wpvp-select2').length) {
            $('.wpvp-select2').select2({
                placeholder: wpvp_admin.select_voters_placeholder,
                allowClear: true
            });
        }
        
        // Select2 with tags for AccessSchema (allows adding new groups)
        if ($('.wpvp-select2-tags').length) {
            $('.wpvp-select2-tags').select2({
                placeholder: wpvp_admin.select_groups_placeholder,
                allowClear: true,
                tags: true,
                tokenSeparators: [',']
            });
        }
        
        // Select2 for users with AJAX search
        if ($('.wpvp-select2-users').length) {
            $('.wpvp-select2-users').select2({
                placeholder: wpvp_admin.select_users_placeholder,
                allowClear: true,
                ajax: {
                    url: wpvp_admin.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'wpvp_search_users',
                            search: params.term,
                            nonce: wpvp_admin.nonce
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.success ? data.data : []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        }
    }
    
    // Add option button handler
    $('#wpvp-add-option').on('click', function(e) {
        e.preventDefault();
        addOptionRow(optionIndex);
        optionIndex++;
    });
    
    // Remove option handler (delegated)
    $(document).on('click', '.wpvp-remove-option', function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled')) {
            $(this).closest('.wpvp-option-row').remove();
            updateOptionIndices();
        }
    });
    
    // Add option row function
    function addOptionRow(index, option) {
        option = option || {text: '', description: ''};
        var template = `
            <div class="wpvp-option-row" data-index="${index}">
                <div class="wpvp-option-fields">
                    <input type="text" name="voting_options[${index}][text]" 
                           placeholder="${wpvp_admin.option_text_placeholder}" 
                           value="${option.text}" class="regular-text" required>
                    <input type="text" name="voting_options[${index}][description]" 
                           placeholder="${wpvp_admin.option_desc_placeholder}" 
                           value="${option.description}" class="regular-text">
                    <button type="button" class="button wpvp-remove-option">
                        ${wpvp_admin.remove_text}
                    </button>
                </div>
            </div>
        `;
        $('#wpvp-options-container').append(template);
        
        // Update remove button states
        updateRemoveButtons();
    }
    
    // Update option indices after removal
    function updateOptionIndices() {
        $('.wpvp-option-row').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input[name*="voting_options"]').each(function() {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            });
        });
        optionIndex = $('.wpvp-option-row').length;
        updateRemoveButtons();
    }
    
    // Update remove button states (disable if < 2 options)
    function updateRemoveButtons() {
        var optionCount = $('.wpvp-option-row').length;
        $('.wpvp-remove-option').prop('disabled', optionCount <= 2);
    }
    
    // Voting type change handler
    $('#voting_type').on('change', function() {
        var votingType = $(this).val();
        
        // Show/hide number of winners
        if (votingType === 'stv') {
            $('#number-of-winners-row').show();
        } else {
            $('#number-of-winners-row').hide();
        }
        
        // Handle predefined options for disciplinary
        if (votingType === 'disciplinary') {
            if (confirm(wpvp_admin.replace_options_confirm)) {
                loadPredefinedOptions(votingType);
            }
        }
        
        // Update description
        updateVotingTypeDescription(votingType);
    });
    
    // Update voting type description
    function updateVotingTypeDescription(votingType) {
        $.post(ajaxurl, {
            action: 'wpvp_get_vote_type_description',
            voting_type: votingType,
            nonce: wpvp_admin.nonce
        }, function(response) {
            if (response.success && response.data) {
                $('#voting-type-description').text(response.data);
            }
        });
    }
    
    // Load predefined options
    function loadPredefinedOptions(votingType) {
        $.post(ajaxurl, {
            action: 'wpvp_get_predefined_options',
            voting_type: votingType,
            nonce: wpvp_admin.nonce
        }, function(response) {
            if (response.success && response.data) {
                $('#wpvp-options-container').empty();
                optionIndex = 0;
                $.each(response.data, function(index, option) {
                    addOptionRow(index, option);
                    optionIndex++;
                });
            }
        });
    }
    
    // Initialize on page load
    updateRemoveButtons();
    $('#voting_type').trigger('change');
    
    // Initialize Select2 after a delay to ensure DOM is ready
    setTimeout(function() {
        initializePermissionSelect2();
    }, 500);
});