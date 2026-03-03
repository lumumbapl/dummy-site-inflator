<?php
/**
 * DSI Generator Class
 *
 * Handles downloading the source image, copying it per post,
 * registering media attachments, and inserting dummy posts.
 *
 * @package DummySiteInflator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DSI_Generator
 */
class DSI_Generator {

	/**
	 * Lorem Ipsum title templates.
	 *
	 * @var array
	 */
	private static $title_templates = array(
		'The Ultimate Guide to %s',
		'Everything You Need to Know About %s',
		'Why %s Matters More Than You Think',
		'A Deep Dive into %s',
		'How %s Is Changing the World',
		'The Hidden Truth About %s',
		'Understanding %s: A Complete Overview',
		'%s: What Nobody Tells You',
		'The Future of %s',
		'Exploring the World of %s',
		'%s Explained for Beginners',
		'Top Insights on %s',
		'%s: Myths vs. Reality',
		'Getting Started with %s',
		'Why Everyone Is Talking About %s',
		'The Science Behind %s',
		'A Beginner\'s Guide to %s',
		'%s: Everything That Matters',
		'Making Sense of %s',
		'The Complete Story of %s',
	);

	/**
	 * Title subjects to fill into templates.
	 *
	 * @var array
	 */
	private static $title_subjects = array(
		'Modern Technology',
		'Digital Transformation',
		'Web Performance',
		'Cloud Hosting',
		'Content Management',
		'Online Marketing',
		'User Experience',
		'Data Security',
		'Open Source Software',
		'Website Optimization',
		'Server Infrastructure',
		'Mobile Development',
		'E-commerce Trends',
		'Artificial Intelligence',
		'Search Engine Optimization',
		'Social Media Strategy',
		'Email Marketing',
		'Web Accessibility',
		'Cybersecurity Basics',
		'Sustainable Technology',
		'Database Management',
		'API Development',
		'DevOps Practices',
		'Remote Work Tools',
		'Digital Privacy',
	);

	/**
	 * Lorem Ipsum paragraph pool (~50 words each).
	 *
	 * @var array
	 */
	private static $lorem_paragraphs = array(
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
		'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra.',
		'Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis molestie dictum semper, metus arcu fermentum odio, vitae molestie mauris metus at nunc. Duis eget arcu. Vivamus luctus libero et risus. Proin id metus. In hac habitasse platea dictumst. Donec aliquet, tortor sed accumsan bibendum, erat ligula aliquet magna.',
		'Fusce fermentum. Nullam varius nulla at quam tempor, in accumsan lectus dignissim. Morbi luctus, wisi viverra faucibus pretium, nibh est placerat odio, nec commodo wisi enim eget quam. Quisque libero metus, condimentum nec, tempor a, commodo mollis, magna. Vestibulum ullamcorper mauris at ligula. Fuse commodo. Proin mattis lacinia justo.',
		'Sed pretium blandit orci. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. Nullam mauris orci, aliquet et, iaculis et, viverra vitae, ligula. Nulla ut felis in purus aliquam imperdiet. Maecenas aliquet mollis lectus. Vivamus consectetuer risus et tortor.',
		'Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus. Phasellus ultrices nulla quis nibh. Quisque a lectus. Donec consectetuer ligula vulputate sem tristique cursus. Nam nulla quam, gravida non, commodo a, sodales sit amet, nisi. Nullam in massa. Suspendisse vitae nisl sit amet augue bibendum aliquam.',
		'Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus. Phasellus ultrices nulla quis nibh. Quisque a lectus donec consectetuer ligula vulputate.',
		'Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque. Nulla facilisi. Quisque tempus lacus vel augue placerat vulputate.',
		'Vivamus pretium ornare risus. Donec molestie facilisis ante. Etiam sed est ultrices diam eleifend tincidunt. Suspendisse sed mauris vitae elit sollicitudin malesuada. Maecenas ultricies mollis augue. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat nunc.',
		'Integer vulputate sem a nibh rutrum consequat. Nunc massa metus, elementum vel, accumsan vel, laoreet in, felis. Nulla facilisi. In id erat non orci commodo lobortis. Proin neque massa, cursus ut, gravida ut, lobortis eget, lacus. Sed diam. Praesent fermentum tempor tellus. Nullam tempus. Mauris ac felis vel velit tristique imperdiet.',
	);

	/**
	 * Get or download the source image to local storage.
	 *
	 * @return string|WP_Error Local path to the source image or WP_Error on failure.
	 */
	public static function get_source_image() {
		$upload_dir = wp_upload_dir();
		$dsi_dir    = trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator';
		$local_path = trailingslashit( $dsi_dir ) . DSI_IMAGE_FILENAME;

		// If already downloaded, return the cached path.
		if ( file_exists( $local_path ) && filesize( $local_path ) > 0 ) {
			return $local_path;
		}

		// Ensure the directory exists.
		if ( ! file_exists( $dsi_dir ) ) {
			wp_mkdir_p( $dsi_dir );
		}

		// Download the image from the remote URL.
		$response = wp_remote_get(
			DSI_IMAGE_SOURCE_URL,
			array(
				'timeout'  => 120,
				'stream'   => true,
				'filename' => $local_path,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'dsi_download_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to download source image: %s', 'dummy-site-inflator' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $response_code ) {
			return new WP_Error(
				'dsi_download_http_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'Source image download returned HTTP %d.', 'dummy-site-inflator' ),
					$response_code
				)
			);
		}

		if ( ! file_exists( $local_path ) || filesize( $local_path ) === 0 ) {
			return new WP_Error(
				'dsi_file_empty',
				__( 'Downloaded image file is empty or missing.', 'dummy-site-inflator' )
			);
		}

		return $local_path;
	}

