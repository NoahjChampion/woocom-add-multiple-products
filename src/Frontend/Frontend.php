<?php # -*- coding: utf-8 -*-

namespace TheDramatist\WooComAddMultipleProducts\Frontend;

class Frontend {

	public function __construct() {

	}

	public function init() {
		// Action Hooks.
		add_action( 'woocommerce_after_cart', [ $this, 'input_from' ] );
		add_action( 'woocommerce_cart_is_empty', [ $this, 'input_from' ] );
		// Ajax product adding action hooks.
		add_action( 'wp_ajax_woocom_amp_add_to_cart', $this, 'ajax_cart' );
		add_action( 'wp_ajax_nopriv_woocom_amp_add_to_cart', $this, 'ajax_cart' );
		// Shortcode for adding products input to different places
		add_shortcode( 'wamp_product_input', [ $this, 'input_form_shortcode' ] );
	}

	// Product input form
	public function input_from() {
		if ( get_option( 'woocom_amp_user_check' ) == 1 && is_user_logged_in() ) {
			$user_role = get_option( 'woocom_amp_user_role' );
			$cu_roles  = $this->get_user_role( get_current_user_id() );
			$is_auth   = array_intersect( $user_role, $cu_roles ) ? 'true'
				: 'false';
			if ( ! empty( $user_role ) ) {
				if ( $is_auth ) {
					include 'Views/html-input-field.php';
				}
			} else {
				include 'Views/html-input-field.php';
			}
		} else {
			include 'Views/html-input-field.php';
		}
	}

	// Product input form
	public function input_form_shortcode( $atts ) {
		if ( get_option( 'woocom_amp_user_check' ) == 1 && is_user_logged_in() ) {
			$user_role = (array) get_option( 'woocom_amp_user_role' );
			$cu_roles  = $this->get_user_role( get_current_user_id() );
			$is_auth   = array_intersect( $user_role, $cu_roles ) ? 'true'
				: 'false';
			if ( ! empty( $user_role ) ) {
				if ( $is_auth ) {
					include 'Views/html-sc-input-field.php';
				}
			} else {
				include 'Views/html-sc-input-field.php';
			}
		} else {
			include 'Views/html-sc-input-field.php';
		}
	}

	// Ajax function
	public function ajax_cart() {
		global $woocommerce;
		// Getting and sanitizing $_POST data.
		$product_ids = filter_var_array( $_POST[ 'ids' ], FILTER_SANITIZE_SPECIAL_CHARS );
		foreach ( $product_ids as $product_id ) {
			$woocommerce->cart->add_to_cart( $product_id );
		}
		wp_die();
	}

	// Get products on list.
	public function get_products() {
		// Get category settings
		$product_cat_setting = (array) get_option( 'woocom_amp_product_cat' );
		if ( in_array( '-1', $product_cat_setting ) ) {
			// WP_Query arg for "Product" post type.
			$args = [
				'post_type'      => 'product',
				'fields'         => 'ids',
				'posts_per_page' => '-1',
			];
		} else {
			// WP_Query arg for "Product" post type.
			$args = [
				'post_type'      => 'product',
				'tax_query'      => [
					[
						'taxonomy' => 'product_cat',
						'field'    => 'id',
						'terms'    => $product_cat_setting,
					],
				],
				'fields'         => 'ids',
				'posts_per_page' => '-1',
			];
		}

		$this->wp_query( $args );
	}

	// Get products on list for dynamic shortcode.
	public function get_shortcode_products( $prod_cat_atts = [] ) {
		$prod_cats = explode( ',', $prod_cat_atts['prod_cat'] );
		// WP_Query arg for "Product" post type.
		$args = [
			'post_type'      => 'product',
			'tax_query'      => [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'id', //can be set to ID
					'terms'    => $prod_cats, //if field is ID you can reference by cat/term number
				],
			],
			'fields'         => 'ids',
			'posts_per_page' => '-1',
		];

		$this->wp_query( $args );
	}

	public function wp_query( $args = [] ) {
		// New Query
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			$rds = $loop->get_posts();
			// Loop Start.
			foreach ( $rds as $rd ) {
				$product   = new WC_Products( $rd );
				$sku       = $product->get_sku();
				$stock     = $product->is_in_stock() ? __( ' -- In stock', 'sodathemes' )
					: __( ' -- Out of stock', 'sodathemes' );
				$disablity = $product->is_in_stock() ? '' : 'disabled';
				echo '<option datad="'
					. esc_attr( $sku ) . '" value="'
					. esc_attr( $rd ) . '"'
					. esc_attr( $disablity ) . '>'
					. esc_attr( $sku ) . ' -- '
					. esc_attr( get_the_title( $rd ) )
					. esc_attr( $stock )
					. '</option>';
			} // Loop End .
		}
		wp_reset_postdata();
	}

	public function get_user_role( $user_id ) {
		if ( is_user_logged_in() ) {
			$user = new WP_User( $user_id );
			if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
				return $user->roles;
			}
		}
	}
}