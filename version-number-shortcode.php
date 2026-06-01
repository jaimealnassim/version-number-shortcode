<?php
/**
 * Plugin Name:       Version Number Shortcode
 * Plugin URI:        https://wordpress.org/plugins/version-number-shortcode/
 * Description:       Fetches a version number from a remote JSON endpoint and outputs it anywhere via the [version_number] shortcode. Configurable check frequency with a manual refresh button.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Nahnu Plugins
 * Author URI:        https://nahnuplugins.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       version-number-shortcode
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

define( 'VNS_VERSION',       '1.0.0' );
define( 'VNS_OPTION_URL',        'vns_release_url' );
define( 'VNS_OPTION_VERSION',    'vns_cached_version' );
define( 'VNS_OPTION_LAST_CHECK', 'vns_last_check' );
define( 'VNS_OPTION_INTERVAL',   'vns_cache_interval' );
define( 'VNS_TRANSIENT_KEY',     'vns_release_version' );

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Returns the allowed check-frequency options (hours => label).
 *
 * @return array<int,string>
 */
function vns_intervals(): array {
	return [
		1  => __( 'Every hour',     'version-number-shortcode' ),
		6  => __( 'Every 6 hours',  'version-number-shortcode' ),
		12 => __( 'Every 12 hours', 'version-number-shortcode' ),
		24 => __( 'Every 24 hours', 'version-number-shortcode' ),
	];
}

/**
 * Returns the current cache interval in hours, validated against allowed values.
 *
 * @return int
 */
function vns_cache_hours(): int {
	$val = (int) get_option( VNS_OPTION_INTERVAL, 6 );
	return array_key_exists( $val, vns_intervals() ) ? $val : 6;
}

// ---------------------------------------------------------------------------
// Core fetch logic
// ---------------------------------------------------------------------------

/**
 * Fetches (or returns cached) the remote version string.
 *
 * @param bool $force Skip cache and fetch immediately.
 * @return string Sanitised, escaped version string, or empty string on failure.
 */
function vns_get_version( bool $force = false ): string {
	if ( ! $force ) {
		$cached = get_transient( VNS_TRANSIENT_KEY );
		if ( false !== $cached ) {
			return esc_html( $cached );
		}
	}

	$url = get_option( VNS_OPTION_URL, '' );
	if ( empty( $url ) ) {
		return '';
	}

	$response = wp_remote_get(
		esc_url_raw( $url ),
		[
			'timeout'    => 10,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
		]
	);

	if ( is_wp_error( $response ) ) {
		// Return last known good value on network error.
		return esc_html( (string) get_option( VNS_OPTION_VERSION, '' ) );
	}

	$body    = json_decode( wp_remote_retrieve_body( $response ), true );
	$version = isset( $body['version'] ) ? sanitize_text_field( $body['version'] ) : '';

	if ( '' !== $version ) {
		set_transient( VNS_TRANSIENT_KEY, $version, vns_cache_hours() * HOUR_IN_SECONDS );
		update_option( VNS_OPTION_VERSION,    $version );
		update_option( VNS_OPTION_LAST_CHECK, time() );
	}

	return esc_html( $version );
}

// ---------------------------------------------------------------------------
// Shortcode
// ---------------------------------------------------------------------------

/**
 * [version_number] shortcode callback.
 *
 * @return string
 */
function vns_shortcode_output(): string {
	return vns_get_version();
}
add_shortcode( 'version_number', 'vns_shortcode_output' );

// ---------------------------------------------------------------------------
// Load plugin text domain
// ---------------------------------------------------------------------------

add_action( 'init', function () {
	load_plugin_textdomain(
		'version-number-shortcode',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
} );

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Version Number Shortcode', 'version-number-shortcode' ),
		__( 'Version Shortcode',        'version-number-shortcode' ),
		'manage_options',
		'version-number-shortcode',
		'vns_render_settings_page'
	);
} );

// ---------------------------------------------------------------------------
// Form handler
// ---------------------------------------------------------------------------

