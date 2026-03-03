/* global dsiData, jQuery */
( function ( $ ) {
	'use strict';

	var IMG_SIZE_MB = 46; // Approximate size per image in MB.

	/**
	 * Update the size estimate display when post count changes.
	 */
	function updateSizeEstimate() {
		var count = parseInt( $( '#dsi-post-count' ).val(), 10 );
		if ( isNaN( count ) || count < 1 ) {
			$( '#dsi-size-estimate' ).text( '—' );
			return;
		}
		var totalMb = count * IMG_SIZE_MB;
		var display;
		if ( totalMb >= 1024 ) {
			display = '~' + ( totalMb / 1024 ).toFixed( 2 ) + ' GB';
		} else {
			display = '~' + totalMb + ' MB';
		}
		$( '#dsi-size-estimate' ).text( display );
	}

	/**
	 * Update the progress bar and status text.
	 *
	 * @param {string} barId    - jQuery selector for the bar element.
	 * @param {string} statusId - jQuery selector for the status text.
	 * @param {number} current  - Current value.
	 * @param {number} total    - Total value.
	 * @param {string} message  - Status message.
	 */
	function updateProgress( barId, statusId, current, total, message ) {
		var pct = total > 0 ? Math.min( ( current / total ) * 100, 100 ) : 0;
		$( barId ).css( 'width', pct + '%' );
		$( statusId ).text( message );
	}

	/**
	 * Show a result message.
	 *
	 * @param {string} containerId - jQuery selector.
	 * @param {string} message     - Message text.
	 * @param {string} type        - 'success' or 'error'.
	 */
	function showResult( containerId, message, type ) {
		var $el = $( containerId );
		$el
			.removeClass( 'dsi-result--success dsi-result--error' )
			.addClass( 'dsi-result--' + type )
			.text( message )
			.show();
	}

	/**
	 * Recursively generate posts in batches.
	 *
	 * @param {number} total         - Total posts to generate.
	 * @param {number} generatedSoFar - Posts generated so far.
	 */
	function generateBatch( total, generatedSoFar ) {
		$.post(
			dsiData.ajaxUrl,
			{
				action:    'dsi_generate_batch',
				nonce:     dsiData.nonce,
				total:     total,
				generated: generatedSoFar,
			},
			function ( response ) {
				if ( ! response.success ) {
					showResult( '#dsi-generate-result', dsiData.strings.errorOccurred + response.data.message, 'error' );
					$( '#dsi-generate-btn' ).prop( 'disabled', false ).text( 'Generate Posts' );
					return;
				}

				var data = response.data;
				var statusMsg = dsiData.strings.generated
					.replace( '%1$d', data.generated )
					.replace( '%2$d', data.total );

				updateProgress(
					'#dsi-generate-bar',
					'#dsi-generate-status',
					data.generated,
					data.total,
					statusMsg
				);

				if ( data.is_done ) {
					// All done.
					var doneMsg = dsiData.strings.allDone.replace( '%d', data.total );
					showResult( '#dsi-generate-result', doneMsg, 'success' );
					$( '#dsi-generate-btn' ).prop( 'disabled', false ).text( 'Generate Posts' );

					// Update the status card counts.
					updateStatusCard( data.total );
				} else {
					// Small delay before next batch — gives server a breath.
					setTimeout( function () {
						generateBatch( data.total, data.generated );
					}, 500 );
				}
			}
		).fail( function ( jqXHR ) {
			var errorDetail = jqXHR.status
				? 'HTTP ' + jqXHR.status + ' — ' + ( jqXHR.responseText ? jqXHR.responseText.substring( 0, 200 ) : 'No response body' )
				: 'No response from server (possible timeout or server crash)';
			showResult( '#dsi-generate-result', dsiData.strings.errorOccurred + errorDetail, 'error' );
			$( '#dsi-generate-btn' ).prop( 'disabled', false ).text( 'Generate Posts' );
		} );
	}

	/**
	 * Recursively delete dummy posts in batches.
	 *
	 * @param {number} totalDeleted - Running total of deleted posts.
	 */
	function deleteBatch( totalDeleted ) {
		$.post(
			dsiData.ajaxUrl,
			{
				action: 'dsi_delete_batch',
				nonce:  dsiData.nonce,
			},
			function ( response ) {
				if ( ! response.success ) {
					showResult( '#dsi-delete-result', dsiData.strings.errorOccurred + response.data.message, 'error' );
					$( '#dsi-delete-btn' ).prop( 'disabled', false );
					return;
				}

				var data        = response.data;
				totalDeleted   += data.deleted;

				var statusMsg   = dsiData.strings.deleted.replace( '%d', totalDeleted );
				var progressPct = data.remaining === 0 ? 100 : 50; // Keep indeterminate-ish until done.

				$( '#dsi-delete-bar' ).css( 'width', progressPct + '%' );
				$( '#dsi-delete-status' ).text( statusMsg );

				if ( data.is_done ) {
					$( '#dsi-delete-bar' ).css( 'width', '100%' );
					showResult( '#dsi-delete-result', dsiData.strings.allDeleted, 'success' );
					$( '#dsi-delete-btn' ).prop( 'disabled', false );

					// Hide the cleanup card since there's nothing left.
					$( '.dsi-card--danger' ).fadeOut( 400 );

					// Reset status card.
					$( '#dsi-post-count-display' ).text( '0' );
					$( '#dsi-disk-usage-display' ).text( '0 MB' );
				} else {
					deleteBatch( totalDeleted );
				}
			}
		).fail( function ( jqXHR ) {
			var errorDetail = jqXHR.status
				? 'HTTP ' + jqXHR.status + ' — ' + ( jqXHR.responseText ? jqXHR.responseText.substring( 0, 200 ) : 'No response body' )
				: 'No response from server (possible timeout or server crash)';
			showResult( '#dsi-delete-result', dsiData.strings.errorOccurred + errorDetail, 'error' );
			$( '#dsi-delete-btn' ).prop( 'disabled', false );
		} );
	}

	/**
	 * Update the status card post count and disk usage after generation.
	 *
	 * @param {number} newlyGenerated - Number of posts just generated.
	 */
	function updateStatusCard( newlyGenerated ) {
		var currentCount = parseInt( $( '#dsi-post-count-display' ).text().replace( /,/g, '' ), 10 ) || 0;
		var newCount     = currentCount + newlyGenerated;

		$( '#dsi-post-count-display' ).text( newCount.toLocaleString() );

		var totalMb = newCount * IMG_SIZE_MB;
		var display;
		if ( totalMb >= 1024 ) {
			display = ( totalMb / 1024 ).toFixed( 2 ) + ' GB';
		} else {
			display = totalMb + ' MB';
		}
		$( '#dsi-disk-usage-display' ).text( display );
	}

	// ===================== Event Listeners =====================

	$( document ).ready( function () {

		// Update size estimate live as user types.
		$( '#dsi-post-count' ).on( 'input change', updateSizeEstimate );

		// Generate button click.
		$( '#dsi-generate-btn' ).on( 'click', function () {
			var count = parseInt( $( '#dsi-post-count' ).val(), 10 );
			if ( isNaN( count ) || count < 1 ) {
				alert( dsiData.strings.enterValidNumber );
				return;
			}

			// Reset UI.
			$( '#dsi-generate-result' ).hide().text( '' );
			$( '#dsi-generate-bar' ).css( 'width', '0%' );
			$( '#dsi-generate-status' ).text( dsiData.strings.generating );
			$( '#dsi-generate-progress' ).show();
			$( this ).prop( 'disabled', true );

			// Start batch generation.
			generateBatch( count, 0 );
		} );

		// Delete button click.
		$( '#dsi-delete-btn' ).on( 'click', function () {
			if ( ! window.confirm( dsiData.strings.confirmDelete ) ) {
				return;
			}

			// Reset UI.
			$( '#dsi-delete-result' ).hide().text( '' );
			$( '#dsi-delete-bar' ).css( 'width', '0%' );
			$( '#dsi-delete-status' ).text( dsiData.strings.deleting );
			$( '#dsi-delete-progress' ).show();
			$( this ).prop( 'disabled', true );

			deleteBatch( 0 );
		} );
	} );

} )( jQuery );
