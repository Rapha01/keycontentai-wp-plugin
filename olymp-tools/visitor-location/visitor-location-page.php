<?php
/**
 * Visitor Location — admin page
 *
 * Included from Olymp_Tool_Visitor_Location::render_page(), which provides:
 *   $db_exists   (bool)        is the database present on disk?
 *   $db_size     (int)         database size in bytes
 *   $db_updated  (int)         timestamp of last successful download (0 = never)
 *   $db_error    (string)      last download error, if any
 *   $downloading (bool)        a download is in flight / backing off
 *   $stale       (bool)        the database is due a refresh
 *   $refresh_days(int)         refresh interval in days
 *   $preview_ip  (string)      the IP used for the live preview
 *   $preview     (array|null)  resolved { city, region, country, country_code, postal } or null
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcodes = array(
    array( '[olymp_visitor_city default="deiner Nähe"]',      __( "Visitor's city, e.g. München", 'sparkplus' ) ),
    array( '[olymp_visitor_region default="deiner Region"]',  __( "Visitor's region / state, e.g. Bayern", 'sparkplus' ) ),
    array( '[olymp_visitor_country default="Deutschland"]',   __( "Visitor's country, e.g. Deutschland", 'sparkplus' ) ),
    array( '[olymp_visitor_country_code default="DE"]',       __( 'ISO country code, e.g. DE', 'sparkplus' ) ),
    array( '[olymp_visitor_location field="city,country" separator=", " default="deiner Nähe"]', __( 'Several fields joined, empty parts dropped', 'sparkplus' ) ),
);
?>
<div class="wrap olymp-tools-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Output the current visitor\'s location anywhere with a shortcode — ideal for location-personalised copy like "Best offers in [olymp_visitor_city]". Lookups are performed locally against a DB-IP database; the visitor\'s IP never leaves your server.', 'sparkplus' ); ?>
    </p>

    <h2><?php esc_html_e( 'Database status', 'sparkplus' ); ?></h2>
    <table class="widefat striped" style="max-width: 640px;">
        <tbody>
            <tr>
                <th scope="row" style="width: 180px;"><?php esc_html_e( 'Database', 'sparkplus' ); ?></th>
                <td>
                    <?php if ( $db_exists ) : ?>
                        <span style="color:#007017;">&#10003; <?php esc_html_e( 'Installed', 'sparkplus' ); ?></span>
                        <?php if ( $db_size > 0 ) : ?>
                            &nbsp;&middot;&nbsp;<?php echo esc_html( size_format( $db_size ) ); ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <span style="color:#b32d2e;"><?php esc_html_e( 'Not downloaded yet', 'sparkplus' ); ?></span>
                        &nbsp;&mdash;&nbsp;<?php esc_html_e( 'it will download automatically on the first visitor, or click the button below.', 'sparkplus' ); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Last updated', 'sparkplus' ); ?></th>
                <td>
                    <?php
                    if ( $db_updated > 0 ) {
                        printf(
                            /* translators: %s: human-readable time difference, e.g. "3 days" */
                            esc_html__( '%s ago', 'sparkplus' ),
                            esc_html( human_time_diff( $db_updated, time() ) )
                        );
                        if ( $stale ) {
                            echo ' &middot; <em>' . esc_html__( 'a refresh is due and will run in the background', 'sparkplus' ) . '</em>';
                        }
                    } else {
                        esc_html_e( 'Never', 'sparkplus' );
                    }
                    ?>
                </td>
            </tr>
            <?php if ( $downloading ) : ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'Refresh', 'sparkplus' ); ?></th>
                <td><em><?php esc_html_e( 'A download is currently running (or was just attempted).', 'sparkplus' ); ?></em></td>
            </tr>
            <?php endif; ?>
            <?php if ( '' !== $db_error ) : ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'Last error', 'sparkplus' ); ?></th>
                <td><code style="color:#b32d2e;"><?php echo esc_html( $db_error ); ?></code></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <form class="olymp-tools-form" data-tool="visitor-location" method="post" style="margin-top: 12px;">
        <p class="submit" style="margin: 0; padding: 0;">
            <button type="submit" class="button olymp-tools-save-button"><?php esc_html_e( 'Refresh database now', 'sparkplus' ); ?></button>
            <span class="olymp-tools-save-status"></span>
        </p>
        <p class="description">
            <?php
            printf(
                /* translators: %d: number of days */
                esc_html__( 'The database refreshes automatically about every %d days. Use this to force an immediate update.', 'sparkplus' ),
                (int) $refresh_days
            );
            ?>
        </p>
    </form>

    <h2><?php esc_html_e( 'Live preview', 'sparkplus' ); ?></h2>
    <?php if ( is_array( $preview ) ) : ?>
        <p class="olymp-tools-vloc-preview">
            <?php
            $parts = array_filter( array(
                $preview['city'],
                $preview['region'],
                $preview['country'] . ( '' !== $preview['country_code'] ? ' (' . $preview['country_code'] . ')' : '' ),
            ), function ( $v ) { return '' !== trim( $v ); } );
            ?>
            <?php if ( ! empty( $parts ) ) : ?>
                <strong><?php echo esc_html( implode( ' · ', $parts ) ); ?></strong>
            <?php else : ?>
                <em><?php esc_html_e( 'The database did not have an entry for this IP.', 'sparkplus' ); ?></em>
            <?php endif; ?>
            <br>
            <span class="description">
                <?php
                printf(
                    /* translators: %s: IP address */
                    esc_html__( 'Detected IP: %s', 'sparkplus' ),
                    '<code>' . esc_html( $preview_ip ) . '</code>'
                );
                ?>
                &nbsp;&middot;&nbsp;
                <?php esc_html_e( 'Append ?test_ip=8.8.8.8 to this page URL to preview another address.', 'sparkplus' ); ?>
            </span>
        </p>
    <?php else : ?>
        <p class="description">
            <?php if ( ! $db_exists ) : ?>
                <?php esc_html_e( 'The preview will appear here once the database has been downloaded.', 'sparkplus' ); ?>
            <?php else : ?>
                <?php
                printf(
                    /* translators: %s: IP address */
                    esc_html__( 'No public IP to preview (detected: %s). Append ?test_ip=8.8.8.8 to this page URL to preview a specific address.', 'sparkplus' ),
                    '<code>' . esc_html( $preview_ip ) . '</code>'
                );
                ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <h2><?php esc_html_e( 'Shortcodes', 'sparkplus' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Paste these into any post, page, or widget. Every shortcode accepts a default="…" fallback shown when the location can\'t be determined.', 'sparkplus' ); ?>
    </p>
    <table class="widefat striped olymp-tools-shortcodes" style="max-width: 760px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Shortcode', 'sparkplus' ); ?></th>
                <th><?php esc_html_e( 'Outputs', 'sparkplus' ); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $shortcodes as $sc ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $sc[0] ); ?></code></td>
                    <td><?php echo esc_html( $sc[1] ); ?></td>
                    <td>
                        <button type="button" class="button button-small olymp-tools-copy"
                                data-clipboard="<?php echo esc_attr( $sc[0] ); ?>">
                            <?php esc_html_e( 'Copy', 'sparkplus' ); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="description" style="margin-top: 16px;">
        <?php
        echo wp_kses(
            __( 'IP Geolocation by <a href="https://db-ip.com" target="_blank" rel="noopener">DB-IP</a> (CC BY 4.0). Reader: MaxMind DB Reader (Apache-2.0).', 'sparkplus' ),
            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
        );
        ?>
    </p>
</div>