add_action( 'admin_init', function () {
	if ( ! isset( $_POST['vns_action'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'vns_settings_action', 'vns_nonce' );

	$action = sanitize_key( $_POST['vns_action'] );

	if ( in_array( $action, [ 'save', 'save_and_check' ], true ) ) {
		$new_url      = esc_url_raw( trim( sanitize_text_field( wp_unslash( $_POST['vns_url'] ?? '' ) ) ) );
		$new_interval = (int) ( $_POST['vns_interval'] ?? 6 );

		if ( ! array_key_exists( $new_interval, vns_intervals() ) ) {
			$new_interval = 6;
		}

		$old_url      = (string) get_option( VNS_OPTION_URL, '' );
		$old_interval = vns_cache_hours();

		update_option( VNS_OPTION_URL,      $new_url );
		update_option( VNS_OPTION_INTERVAL, $new_interval );

		// Bust cache whenever the source URL or interval changes.
		if ( $new_url !== $old_url || $new_interval !== $old_interval ) {
			delete_transient( VNS_TRANSIENT_KEY );
		}
	}

	if ( in_array( $action, [ 'check', 'save_and_check' ], true ) ) {
		vns_get_version( true );
	}

	wp_safe_redirect(
		add_query_arg(
			[ 'page' => 'version-number-shortcode', 'updated' => '1' ],
			admin_url( 'options-general.php' )
		)
	);
	exit;
} );

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

/**
 * Renders the Settings > Version Shortcode admin page.
 */
function vns_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$url        = (string) get_option( VNS_OPTION_URL, '' );
	$version    = (string) get_option( VNS_OPTION_VERSION, '' );
	$last_check = (int) get_option( VNS_OPTION_LAST_CHECK, 0 );
	$interval   = vns_cache_hours();
	$updated    = isset( $_GET['updated'] );

	$last_check_label = $last_check
		/* translators: %s: human-readable time difference, e.g. "2 hours" */
		? sprintf( __( '%s ago', 'version-number-shortcode' ), human_time_diff( $last_check, time() ) )
		: __( 'Never', 'version-number-shortcode' );

	$next_check_ts    = $last_check + ( $interval * HOUR_IN_SECONDS );
	$next_check_label = $last_check
		/* translators: %s: human-readable time difference, e.g. "4 hours" */
		? sprintf( __( 'in %s', 'version-number-shortcode' ), human_time_diff( time(), $next_check_ts ) )
		: __( 'After first fetch', 'version-number-shortcode' );
	?>
	<style>
		.vns-wrap{max-width:680px;margin:32px 0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
		.vns-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px 32px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
		.vns-card h2{margin:0 0 6px;font-size:15px;font-weight:600;color:#1e293b}
		.vns-card p.vns-desc{margin:0 0 20px;color:#64748b;font-size:13px}
		.vns-field{margin-bottom:18px}
		.vns-field label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
		.vns-field input[type=url],.vns-field select{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;color:#1e293b;background:#f9fafb;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;appearance:none;-webkit-appearance:none}
		.vns-field select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:34px;cursor:pointer}
		.vns-field input:focus,.vns-field select:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12);background:#fff}
		.vns-hint{font-size:12px;color:#94a3b8;margin-top:6px}
		.vns-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap;align-items:center}
		.vns-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:background .15s,transform .1s;text-decoration:none;line-height:1}
		.vns-btn:active{transform:scale(.97)}
		.vns-btn-primary{background:#6366f1;color:#fff}.vns-btn-primary:hover{background:#4f46e5;color:#fff}
		.vns-btn-secondary{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0}.vns-btn-secondary:hover{background:#e2e8f0;color:#374151}
		.vns-btn-check{background:#0f172a;color:#fff}.vns-btn-check:hover{background:#1e293b;color:#fff}
		.vns-status-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
		@media(max-width:600px){.vns-status-grid{grid-template-columns:1fr 1fr}}
		.vns-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px}
		.vns-stat-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:6px}
		.vns-stat-value{font-size:17px;font-weight:700;color:#1e293b;word-break:break-all}
		.vns-stat-value.empty{color:#cbd5e1;font-size:13px;font-weight:400}
		.vns-shortcode-box{background:#f1f5f9;border:1px dashed #cbd5e1;border-radius:6px;padding:10px 14px;font-family:"SF Mono","Fira Code",monospace;font-size:14px;color:#6366f1;display:inline-block;margin-top:4px}
		.vns-notice{display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 16px;font-size:13px;color:#166534;margin-bottom:20px}
	</style>

	<div class="wrap vns-wrap">
		<h1 style="font-size:20px;font-weight:700;color:#0f172a;margin-bottom:4px;">
			<?php esc_html_e( 'Version Number Shortcode', 'version-number-shortcode' ); ?>
		</h1>
		<p style="color:#64748b;font-size:13px;margin-top:0;">
			<?php esc_html_e( 'Fetch a version number from any remote JSON file and drop it anywhere with a shortcode.', 'version-number-shortcode' ); ?>
		</p>

		<?php if ( $updated ) : ?>
		<div class="vns-notice">
			<span style="font-size:16px;">&#10003;</span>
			<?php
			if ( $version ) {
				printf(
					/* translators: %s: version number string */
					esc_html__( 'Settings saved &mdash; version %s fetched successfully.', 'version-number-shortcode' ),
					'<strong>' . esc_html( $version ) . '</strong>'
				);
			} else {
				esc_html_e( 'Settings saved.', 'version-number-shortcode' );
			}
			?>
		</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'vns_settings_action', 'vns_nonce' ); ?>

			<div class="vns-card">
				<h2><?php esc_html_e( 'Settings', 'version-number-shortcode' ); ?></h2>
				<p class="vns-desc"><?php esc_html_e( 'Configure the remote JSON source and how often the version is refreshed.', 'version-number-shortcode' ); ?></p>

				<div class="vns-field">
					<label for="vns_url"><?php esc_html_e( 'Release JSON URL', 'version-number-shortcode' ); ?></label>
					<input
						type="url"
						id="vns_url"
						name="vns_url"
						value="<?php echo esc_attr( $url ); ?>"
						placeholder="https://example.com/release.json"
					/>
					<p class="vns-hint">
						<?php esc_html_e( 'Expected JSON format:', 'version-number-shortcode' ); ?>
						<code>{ "version": "1.2.3" }</code>
					</p>
				</div>

				<div class="vns-field">
					<label for="vns_interval"><?php esc_html_e( 'Check Frequency', 'version-number-shortcode' ); ?></label>
					<select id="vns_interval" name="vns_interval">
						<?php foreach ( vns_intervals() as $hours => $label ) : ?>
							<option value="<?php echo esc_attr( $hours ); ?>" <?php selected( $interval, $hours ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="vns-hint"><?php esc_html_e( 'How long the fetched version is cached before re-checking the remote URL.', 'version-number-shortcode' ); ?></p>
				</div>

				<div class="vns-actions">
					<button type="submit" name="vns_action" value="save" class="vns-btn vns-btn-primary">
						<?php esc_html_e( 'Save Settings', 'version-number-shortcode' ); ?>
					</button>
					<button type="submit" name="vns_action" value="save_and_check" class="vns-btn vns-btn-check">
						&#8635; <?php esc_html_e( 'Save &amp; Check Now', 'version-number-shortcode' ); ?>
					</button>
					<button type="submit" name="vns_action" value="check" class="vns-btn vns-btn-secondary">
						&#8635; <?php esc_html_e( 'Check Now', 'version-number-shortcode' ); ?>
					</button>
				</div>
			</div>

			<div class="vns-card">
				<h2><?php esc_html_e( 'Status', 'version-number-shortcode' ); ?></h2>
				<p class="vns-desc"><?php esc_html_e( 'Current cache state. Next auto-check is based on the configured frequency.', 'version-number-shortcode' ); ?></p>
				<div class="vns-status-grid">
					<div class="vns-stat">
						<div class="vns-stat-label"><?php esc_html_e( 'Cached Version', 'version-number-shortcode' ); ?></div>
						<div class="vns-stat-value <?php echo $version ? '' : 'empty'; ?>">
							<?php echo $version ? esc_html( $version ) : esc_html__( 'Not fetched yet', 'version-number-shortcode' ); ?>
						</div>
					</div>
					<div class="vns-stat">
						<div class="vns-stat-label"><?php esc_html_e( 'Last Checked', 'version-number-shortcode' ); ?></div>
						<div class="vns-stat-value <?php echo $last_check ? '' : 'empty'; ?>">
							<?php echo esc_html( $last_check_label ); ?>
						</div>
					</div>
					<div class="vns-stat">
						<div class="vns-stat-label"><?php esc_html_e( 'Next Auto-Check', 'version-number-shortcode' ); ?></div>
						<div class="vns-stat-value <?php echo $last_check ? '' : 'empty'; ?>">
							<?php echo esc_html( $next_check_label ); ?>
						</div>
					</div>
				</div>
			</div>
		</form>

		<div class="vns-card">
			<h2><?php esc_html_e( 'Usage', 'version-number-shortcode' ); ?></h2>
			<p class="vns-desc"><?php esc_html_e( 'Place this shortcode anywhere &mdash; pages, posts, widgets, or page builders.', 'version-number-shortcode' ); ?></p>
			<div class="vns-shortcode-box">[version_number]</div>
		</div>
	</div>
	<?php
}
