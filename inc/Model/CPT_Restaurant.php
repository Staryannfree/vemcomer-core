<?php
namespace VemComer\Core\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class CPT_Restaurant {
	public static function register(): void {
		\add_action( 'init', [ __CLASS__, 'register_post_type' ] );
	}

	public static function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Restaurants', 'Post Type General Name', 'vemcomer-core' ),
			'singular_name'         => _x( 'Restaurant', 'Post Type Singular Name', 'vemcomer-core' ),
			'menu_name'             => __( 'Restaurants', 'vemcomer-core' ),
			'name_admin_bar'        => __( 'Restaurant', 'vemcomer-core' ),
			'add_new'               => __( 'Add New', 'vemcomer-core' ),
			'add_new_item'          => __( 'Add New Restaurant', 'vemcomer-core' ),
			'edit_item'             => __( 'Edit Restaurant', 'vemcomer-core' ),
			'new_item'              => __( 'New Restaurant', 'vemcomer-core' ),
			'view_item'             => __( 'View Restaurant', 'vemcomer-core' ),
			'search_items'          => __( 'Search Restaurants', 'vemcomer-core' ),
			'not_found'             => __( 'No restaurants found.', 'vemcomer-core' ),
			'not_found_in_trash'    => __( 'No restaurants found in Trash.', 'vemcomer-core' ),
		];

		$args = [
			'label'               => __( 'Restaurants', 'vemcomer-core' ),
			'labels'              => $labels,
			'public'              => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			supports             => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
			'has_archive'         => true,
			rewrite              => [ 'slug' => 'restaurants' ],
			'menu_icon'           => 'dashicons-store',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		];

		\register_post_type( 'restaurant', $args );
	}
}
