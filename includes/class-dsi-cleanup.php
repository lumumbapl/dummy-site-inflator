<?php
/**
 * DSI Cleanup Class
 *
 * Handles batch deletion of dummy posts, their media attachments,
 * and the cached source image.
 *
 * @package DummySiteInflator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DSI_Cleanup
 */
class DSI_Cleanup {

	/**
	 * Get the total count of dummy posts in the database.
	 *
	 * @return int
	 */
	public static function get_dummy_post_count() {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => DSI_META_KEY,
						'value' => '1',
					),
				),
				'fields'         => 'ids',
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Delete a batch of dummy posts and their associated attachments.
	 *
	 * @param int $batch_size Number of posts to delete in this batch.
	 * @return array { deleted: int, remaining: int }
	 */
	public static function delete_batch( $batch_size = DSI_BATCH_SIZE ) {
		$post_ids = self::get_dummy_post_ids( $batch_size );

		$deleted = 0;
		foreach ( $post_ids as $post_id ) {
			// Delete the attachment registered to this post.
			$attachment_id = get_post_meta( $post_id, '_dummy_inflator_attachment_id', true );
			if ( $attachment_id ) {
				wp_delete_attachment( (int) $attachment_id, true ); // true = force delete from disk.
			}

			// Permanently delete the post.
			wp_delete_post( $post_id, true );
			$deleted++;
		}

		$remaining = self::get_dummy_post_count();

		return array(
			'deleted'   => $deleted,
			'remaining' => $remaining,
		);
	}

	/**
	 * Delete the locally cached source image.
	 *
	 * @return bool True if deleted or didn't exist, false on failure.
	 */
	public static function delete_cached_source_image() {
		$upload_dir = wp_upload_dir();
		$local_path = trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator/' . DSI_IMAGE_FILENAME;

		if ( file_exists( $local_path ) ) {
			return wp_delete_file( $local_path ) || unlink( $local_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return true;
	}

	/**
	 * Get an array of dummy post IDs.
	 *
	 * @param int $limit Max number of IDs to retrieve.
	 * @return int[]
	 */
	private static function get_dummy_post_ids( $limit = DSI_BATCH_SIZE ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => $limit,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => DSI_META_KEY,
						'value' => '1',
					),
				),
				'fields'         => 'ids',
			)
		);

		return $query->posts;
	}
}