	/**
	 * Generate a single dummy post.
	 *
	 * @param string $source_image_path Local path to the source image.
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	public static function generate_post( $source_image_path ) {
		// 1. Generate unique title and content.
		$title   = self::generate_title();
		$content = self::generate_content();

		// 2. Insert the post first (we need the post ID for the image filename).
		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => '<!-- placeholder -->', // Temporary; updated after image upload.
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// 3. Copy the source image to uploads with a unique filename.
		$attachment_id = self::attach_image_copy( $source_image_path, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			// Clean up the post if image fails.
			wp_delete_post( $post_id, true );
			return $attachment_id;
		}

		// 4. Get the image URL.
		$image_url = wp_get_attachment_url( $attachment_id );

		// 5. Build final post content with image in the middle.
		$final_content = self::build_content_with_image( $content, $image_url, $attachment_id );

		// 6. Update the post with final content.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $final_content,
			)
		);

		// 7. Set featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		// 8. Tag the post as a dummy post for easy cleanup.
		update_post_meta( $post_id, DSI_META_KEY, '1' );
		update_post_meta( $post_id, '_dummy_inflator_attachment_id', $attachment_id );

		return $post_id;
	}

	/**
	 * Copy the source image and register it as a media attachment.
	 *
	 * @param string $source_path Path to the source image.
	 * @param int    $post_id     The parent post ID.
	 * @return int|WP_Error Attachment ID or WP_Error.
	 */
	private static function attach_image_copy( $source_path, $post_id ) {
		$upload_dir = wp_upload_dir();

		// Build a unique filename.
		$unique_name = 'dummy-inflator-' . $post_id . '-' . uniqid() . '.png';
		$dest_path   = trailingslashit( $upload_dir['path'] ) . $unique_name;
		$dest_url    = trailingslashit( $upload_dir['url'] ) . $unique_name;

		// Copy the file.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! @copy( $source_path, $dest_path ) ) {
			return new WP_Error(
				'dsi_copy_failed',
				__( 'Failed to copy image file to uploads directory.', 'dummy-site-inflator' )
			);
		}

		// Prepare attachment data.
		$attachment = array(
			'guid'           => $dest_url,
			'post_mime_type' => 'image/png',
			'post_title'     => sanitize_file_name( $unique_name ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert into media library.
		$attachment_id = wp_insert_attachment( $attachment, $dest_path, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Skip full thumbnail generation (very memory-intensive for large files).
		// Instead, store minimal metadata manually so the attachment is valid.
		$image_size = @getimagesize( $dest_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$meta       = array(
			'width'  => $image_size ? $image_size[0] : 0,
			'height' => $image_size ? $image_size[1] : 0,
			'file'   => _wp_relative_upload_path( $dest_path ),
			'sizes'  => array(), // No thumbnails generated — intentional.
		);
		wp_update_attachment_metadata( $attachment_id, $meta );

		return $attachment_id;
	}

	/**
	 * Generate a random post title.
	 *
	 * @return string
	 */
	private static function generate_title() {
		$template = self::$title_templates[ array_rand( self::$title_templates ) ];
		$subject  = self::$title_subjects[ array_rand( self::$title_subjects ) ];
		return sprintf( $template, $subject );
	}

	/**
	 * Generate ~300 words of varied Lorem Ipsum content.
	 *
	 * Returns an array with 'before' and 'after' parts (to sandwich the image).
	 *
	 * @return array { before: string, after: string }
	 */
	private static function generate_content() {
		// Shuffle paragraph pool and pick 6.
		$pool = self::$lorem_paragraphs;
		shuffle( $pool );
		$selected = array_slice( $pool, 0, 6 );

		// Split 3 paragraphs before the image, 3 after.
		return array(
			'before' => implode( "\n\n", array_slice( $selected, 0, 3 ) ),
			'after'  => implode( "\n\n", array_slice( $selected, 3, 3 ) ),
		);
	}

	/**
	 * Build the final post content with the image sandwiched in the middle.
	 *
	 * @param array  $content       { before: string, after: string }
	 * @param string $image_url     URL to the attached image.
	 * @param int    $attachment_id The attachment ID.
	 * @return string
	 */
	private static function build_content_with_image( $content, $image_url, $attachment_id ) {
		$image_html = sprintf(
			'<figure class="wp-block-image size-full"><img src="%s" alt="%s" class="wp-image-%d"/></figure>',
			esc_url( $image_url ),
			esc_attr__( 'Dummy inflator image', 'dummy-site-inflator' ),
			absint( $attachment_id )
		);

		return wp_kses_post(
			'<p>' . $content['before'] . '</p>' .
			"\n\n" . $image_html . "\n\n" .
			'<p>' . $content['after'] . '</p>'
		);
	}
}
