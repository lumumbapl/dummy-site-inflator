<?php
/**
 * DSI Admin Class
 *
 * Registers the admin menu page, renders the UI, enqueues scripts,
 * and handles all AJAX requests.
 *
 * @package DummySiteInflator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DSI_Admin
 */
class DSI_Admin {

	/**
	 * Single instance.
	 *
	 * @var DSI_Admin
	 */
	private static $instance = null;

	/**
	 * Get the single instance.
	 *
	 * @return DSI_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — register hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_dsi_generate_batch', array( $this, 'ajax_generate_batch' ) );
		add_action( 'wp_ajax_dsi_delete_batch', array( $this, 'ajax_delete_batch' ) );
		add_action( 'wp_ajax_dsi_check_image', array( $this, 'ajax_check_image' ) );
	}

	/**
	 * Register admin menu item under Tools.
	 */
	public function register_menu() {
		add_management_page(
			__( 'Dummy Site Inflator', 'dummy-site-inflator' ),
			__( 'Dummy Site Inflator', 'dummy-site-inflator' ),
			'manage_options',
			'dummy-site-inflator',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles (only on our page).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'tools_page_dummy-site-inflator' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'dsi-admin-styles',
			DSI_PLUGIN_URL . 'admin/css/dsi-admin.css',
			array(),
			DSI_VERSION
		);

		wp_enqueue_script(
			'dsi-admin-script',
			DSI_PLUGIN_URL . 'admin/js/dsi-admin.js',
			array( 'jquery' ),
			DSI_VERSION,
			true
		);

		wp_localize_script(
			'dsi-admin-script',
			'dsiData',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'dsi_nonce' ),
				'batchSize'      => DSI_BATCH_SIZE,
				'strings'        => array(
					'generating'       => __( 'Generating posts...', 'dummy-site-inflator' ),
					'deleting'         => __( 'Deleting posts...', 'dummy-site-inflator' ),
					'generated'        => __( 'Generated %1$d of %2$d posts', 'dummy-site-inflator' ),
					'deleted'          => __( 'Deleted %d posts so far...', 'dummy-site-inflator' ),
					'allDone'          => __( 'All done! %d posts generated successfully.', 'dummy-site-inflator' ),
					'allDeleted'       => __( 'All dummy posts deleted successfully.', 'dummy-site-inflator' ),
					'confirmDelete'    => __( 'Are you sure you want to delete ALL dummy posts and their images? This cannot be undone.', 'dummy-site-inflator' ),
					'errorOccurred'    => __( 'An error occurred: ', 'dummy-site-inflator' ),
					'enterValidNumber' => __( 'Please enter a valid number of posts (minimum 1).', 'dummy-site-inflator' ),
					'downloadingImage' => __( 'Downloading source image (one-time, please wait)...', 'dummy-site-inflator' ),
				),
			)
		);
	}

	/**
	 * Render the admin page HTML.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dummy-site-inflator' ) );
		}

		$dummy_post_count = DSI_Cleanup::get_dummy_post_count();
		$upload_dir       = wp_upload_dir();
		$source_exists    = file_exists( trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator/' . DSI_IMAGE_FILENAME );
		?>
		<div class="wrap dsi-wrap">

			<h1 class="dsi-heading">
				<span class="dsi-icon">📦</span>
				<?php esc_html_e( 'Dummy Site Inflator', 'dummy-site-inflator' ); ?>
			</h1>

			<p class="dsi-tagline">
				<?php esc_html_e( 'Generate dummy posts with large images to inflate your test site for QA, load testing, and hosting benchmarks.', 'dummy-site-inflator' ); ?>
			</p>

			<?php if ( ! $source_exists ) : ?>
			<div class="dsi-notice dsi-notice--info">
				<strong><?php esc_html_e( 'First Run:', 'dummy-site-inflator' ); ?></strong>
				<?php esc_html_e( 'The source image will be downloaded from your server the first time you generate posts. This is a one-time download.', 'dummy-site-inflator' ); ?>
			</div>
			<?php endif; ?>

			<!-- ===================== GENERATE SECTION ===================== -->
			<div class="dsi-card">
				<h2><?php esc_html_e( 'Generate Dummy Posts', 'dummy-site-inflator' ); ?></h2>

				<div class="dsi-field-row">
					<label for="dsi-post-count">
						<?php esc_html_e( 'Number of posts to generate:', 'dummy-site-inflator' ); ?>
					</label>
					<input
						type="number"
						id="dsi-post-count"
						min="1"
						max="10000"
						value="10"
						class="dsi-number-input"
					/>
				</div>

				<div class="dsi-size-estimate">
					<?php esc_html_e( 'Estimated site size increase:', 'dummy-site-inflator' ); ?>
					<strong id="dsi-size-estimate">~460 MB</strong>
				</div>

				<button id="dsi-generate-btn" class="button button-primary dsi-btn dsi-btn--generate">
					<?php esc_html_e( 'Generate Posts', 'dummy-site-inflator' ); ?>
				</button>

				<!-- Progress area -->
				<div id="dsi-generate-progress" class="dsi-progress-wrap" style="display:none;">
					<div class="dsi-progress-bar-track">
						<div id="dsi-generate-bar" class="dsi-progress-bar"></div>
					</div>
					<p id="dsi-generate-status" class="dsi-progress-status"></p>
				</div>

				<div id="dsi-generate-result" class="dsi-result" style="display:none;"></div>
			</div>

			<!-- ===================== STATUS SECTION ===================== -->
			<div class="dsi-card dsi-card--status">
				<h2><?php esc_html_e( 'Current Status', 'dummy-site-inflator' ); ?></h2>
				<div class="dsi-stat-row">
					<span><?php esc_html_e( 'Dummy posts in database:', 'dummy-site-inflator' ); ?></span>
					<strong id="dsi-post-count-display"><?php echo esc_html( number_format( $dummy_post_count ) ); ?></strong>
				</div>
				<div class="dsi-stat-row">
					<span><?php esc_html_e( 'Estimated disk usage:', 'dummy-site-inflator' ); ?></span>
					<strong id="dsi-disk-usage-display">
						<?php
						$size_mb = $dummy_post_count * 46;
						if ( $size_mb >= 1024 ) {
							echo esc_html( round( $size_mb / 1024, 2 ) . ' GB' );
						} else {
							echo esc_html( $size_mb . ' MB' );
						}
						?>
					</strong>
				</div>
				<div class="dsi-stat-row">
					<span><?php esc_html_e( 'Source image cached:', 'dummy-site-inflator' ); ?></span>
					<strong>
						<?php if ( $source_exists ) : ?>
							<span class="dsi-badge dsi-badge--green"><?php esc_html_e( 'Yes', 'dummy-site-inflator' ); ?></span>
						<?php else : ?>
							<span class="dsi-badge dsi-badge--grey"><?php esc_html_e( 'Not yet downloaded', 'dummy-site-inflator' ); ?></span>
						<?php endif; ?>
					</strong>
				</div>
			</div>

			<!-- ===================== DELETE SECTION ===================== -->
			<?php if ( $dummy_post_count > 0 ) : ?>
			<div class="dsi-card dsi-card--danger">
				<h2><?php esc_html_e( 'Cleanup', 'dummy-site-inflator' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %d: number of dummy posts */
						esc_html__( 'You have %d dummy post(s) in the database. Clicking the button below will permanently delete all of them along with their images.', 'dummy-site-inflator' ),
						(int) $dummy_post_count
					);
					?>
				</p>

				<button id="dsi-delete-btn" class="button dsi-btn dsi-btn--delete">
					<?php esc_html_e( 'Delete All Dummy Posts', 'dummy-site-inflator' ); ?>
				</button>

				<!-- Delete progress area -->
				<div id="dsi-delete-progress" class="dsi-progress-wrap" style="display:none;">
					<div class="dsi-progress-bar-track">
						<div id="dsi-delete-bar" class="dsi-progress-bar dsi-progress-bar--red"></div>
					</div>
					<p id="dsi-delete-status" class="dsi-progress-status"></p>
				</div>

				<div id="dsi-delete-result" class="dsi-result" style="display:none;"></div>
			</div>
			<?php endif; ?>

		</div><!-- .dsi-wrap -->
		<?php
	}

	/**
	 * AJAX: Generate a batch of posts.
	 */
	public function ajax_generate_batch() {
		check_ajax_referer( 'dsi_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dummy-site-inflator' ) ) );
		}

		// Extend limits for this heavy request.
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		@ini_set( 'memory_limit', '512M' );
		@set_time_limit( 120 );
		// phpcs:enable

		$total_posts      = isset( $_POST['total'] ) ? absint( $_POST['total'] ) : 0;
		$generated_so_far = isset( $_POST['generated'] ) ? absint( $_POST['generated'] ) : 0;

		if ( $total_posts < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post count.', 'dummy-site-inflator' ) ) );
		}

		// Step 1: Ensure source image is available.
		$source_image = DSI_Generator::get_source_image();
		if ( is_wp_error( $source_image ) ) {
			wp_send_json_error( array( 'message' => $source_image->get_error_message() ) );
		}

		// Step 2: Generate this batch.
		$batch_size    = min( DSI_BATCH_SIZE, $total_posts - $generated_so_far );
		$batch_created = 0;
		$errors        = array();

		for ( $i = 0; $i < $batch_size; $i++ ) {
			$result = DSI_Generator::generate_post( $source_image );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} else {
				$batch_created++;
			}
		}

		$new_total_generated = $generated_so_far + $batch_created;
		$is_done             = $new_total_generated >= $total_posts;

		wp_send_json_success(
			array(
				'generated'  => $new_total_generated,
				'total'      => $total_posts,
				'batch_done' => $batch_created,
				'is_done'    => $is_done,
				'errors'     => $errors,
			)
		);
	}

	/**
	 * AJAX: Delete a batch of dummy posts.
	 */
	public function ajax_delete_batch() {
		check_ajax_referer( 'dsi_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dummy-site-inflator' ) ) );
		}

		$result = DSI_Cleanup::delete_batch( DSI_BATCH_SIZE );

		wp_send_json_success(
			array(
				'deleted'   => $result['deleted'],
				'remaining' => $result['remaining'],
				'is_done'   => $result['remaining'] === 0,
			)
		);
	}

	/**
	 * AJAX: Check if source image is already cached.
	 */
	public function ajax_check_image() {
		check_ajax_referer( 'dsi_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dummy-site-inflator' ) ) );
		}

		$upload_dir   = wp_upload_dir();
		$source_path  = trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator/' . DSI_IMAGE_FILENAME;
		$image_cached = file_exists( $source_path ) && filesize( $source_path ) > 0;

		wp_send_json_success( array( 'cached' => $image_cached ) );
	}
}
