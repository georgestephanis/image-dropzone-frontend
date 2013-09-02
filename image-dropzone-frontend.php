<?php

/**
 * Plugin Name: Image Dropzone Frontend
 * Plugin URI:  http://github.com/georgestephanis/image-dropzone-frontend
 * Description: Drop images onto the frontend of your site, and watch them turn into posts!
 * Author:      George Stephanis
 * Version:     0.9
 * Author URI:  http://stephanis.info
 * License:     GPL2+
 */

class Image_Dropzone_Frontend {

	static function go() {
		add_action( 'init',           array( __CLASS__, 'check_and_enqueue' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_plugin_textdomain' ) );
	}

	static function check_and_enqueue() {
		if ( current_user_can( 'upload_files' ) && current_user_can( 'publish_posts' ) ) {
			add_action( 'wp_ajax_image_dropzone_frontend', array( __CLASS__, 'wp_ajax_image_dropzone_frontend' ) );
			add_action( 'wp_enqueue_scripts',              array( __CLASS__, 'wp_enqueue_scripts' ) );
		}
	}

	static function load_plugin_textdomain() {
		load_plugin_textdomain( 'image-dropzone-frontend' );
	}

	static function wp_ajax_image_dropzone_frontend() {
		global $content_width;

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'image-dropzone-frontend_nonce' ) ) {
			wp_send_json( array( 'error' => __( 'Invalid or expired nonce.', 'image-dropzone-frontend' ) ) );
		}

		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'publish_posts' ) ) {
			wp_send_json( array( 'error' => _x( 'Are you the Keymaster? I am The Gatekeeper.', 'Ghostbusters Reference', 'image-dropzone-frontend' ) ) );
		}

		$_POST['action'] = 'wp_handle_upload';

		$image_id_arr    = array();
		$image_error_arr = array();
		$post_id_arr     = array();

		$i = 0;

		while ( isset( $_FILES['image_' . $i ] ) ) {

			// Create attachment for the image.
			$image_id = media_handle_upload( "image_$i", 0 );

			if ( is_wp_error( $image_id ) ) {
				$error = array( $image_id, $image_id->get_error_message() );
				array_push( $image_error_arr, $error );
			} else {
				array_push( $image_id_arr, $image_id );
			}

			$i++;

		}

		if ( $image_id_arr ) {

			foreach ( $image_id_arr as $image_id ) {
				$post = get_default_post_to_edit();

				$meta = wp_get_attachment_metadata( $image_id );
				$image_html = get_image_send_to_editor( $image_id, $meta['image_meta']['caption'], '', 'none', wp_get_attachment_url( $image_id ), '', 'full' );

				$post->post_title    = basename( get_attached_file( $image_id ) );
				$post->post_content  = $image_html;
				$post->post_category = array();

				$post_id = wp_insert_post( $post );

				wp_update_post( array(
					'ID'          => $image_id,
					'post_parent' => $post_id
				) );

				set_post_format( $post_id, 'image' );
				set_post_thumbnail( $post_id, $image_id );
				wp_publish_post( $post_id );

				array_push( $post_id_arr, $post_id );
			}

		}

		$data = array(
			'url'             => get_post_format_link( 'image' ),
			'image_id_arr'    => $image_id_arr,
			'image_error_arr' => $image_error_arr,
			'post_id_arr'     => $post_id_arr,
		);

		if ( $image_error_arr ) {
			$data['error'] = '';
			foreach ( $image_error_arr as $error ) {
				$data['error'] .= $error[1] . "\n";
			}
		}

		wp_send_json( $data );
	}

	static function wp_enqueue_scripts() {
		wp_enqueue_style( 'image-dropzone-frontend', plugins_url( 'image-dropzone-frontend.css', __FILE__ ) );
		wp_enqueue_script( 'image-dropzone-frontend', plugins_url( 'image-dropzone-frontend.js', __FILE__ ), array( 'jquery' ) );

		$options = array(
			'nonce'   => wp_create_nonce( 'image-dropzone-frontend_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php?action=image_dropzone_frontend' ),
			'labels'  => array(
				'dragging'      => __( 'Drop images to upload', 'image-dropzone-frontend' ),
				'uploading'     => __( 'Uploading…', 'image-dropzone-frontend' ),
				'processing'    => __( 'Processing…', 'image-dropzone-frontend' ),
				'unsupported'   => __( "Sorry, your browser isn't supported. Upgrade at browsehappy.com.", 'image-dropzone-frontend' ),
				'invalidUpload' => __( 'Only images can be uploaded here.', 'image-dropzone-frontend' ),
				'error'         => __( "Your upload didn't complete; try again later or cross your fingers and try again right now.", 'image-dropzone-frontend' ),
			)
		);

		wp_localize_script( 'image-dropzone-frontend', 'Image_Dropzone_Frontend_Options', $options );

	}

}
Image_Dropzone_Frontend::go();
