/**
 * KeyContentAI Settings Page JavaScript
 */

jQuery(document).ready(function($) {
    
    /**
     * Fetch Available Models from OpenAI
     */
    $('#keycontentai-fetch-models-btn').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const $status = $('#keycontentai-fetch-models-status');
        const $textModelSelect = $('#keycontentai_text_model');
        const $imageModelSelect = $('#keycontentai_image_model');
        const apiKey = $('#keycontentai_openai_api_key').val();
        
        // Store current selections
        const currentTextModel = $textModelSelect.val();
        const currentImageModel = $imageModelSelect.val();
        
        // Validate API key
        if (!apiKey || apiKey.trim() === '') {
            $status.html('<span style="color: #d63638;">Please enter an API key first.</span>');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true);
        $status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span> Fetching models...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'keycontentai_fetch_models',
                nonce: keycontentaiSettings.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateModelDropdowns(
                        response.data.text_models,
                        response.data.image_models,
                        currentTextModel,
                        currentImageModel
                    );
                    
                    $status.html(
                        '<span style="color: #00a32a;">✓ Models updated successfully! Found ' + 
                        response.data.text_models.length + ' text models and ' + 
                        response.data.image_models.length + ' image models.</span>'
                    );
                    
                    // Clear success message after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 5000);
                    
                } else {
                    const errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Failed to fetch models.';
                    $status.html('<span style="color: #d63638;">✗ ' + errorMsg + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $status.html('<span style="color: #d63638;">✗ Error: ' + error + '</span>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    /**
     * Update model dropdowns with fetched models
     */
    function updateModelDropdowns(textModels, imageModels, currentTextModel, currentImageModel) {
        const $textSelect = $('#keycontentai_text_model');
        const $imageSelect = $('#keycontentai_image_model');
        
        // Update text models dropdown
        if (textModels && textModels.length > 0) {
            $textSelect.empty();
            
            textModels.forEach(function(model) {
                const isRecommended = model.id === 'gpt-4o-mini';
                const label = isRecommended ? model.id + ' (Recommended)' : model.id;
                const isSelected = model.id === currentTextModel;
                
                $textSelect.append(
                    $('<option></option>')
                        .val(model.id)
                        .text(label)
                        .prop('selected', isSelected)
                );
            });
            
            // If current selection is not in the new list, select the first one
            if ($textSelect.find('option[value="' + currentTextModel + '"]').length === 0) {
                $textSelect.val($textSelect.find('option:first').val());
            }
        }
        
        // Update image models dropdown
        if (imageModels && imageModels.length > 0) {
            $imageSelect.empty();
            
            imageModels.forEach(function(model) {
                const isRecommended = model.id === 'dall-e-3';
                const label = isRecommended ? model.id + ' (Recommended)' : model.id;
                const isSelected = model.id === currentImageModel;
                
                $imageSelect.append(
                    $('<option></option>')
                        .val(model.id)
                        .text(label)
                        .prop('selected', isSelected)
                );
            });
            
            // If current selection is not in the new list, select the first one
            if ($imageSelect.find('option[value="' + currentImageModel + '"]').length === 0) {
                $imageSelect.val($imageSelect.find('option:first').val());
            }
        }
    }
    
});
