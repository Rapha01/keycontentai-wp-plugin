<?php
/**
 * API Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

$supported_models    = sparkplus_get_supported_models();
$text_model_default  = $supported_models['text']['default'];
$image_model_default = $supported_models['image']['default'];

$openai_api_key    = get_option( 'sparkplus_openai_api_key',    '' );
$anthropic_api_key = get_option( 'sparkplus_anthropic_api_key', '' );
$gemini_api_key    = get_option( 'sparkplus_gemini_api_key',    '' );

$text_model  = get_option( 'sparkplus_text_model',  $text_model_default );
$image_model = get_option( 'sparkplus_image_model', $image_model_default );

$text_model_deprecated  = sparkplus_get_model_provider( $text_model,  'text'  ) === null;
$image_model_deprecated = sparkplus_get_model_provider( $image_model, 'image' ) === null;

$text_by_provider  = array_diff_key( $supported_models['text']['by_provider'],  array( 'anthropic' => true ) );
$image_by_provider = array_diff_key( $supported_models['image']['by_provider'], array( 'anthropic' => true ) );
?>

<div class="sparkplus-tab-panel">
    
    <form method="post" class="sparkplus-settings-form" data-tab="api-settings">
        
        <table class="form-table" role="presentation">
            <tbody>
                <?php if ( $text_model_deprecated || $image_model_deprecated ) : ?>
                <tr>
                    <td colspan="2" style="padding-left: 0;">
                        <div class="notice notice-warning inline" style="margin: 0;">
                            <p>
                                <strong><?php esc_html_e( 'Deprecated model detected', 'sparkplus' ); ?></strong><br>
                                <?php if ( $text_model_deprecated ) : ?>
                                    <?php printf(
                                        /* translators: %s: model name */
                                        esc_html__( 'The text model “%s” is no longer supported. Please select a current model below and save.', 'sparkplus' ),
                                        esc_html( $text_model )
                                    ); ?><br>
                                <?php endif; ?>
                                <?php if ( $image_model_deprecated ) : ?>
                                    <?php printf(
                                        /* translators: %s: model name */
                                        esc_html__( 'The image model “%s” is no longer supported. Please select a current model below and save.', 'sparkplus' ),
                                        esc_html( $image_model )
                                    ); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <!-- ── OpenAI ──────────────────────────────────────────── -->
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0.5em 0 0.25em;"><?php esc_html_e( 'OpenAI', 'sparkplus' ); ?></h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_openai_api_key">
                            <?php esc_html_e( 'API Key', 'sparkplus' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="sparkplus_openai_api_key"
                            name="sparkplus_openai_api_key"
                            value="<?php echo esc_attr( $openai_api_key ); ?>"
                            class="regular-text"
                            placeholder="sk-..."
                        />
                        <p class="description">
                            <?php esc_html_e( 'Required for OpenAI GPT text models and all image generation. Get a key from', 'sparkplus' ); ?>
                            <a href="https://platform.openai.com/api-keys" target="_blank"><?php esc_html_e( 'OpenAI Platform', 'sparkplus' ); ?></a>.
                        </p>
                    </td>
                </tr>

                <!-- ── Anthropic ───────────────────────────────────────── -->
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0.5em 0 0.25em;"><?php esc_html_e( 'Anthropic Claude', 'sparkplus' ); ?></h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_anthropic_api_key">
                            <?php esc_html_e( 'API Key', 'sparkplus' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="sparkplus_anthropic_api_key"
                            name="sparkplus_anthropic_api_key"
                            value="<?php echo esc_attr( $anthropic_api_key ); ?>"
                            class="regular-text"
                            placeholder="sk-ant-..."
                        />
                        <p class="description">
                            <?php esc_html_e( 'Required for Claude text models. Get a key from', 'sparkplus' ); ?>
                            <a href="https://console.anthropic.com/settings/keys" target="_blank"><?php esc_html_e( 'Anthropic Console', 'sparkplus' ); ?></a>.
                        </p>
                    </td>
                </tr>

                <!-- ── Google Gemini ───────────────────────────────────── -->
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0.5em 0 0.25em;"><?php esc_html_e( 'Google Gemini', 'sparkplus' ); ?></h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_gemini_api_key">
                            <?php esc_html_e( 'API Key', 'sparkplus' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="sparkplus_gemini_api_key"
                            name="sparkplus_gemini_api_key"
                            value="<?php echo esc_attr( $gemini_api_key ); ?>"
                            class="regular-text"
                            placeholder="AIza..."
                        />
                        <p class="description">
                            <?php esc_html_e( 'Required for Gemini text models. Get a key from', 'sparkplus' ); ?>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank"><?php esc_html_e( 'Google AI Studio', 'sparkplus' ); ?></a>.
                        </p>
                    </td>
                </tr>

                <!-- ── Model Selection ─────────────────────────────────── -->
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0.5em 0 0.25em;"><?php esc_html_e( 'Model Selection', 'sparkplus' ); ?></h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_text_model">
                            <?php esc_html_e( 'Text Generation Model', 'sparkplus' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="sparkplus_text_model" name="sparkplus_text_model" class="regular-text">
                            <?php if ( $text_model_deprecated ) : ?>
                                <option value="<?php echo esc_attr( $text_model ); ?>" selected="selected">
                                    <?php echo esc_html( $text_model ); ?> &mdash; <?php esc_html_e( 'deprecated', 'sparkplus' ); ?>
                                </option>
                            <?php endif; ?>
                            <?php foreach ( $text_by_provider as $provider_data ) : ?>
                                <optgroup label="<?php echo esc_attr( $provider_data['label'] ); ?>">
                                    <?php foreach ( $provider_data['models'] as $model_id => $model_label ) : ?>
                                        <option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $text_model, $model_id ); ?>>
                                            <?php echo esc_html( $model_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select the AI model for text generation. Make sure the corresponding API key above is configured.', 'sparkplus' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="sparkplus_image_model">
                            <?php esc_html_e( 'Image Generation Model', 'sparkplus' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="sparkplus_image_model" name="sparkplus_image_model" class="regular-text">
                            <?php if ( $image_model_deprecated ) : ?>
                                <option value="<?php echo esc_attr( $image_model ); ?>" selected="selected">
                                    <?php echo esc_html( $image_model ); ?> &mdash; <?php esc_html_e( 'deprecated', 'sparkplus' ); ?>
                                </option>
                            <?php endif; ?>
                            <?php foreach ( $image_by_provider as $provider_data ) : ?>
                                <optgroup label="<?php echo esc_attr( $provider_data['label'] ); ?>">
                                    <?php foreach ( $provider_data['models'] as $model_id => $model_label ) : ?>
                                        <option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $image_model, $model_id ); ?>>
                                            <?php echo esc_html( $model_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select the model for image generation. Only OpenAI image models are currently supported.', 'sparkplus' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary sparkplus-save-button" value="<?php esc_attr_e('Save Changes', 'sparkplus'); ?>" />
            <span class="sparkplus-save-status"></span>
        </p>
    </form>
</div>

<?php
})(); // End IIFE
