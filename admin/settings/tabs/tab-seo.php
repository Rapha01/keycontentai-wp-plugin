<?php
/**
 * SEO Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

$seo_rankmath_enable = (bool) get_option('sparkplus_seo_rankmath_enable', false);
$seo_slug_enable     = (bool) get_option('sparkplus_seo_slug_enable', false);

?>

<div class="sparkplus-tab-panel">

    <form method="post" class="sparkplus-settings-form" data-tab="seo">

        <h2><?php esc_html_e('RankMath Integration', 'sparkplus'); ?></h2>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_seo_rankmath_enable">
                            <?php esc_html_e('Enable RankMath Fields', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <?php if (defined('RANK_MATH_VERSION')) : ?>
                            <label>
                                <input
                                    type="checkbox"
                                    id="sparkplus_seo_rankmath_enable"
                                    name="sparkplus_seo_rankmath_enable"
                                    value="1"
                                    <?php checked($seo_rankmath_enable, true); ?>
                                />
                                <?php esc_html_e('Add RankMath SEO Title and SEO Description fields to the CPT field list', 'sparkplus'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, the RankMath SEO Title (rank_math_title) and SEO Description (rank_math_description) fields will appear in the CPT field list and can be generated or cleared like any other field. After toggling, go to the CPT tab and save once to refresh the field list.', 'sparkplus'); ?>
                            </p>
                        <?php else : ?>
                            <p class="description">
                                <?php esc_html_e('RankMath SEO plugin is not active. Install and activate RankMath to use this feature.', 'sparkplus'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('URL Slug Generation', 'sparkplus'); ?></h2>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_seo_slug_enable">
                            <?php esc_html_e('Enable URL Slug Field', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                id="sparkplus_seo_slug_enable"
                                name="sparkplus_seo_slug_enable"
                                value="1"
                                <?php checked($seo_slug_enable, true); ?>
                            />
                            <?php esc_html_e('Add a URL Slug field to the CPT field list', 'sparkplus'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, a "URL Slug" field will appear in the CPT field list. The AI will generate a short, keyword-rich slug (e.g. "best-coffee-grinders-2025") and apply it as the post permalink. After toggling, go to the CPT tab and save once to refresh the field list.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary sparkplus-save-btn">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Save SEO Settings', 'sparkplus'); ?>
            </button>
            <span class="sparkplus-save-status"></span>
        </p>

    </form>

</div>

<?php
})(); // End IIFE
