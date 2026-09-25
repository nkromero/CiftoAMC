<?php
/**
 * Plugin Name: ODR RRUFF CIF Viewer
 * Description: Reads a CIF and creates the AMC header
 * Version: 1.0.0
 * Author: Nathan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIF_VIEWER_VERSION', '1.0.0' );
define( 'CIF_VIEWER_URL', plugin_dir_url( __FILE__ ) );

function cif_viewer_register_settings() {
	register_setting(
		'cif_viewer_settings',
		'cif_viewer_api_token',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'cif_viewer_register_settings' );

function cif_viewer_add_settings_page() {
	add_options_page( 'CIF Viewer', 'CIF Viewer', 'manage_options', 'cif-viewer', 'cif_viewer_render_settings_page' );
}
add_action( 'admin_menu', 'cif_viewer_add_settings_page' );

function cif_viewer_render_settings_page() {
	?>
	<div class="wrap">
		<h1>CIF Viewer Settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'cif_viewer_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="cif_viewer_api_token">AMCSD API Token</label></th>
					<td>
						<input type="password" id="cif_viewer_api_token" name="cif_viewer_api_token" value="<?php echo esc_attr( get_option( 'cif_viewer_api_token', '' ) ); ?>" size="60">
						<p class="description">Optional. Used by the [odr_rruff_cifviewer] shortcode to fetch records from the AMCSD API automatically.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// A fixed version string means the enqueued URL (style.css?ver=1.0.0) never
// changes between edits, so browsers/caching plugins keep serving the old file
// after every update - use the file's own mtime instead so it always bumps.
function cif_viewer_asset_version( $relative_path ) {
	$full_path = plugin_dir_path( __FILE__ ) . $relative_path;
	return file_exists( $full_path ) ? (string) filemtime( $full_path ) : CIF_VIEWER_VERSION;
}

function cif_viewer_register_assets() {
	wp_register_style( 'cif-viewer-style', CIF_VIEWER_URL . 'assets/style.css', array(), cif_viewer_asset_version( 'assets/style.css' ) );
	wp_register_script( 'cif-viewer-spacegroups', CIF_VIEWER_URL . 'assets/spacegroups.js', array(), cif_viewer_asset_version( 'assets/spacegroups.js' ), true );
	wp_register_script( 'cif-viewer-app', CIF_VIEWER_URL . 'assets/app.js', array( 'cif-viewer-spacegroups' ), cif_viewer_asset_version( 'assets/app.js' ), true );
	wp_register_script( 'cif-viewer-amc2cif', CIF_VIEWER_URL . 'assets/amc2cif.js', array( 'cif-viewer-spacegroups' ), cif_viewer_asset_version( 'assets/amc2cif.js' ), true );
	wp_register_script( 'cif-viewer-amc2cif-ui', CIF_VIEWER_URL . 'assets/amc2cif-ui.js', array( 'cif-viewer-app', 'cif-viewer-amc2cif' ), cif_viewer_asset_version( 'assets/amc2cif-ui.js' ), true );

	$token = get_option( 'cif_viewer_api_token', '' );
	wp_add_inline_script( 'cif-viewer-spacegroups', 'window.AMCSD_API_TOKEN = ' . wp_json_encode( $token ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'cif_viewer_register_assets' );

function cif_viewer_shortcode() {
	wp_enqueue_style( 'cif-viewer-style' );
	wp_enqueue_script( 'cif-viewer-spacegroups' );
	wp_enqueue_script( 'cif-viewer-app' );
	wp_enqueue_script( 'cif-viewer-amc2cif' );
	wp_enqueue_script( 'cif-viewer-amc2cif-ui' );

	ob_start();
	?>
	<div class="cif-viewer-app">
		<h1>CIF Viewer</h1>

		<div id="panel">
			<p>Select a .cif file to read its data.</p>

			<input type="file" id="fileInput" accept=".cif,text/plain">

			<div id="apiSection">
				<h3>AMCSD Record</h3>
				<div id="apiStatus"></div>
			</div>

			<div id="output">
				<h3>AMC Header</h3>
				<button id="copyHeaderBtn" class="cif-viewer-btn" type="button">Copy</button>
				<textarea id="amcHeaderOutput" readonly rows="1"></textarea>
				<p id="crystalSystemDisplay"></p>
			</div>

			<div id="amcToCifSection">
				<h3>AMC &rarr; CIF</h3>
				<p>Select a .amc file to convert it to CIF.</p>
				<input type="file" id="amcFileInput" accept=".amc,text/plain">
				<div id="amcToCifStatus"></div>
				<button id="copyAmcToCifBtn" class="cif-viewer-btn" type="button">Copy</button>
				<textarea id="amcToCifOutput" readonly rows="1"></textarea>
			</div>

			<div id="citationSection">
				<h3>Format Citation</h3>
				<p>Paste a citation like: Authors (Year) Title. Journal Volume, Pages</p>
				<textarea id="citationInput" rows="1"></textarea>
				<button id="formatCitationBtn" class="cif-viewer-btn" type="button">Format</button>
				<button id="copyCitationBtn" class="cif-viewer-btn" type="button">Copy</button>
				<div id="citationStatus"></div>
				<textarea id="citationOutput" readonly rows="1"></textarea>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'odr_rruff_cifviewer', 'cif_viewer_shortcode' );
