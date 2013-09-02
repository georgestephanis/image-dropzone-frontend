jQuery( function ( $ ) {

	/**
	 * Enable front-end uploading of images.
	 */
	var Image_Dropzone_Frontend = {
		init : function () {
			$( document ).on( 'dragover.image-dropzone-frontend',  'body, #image-dropzone-frontend-drop-zone', this.onDragOver );
			$( document ).on( 'dragleave.image-dropzone-frontend', 'body, #image-dropzone-frontend-drop-zone', this.onDragLeave );
			$( document ).on( 'drop.image-dropzone-frontend',      'body, #image-dropzone-frontend-drop-zone', this.onDrop );

			$( 'body' ).append( $( '<div id="image-dropzone-frontend-drop-zone"><p class="dragging" /><p class="uploading" /></div>' ) );
			$( '#image-dropzone-frontend-drop-zone' )
				.find( '.dragging' )
					.text( Image_Dropzone_Frontend_Options.labels.dragging )
				.end()
				.find( '.uploading' )
					.text( Image_Dropzone_Frontend_Options.labels.uploading );

			if ( ! ( 'FileReader' in window && 'File' in window ) ) {
				$( '#image-dropzone-frontend-drop-zone .dragging' ).text( Image_Dropzone_Frontend_Options.labels.unsupported );
				$( document ).off( 'drop.image-dropzone-frontend' ).on( 'drop.image-dropzone-frontend', 'body, #image-dropzone-frontend-drop-zone', this.onDragLeave );
			}
		},

		/**
	 	 * Only upload image files.
		 */
		filterImageFiles : function ( files ) {
			var validFiles = [];

			for ( var i = 0, _len = files.length; i < _len; i++ ) {
				if ( files[i].type.match( /^image\//i ) ) {
					validFiles.push( files[i] );
				}
			}

			return validFiles;
		},

		dragTimeout : null,

		onDragOver: function ( event ) {
			event.preventDefault();

			clearTimeout( Image_Dropzone_Frontend.dragTimeout );

			$( 'body' ).addClass( 'dragging' );
		},

		onDragLeave: function ( event ) {
			clearTimeout( Image_Dropzone_Frontend.dragTimeout );

			// In Chrome, the screen flickers because we're moving the drop zone in front of 'body'
			// so the dragover/dragleave events happen frequently.
			Image_Dropzone_Frontend.dragTimeout = setTimeout( function () {
				$( 'body' ).removeClass( 'dragging' );
			}, 100 );
		},

		onDrop: function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			// recent chrome bug requires this, see stackoverflow thread: http://bit.ly/13BU7b5
			event.originalEvent.stopPropagation();
			event.originalEvent.preventDefault();

			var files = Image_Dropzone_Frontend.filterImageFiles( event.originalEvent.dataTransfer.files );

			$( 'body' ).removeClass( 'dragging' );

			if ( files.length == 0 ) {
				alert( Image_Dropzone_Frontend_Options.labels.invalidUpload );
				return;
			}

			$( 'body' ).addClass( 'uploading' );

			var formData = new FormData();

			for ( var i = 0, fl = files.length; i < fl; i++ ) {
				formData.append( 'image_' + i, files[ i ] ); // won't work as image[]
			}

			$.ajax( {
				url:         Image_Dropzone_Frontend_Options.ajaxurl + '&nonce=' + Image_Dropzone_Frontend_Options.nonce,
				data:        formData,
				processData: false,
				contentType: false,
				type:        'POST',
				dataType:    'json',
				xhrFields:   { withCredentials: true }
			} )
			.done( function( data ) {
				$( '#image-dropzone-frontend-drop-zone .uploading' ).text( Image_Dropzone_Frontend_Options.labels.processing );

				if ( 'url' in data ) {
					document.location.href = data.url;
				} else if ( 'error' in data ) {
					alert( data.error );

					$( 'body' ).removeClass( 'uploading' );
				}
			} )
			.fail( function ( req ) {
				alert( Image_Dropzone_Frontend_Options.labels.error );
			} );
		}
	};

	Image_Dropzone_Frontend.init();
} );