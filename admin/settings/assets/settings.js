/**
 * KeyContentAI Settings Page JavaScript
 */

jQuery(document).ready(function($) {
    
    /**
     * Handle Image Dimension Preset Selection
     */
    $(document).on('change', '.keycontentai-dimension-select', function() {
        const $select = $(this);
        const fieldKey = $select.data('field-key');
        const selectedValue = $select.val();
        
        const $widthInput = $('.keycontentai-dimension-width[data-field-key="' + fieldKey + '"]');
        const $heightInput = $('.keycontentai-dimension-height[data-field-key="' + fieldKey + '"]');
        const $customDiv = $('.keycontentai-custom-dimensions[data-field-key="' + fieldKey + '"]');
        const $customWidth = $('.keycontentai-custom-width[data-field-key="' + fieldKey + '"]');
        const $customHeight = $('.keycontentai-custom-height[data-field-key="' + fieldKey + '"]');
        
        if (selectedValue === 'custom') {
            // Show custom dimension fields
            $customDiv.show();
            // Update hidden inputs from custom fields if they have values
            if ($customWidth.val()) {
                $widthInput.val($customWidth.val());
            }
            if ($customHeight.val()) {
                $heightInput.val($customHeight.val());
            }
        } else {
            // Hide custom dimension fields
            $customDiv.hide();
            // Parse and set preset dimensions
            const dimensions = selectedValue.split('x');
            if (dimensions.length === 2) {
                $widthInput.val(dimensions[0]);
                $heightInput.val(dimensions[1]);
            }
        }
    });
    
    /**
     * Handle Custom Dimension Input Changes
     */
    $(document).on('input', '.keycontentai-custom-width, .keycontentai-custom-height', function() {
        const $input = $(this);
        const fieldKey = $input.data('field-key');
        const isWidth = $input.hasClass('keycontentai-custom-width');
        
        const $hiddenInput = isWidth 
            ? $('.keycontentai-dimension-width[data-field-key="' + fieldKey + '"]')
            : $('.keycontentai-dimension-height[data-field-key="' + fieldKey + '"]');
        
        $hiddenInput.val($input.val());
    });
    
});
