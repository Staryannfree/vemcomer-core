<?php
namespace VemComer\Core\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class CPT_Product {
	public static function register(): void {
		\add_action( 'init', [ __CLASS__, 'register_post_type' ] );
	}

	public static function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Products', 'Post Type General Name', 'vemcomer-core' ),
			'singular_name'      => _x( 'Product', 'Post Type Singular Name', 'vemcomer-core' ),
			'menu_name'          => __( 'Products', 'vemcomer-core' ),
			'add_new'            => __( 'Add New', 'vemcomer-core' ),
			'add_new_item'       => __( 'Add New Product', 'vemcomer-core' ),
			'edit_item'          => __( 'Edit Product', 'vemcomer-core' ),
			'new_item'           => __( 'New Product', 'vemcomer-core' ),
			'view_item'          => __( 'View Product', 'vemcomer-core' ),
			'search_items'       => __( 'Search Products', 'vemcomer-core' ),
			'not_found'          => __( 'No products found.', 'vemcomer-core' ),
			'not_found_in_trash' => __( 'No products found in Trash.', 'vemcomer-core' ),
		];

		$args = [
			'label'               => __( 'Products', 'vemcomer-core' ),
			'labels'              => $labels,
			'public'              => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
			'has_archive'         => true,
			'rewrite'             => [ 'slug' => 'products' ],
			'menu_icon'           => 'dashicons-cart',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		];

		\register_post_type( 'product', $args );
	}
}
