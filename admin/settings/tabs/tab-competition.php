<?php
/**
 * Competition Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$competition_urls = get_option('keycontentai_competition_urls', array());

// Ensure we have at least one empty field
if (empty($competition_urls)) {
    $competition_urls = array('');
}
?>

<div class="keycontentai-tab-panel">
    <?php settings_errors('keycontentai_competition_settings'); ?>
    
    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Add URLs of competitor websites or pages. This information can help the AI understand the competitive landscape and generate more relevant content.', 'keycontentai'); ?>
    </p>
    
    <form method="post" action="options.php" id="keycontentai-competition-form">
        <?php
        settings_fields('keycontentai_competition_settings');
        do_settings_sections('keycontentai_competition_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label>
                            <?php esc_html_e('Competitor URLs', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <div id="keycontentai-competition-urls-container">
                            <?php foreach ($competition_urls as $index => $url) : ?>
                                <div class="keycontentai-url-row" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                    <input 
                                        type="url" 
                                        name="keycontentai_competition_urls[]"
                                        value="<?php echo esc_attr($url); ?>" 
                                        class="regular-text keycontentai-competition-url"
                                        placeholder="<?php esc_attr_e('https://competitor-website.com', 'keycontentai'); ?>"
                                    />
                                    <button 
                                        type="button" 
                                        class="button keycontentai-remove-url" 
                                        style="color: #b32d2e;"
                                        <?php echo count($competition_urls) === 1 ? 'disabled' : ''; ?>
                                    >
                                        <?php esc_html_e('Remove', 'keycontentai'); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button 
                            type="button" 
                            id="keycontentai-add-url" 
                            class="button button-secondary"
                            style="margin-top: 10px;"
                        >
                            <?php esc_html_e('+ Add Another URL', 'keycontentai'); ?>
                        </button>
                        
                        <p class="description" style="margin-top: 15px;">
                            <?php esc_html_e('Enter full URLs including http:// or https://. You can add as many competitor URLs as needed.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
(function($) {
    'use strict';
    
    $(document).ready(function() {
        var container = $('#keycontentai-competition-urls-container');
        
        // Add new URL field
        $('#keycontentai-add-url').on('click', function() {
            var newRow = $('<div class="keycontentai-url-row" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;"></div>');
            newRow.html(
                '<input type="url" name="keycontentai_competition_urls[]" value="" class="regular-text keycontentai-competition-url" placeholder="<?php esc_attr_e('https://competitor-website.com', 'keycontentai'); ?>" />' +
                '<button type="button" class="button keycontentai-remove-url" style="color: #b32d2e;"><?php esc_html_e('Remove', 'keycontentai'); ?></button>'
            );
            container.append(newRow);
            updateRemoveButtons();
        });
        
        // Remove URL field
        container.on('click', '.keycontentai-remove-url', function() {
            $(this).closest('.keycontentai-url-row').remove();
            updateRemoveButtons();
        });
        
        // Update remove button states
        function updateRemoveButtons() {
            var rows = container.find('.keycontentai-url-row');
            var removeButtons = container.find('.keycontentai-remove-url');
            
            if (rows.length === 1) {
                removeButtons.prop('disabled', true);
            } else {
                removeButtons.prop('disabled', false);
            }
        }
        
        // Initial state
        updateRemoveButtons();
    });
})(jQuery);
</script>

<style>
    .keycontentai-url-row {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .keycontentai-remove-url:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
