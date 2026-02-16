/**
 * SparkPlus Settings Page JavaScript
 */

jQuery(document).ready(function($) {
    
    // ─── Post Type Switcher (CPT tab) ───
    $('#sparkplus_selected_post_type').on('change', function() {
        var selectedPostType = $(this).val();
        $('input[name="sparkplus_selected_post_type"]').val(selectedPostType);
        
        var baseUrl = window.location.href.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        params.set('cpt', selectedPostType);
        window.location.href = baseUrl + '?' + params.toString();
    });
    
    // ─── Dimension dropdown (CPT tab) ───
    $(document).on('change', '.sparkplus-dimension-select', function() {
        var fieldKey = $(this).data('field-key');
        var selectedValue = $(this).val();
        var customDimensionsDiv = $('.sparkplus-custom-dimensions[data-field-key="' + fieldKey + '"]');
        var widthInput = $('.sparkplus-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.sparkplus-dimension-height[data-field-key="' + fieldKey + '"]');
        
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
    
    $(document).on('input', '.sparkplus-custom-width, .sparkplus-custom-height', function() {
        var fieldKey = $(this).data('field-key');
        var customWidth = $('.sparkplus-custom-width[data-field-key="' + fieldKey + '"]').val();
        var customHeight = $('.sparkplus-custom-height[data-field-key="' + fieldKey + '"]').val();
        $('.sparkplus-dimension-width[data-field-key="' + fieldKey + '"]').val(customWidth);
        $('.sparkplus-dimension-height[data-field-key="' + fieldKey + '"]').val(customHeight);
    });
    
    // ─── AJAX Settings Save ───
    $('.sparkplus-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('.sparkplus-save-button');
        var $statusMessage = $form.find('.sparkplus-save-status');
        var tab = $form.data('tab');
        
        // Button: Saving state
        var originalText = $submitButton.val();
        $submitButton.prop('disabled', true).val(sparkplusSettings.saving);
        $statusMessage.removeClass('success error').text('').stop(true, true).show();
        
        // Build form data
        var formData = $form.serializeArray();
        
        // Add AJAX-specific fields
        var ajaxData = {
            action: 'sparkplus_save_settings',
            nonce: sparkplusSettings.nonce,
            tab: tab
        };
        
        // Flatten form data into the ajax data object
        formData.forEach(function(item) {
            ajaxData[item.name] = item.value;
        });
        
        $.ajax({
            url: sparkplusSettings.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    $statusMessage.addClass('success').text('✓ ' + response.data.message);
                    $submitButton.val(sparkplusSettings.saved);
                } else {
                    $statusMessage.addClass('error').text('✗ ' + (response.data.message || sparkplusSettings.error));
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
                $statusMessage.addClass('error').text('✗ ' + sparkplusSettings.error);
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
    $('#sparkplus-reset-settings-trigger').on('click', function() {
        $('#sparkplus-reset-settings-modal').fadeIn(200);
    });
    
    $('#sparkplus-reset-meta-trigger').on('click', function() {
        $('#sparkplus-reset-meta-modal').fadeIn(200);
    });
    
    // Close modal on cancel or overlay click
    $('.sparkplus-modal-cancel, .sparkplus-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).closest('.sparkplus-modal-overlay').fadeOut(200);
        }
    });
    
    $('.sparkplus-modal').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Confirm reset
    $('.sparkplus-modal-confirm').on('click', function() {
        var $button = $(this);
        var target = $button.data('target');
        var $modal = $button.closest('.sparkplus-modal-overlay');
        var $cancelButton = $modal.find('.sparkplus-modal-cancel');
        var $status = $('#sparkplus-reset-' + target + '-status');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(sparkplusSettings.saving);
        $cancelButton.prop('disabled', true);
        $status.removeClass('success error').text('');
        
        $.ajax({
            url: sparkplusSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sparkplus_reset_settings',
                nonce: sparkplusSettings.nonce,
                target: target,
                post_type: $button.data('post-type') || ''
            },
            success: function(response) {
                $modal.fadeOut(200);
                if (response.success) {
                    $status.addClass('success').text('✓ ' + response.data.message);
                } else {
                    $status.addClass('error').text('✗ ' + (response.data.message || sparkplusSettings.error));
                }
                $button.prop('disabled', false).text(originalText);
                $cancelButton.prop('disabled', false);
            },
            error: function() {
                $modal.fadeOut(200);
                $status.addClass('error').text('✗ ' + sparkplusSettings.error);
                $button.prop('disabled', false).text(originalText);
                $cancelButton.prop('disabled', false);
            }
        });
    });
    
});
