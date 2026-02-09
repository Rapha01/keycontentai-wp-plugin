/**
 * SparkWP Settings Page JavaScript
 */

jQuery(document).ready(function($) {
    
    // ─── Post Type Switcher (CPT tab) ───
    $('#sparkwp_selected_post_type').on('change', function() {
        var selectedPostType = $(this).val();
        $('input[name="sparkwp_selected_post_type"]').val(selectedPostType);
        
        var baseUrl = window.location.href.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        params.set('cpt', selectedPostType);
        window.location.href = baseUrl + '?' + params.toString();
    });
    
    // ─── Dimension dropdown (CPT tab) ───
    $(document).on('change', '.sparkwp-dimension-select', function() {
        var fieldKey = $(this).data('field-key');
        var selectedValue = $(this).val();
        var customDimensionsDiv = $('.sparkwp-custom-dimensions[data-field-key="' + fieldKey + '"]');
        var widthInput = $('.sparkwp-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.sparkwp-dimension-height[data-field-key="' + fieldKey + '"]');
        
        if (selectedValue === 'custom') {
            customDimensionsDiv.slideDown();
        } else {
            customDimensionsDiv.slideUp();
            var dimensions = selectedValue.split('x');
            if (dimensions.length === 2) {
                widthInput.val(dimensions[0]);
                heightInput.val(dimensions[1]);
            }
        }
    });
    
    $(document).on('input', '.sparkwp-custom-width, .sparkwp-custom-height', function() {
        var fieldKey = $(this).data('field-key');
        var customWidth = $('.sparkwp-custom-width[data-field-key="' + fieldKey + '"]').val();
        var customHeight = $('.sparkwp-custom-height[data-field-key="' + fieldKey + '"]').val();
        $('.sparkwp-dimension-width[data-field-key="' + fieldKey + '"]').val(customWidth);
        $('.sparkwp-dimension-height[data-field-key="' + fieldKey + '"]').val(customHeight);
    });
    
    // ─── AJAX Settings Save ───
    $('.sparkwp-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('.sparkwp-save-button');
        var $statusMessage = $form.find('.sparkwp-save-status');
        var tab = $form.data('tab');
        
        // Button: Saving state
        var originalText = $submitButton.val();
        $submitButton.prop('disabled', true).val(sparkwpSettings.saving);
        $statusMessage.removeClass('success error').text('').stop(true, true).show();
        
        // Build form data
        var formData = $form.serializeArray();
        
        // Add AJAX-specific fields
        var ajaxData = {
            action: 'sparkwp_save_settings',
            nonce: sparkwpSettings.nonce,
            tab: tab
        };
        
        // Flatten form data into the ajax data object
        formData.forEach(function(item) {
            ajaxData[item.name] = item.value;
        });
        
        $.ajax({
            url: sparkwpSettings.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    $statusMessage.addClass('success').text('✓ ' + response.data.message);
                    $submitButton.val(sparkwpSettings.saved);
                } else {
                    $statusMessage.addClass('error').text('✗ ' + (response.data.message || sparkwpSettings.error));
                    $submitButton.prop('disabled', false).val(originalText);
                }
                
                // Reset after 2 seconds
                setTimeout(function() {
                    $submitButton.prop('disabled', false).val(originalText);
                    $statusMessage.fadeOut(400, function() {
                        $(this).removeClass('success error').text('').show();
                    });
                }, 2000);
            },
            error: function() {
                $statusMessage.addClass('error').text('✗ ' + sparkwpSettings.error);
                $submitButton.prop('disabled', false).val(originalText);
                
                setTimeout(function() {
                    $statusMessage.fadeOut(400, function() {
                        $(this).removeClass('success error').text('').show();
                    });
                }, 3000);
            }
        });
    });
    
    // ─── Reset Tab ───
    $('#sparkwp-reset-settings-trigger').on('click', function() {
        $('#sparkwp-reset-settings-modal').fadeIn(200);
    });
    
    $('#sparkwp-reset-meta-trigger').on('click', function() {
        $('#sparkwp-reset-meta-modal').fadeIn(200);
    });
    
    // Close modal on cancel or overlay click
    $('.sparkwp-modal-cancel, .sparkwp-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).closest('.sparkwp-modal-overlay').fadeOut(200);
        }
    });
    
    $('.sparkwp-modal').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Confirm reset
    $('.sparkwp-modal-confirm').on('click', function() {
        var $button = $(this);
        var target = $button.data('target');
        var $modal = $button.closest('.sparkwp-modal-overlay');
        var $cancelButton = $modal.find('.sparkwp-modal-cancel');
        var $status = $('#sparkwp-reset-' + target + '-status');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(sparkwpSettings.saving);
        $cancelButton.prop('disabled', true);
        $status.removeClass('success error').text('');
        
        $.ajax({
            url: sparkwpSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sparkwp_reset_settings',
                nonce: sparkwpSettings.nonce,
                target: target,
                post_type: $button.data('post-type') || ''
            },
            success: function(response) {
                $modal.fadeOut(200);
                if (response.success) {
                    $status.addClass('success').text('✓ ' + response.data.message);
                } else {
                    $status.addClass('error').text('✗ ' + (response.data.message || sparkwpSettings.error));
                }
                $button.prop('disabled', false).text(originalText);
                $cancelButton.prop('disabled', false);
            },
            error: function() {
                $modal.fadeOut(200);
                $status.addClass('error').text('✗ ' + sparkwpSettings.error);
                $button.prop('disabled', false).text(originalText);
                $cancelButton.prop('disabled', false);
            }
        });
    });
    
});
