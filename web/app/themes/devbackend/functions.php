<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', function () {
	define('Carbon_Fields\URL', home_url('/vendor/htmlburger/carbon-fields'));
	\Carbon_Fields\Carbon_Fields::boot();
});

// This is an example of how to create a new field.
// See more in the documentation: https://docs.carbonfields.net/
add_action('carbon_fields_register_fields', function () {
	Container::make('theme_options', __('Theme Options'))
		->add_fields([
			Field::make('text', 'crb_text', 'Text Field'),
		]);
});

/*
**						# Auto activation of plugins and theme after installation #
**
**			This configuration will auto activate the theme and plugins
**				* ABSPATH allows for pointing to wordpress root directory
**				without the need to change its files directly;
**				* With the previous step, it is possible to activate the
**				plugin desired;
**				* We then change to the desired theme with the switch_theme
**				function.
*/

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
//add_action( 'init', activate_plugin( 'wp-graphql/wp-graphql.php') );
$plugin_filepath = 'wp-graphql/wp-graphql.php';
$plugin_dir = WP_PLUGIN_DIR . "/{$plugin_filepath}";
if (file_exists($plugin_dir) && !is_plugin_active($plugin_filepath))
  activate_plugin($plugin_filepath);

activate_my_theme('devbackend');

function activate_my_theme($theme_name) {
  if ($theme_name!=get_current_theme()) {
    $theme = get_theme($theme_name);
    switch_theme(
      $theme['Template'],
      $theme['Stylesheet']
    );
  }
}

/*
**						# Custom Post Type creation #
*/

add_action( 'init', function() {
	$labels = array(
		'name'              => _x( 'Products', 'devbackend' ),
		'singular_name'     => _x( 'Product', 'devbackend' ),
		'menu_name'         => __( 'Products' ),
		'parent_item_colon' => __( 'Parent Product' ),
		'all_items'         => __( 'All Products' ),
		'view_item'         => __( 'View Product' ),
		'add_new_item'      => __( 'Add New Product' ),
		'add_new'           => __( 'Add New Product' ),
		'edit_item'         => __( 'Edit Product' ),
		'update_item'       => __( 'Update Product' ),
		'search_items'      => __( 'Search Product' ),
	);

	$args = array(
		'label'             => __( 'Products' ),
		'labels'            => $labels,
		'description'       => __( 'Holds your products and product-specific data' ),
		'supports'          => array( 'title', 'editor', 'excerpt', 'author',
			'thumbnail', 'comments', 'revisions', 'custom-fields', 'page-attributes',
			'post-formats' ),
		'public'            => true,
		'menu_position'     => 5,
		'has_archive'       => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'product' ),
	);
	register_post_type( 'product', $args );
} );

/*
**						# GraphQL Custom Post Type creation #
*/

add_action( 'init', function() {
	register_post_type( 'product', array(
		'show_ui'             => true,
		'labels'              => [
			'menu_name'         => __( 'Products', 'http://localhost:8080' ),
		],
		'show_in_graphql'     => true,
		'hierarchical'        => true,
		'graphql_single_name' => 'product',
		'graphql_plural_name' => 'products',
	 ) );
} );

/*
**						# Creation of custom field image and associations #
*/

add_action('carbon_fields_register_fields', function() {
	Container::make( 'post_meta', 'Custom Data' )
	->where( 'post_type', '=', 'product' )
	->add_fields( array(
		Field::make( 'image', 'crb_image', __( 'Image' )  ),
		Field::make( 'association', 'crb_association_product_post', __( 'Association' ) )
			->set_types( array(
				array(
					'type'      => 'post',
					'post_type' => 'post',
				),
			)),
	));
Container::make( 'post_meta', 'Custom Data' )
	->where( 'post_type', '=', 'post' )
	->add_fields( array(
		Field::make( 'association', 'crb_association_post_product', __( 'Association' ) )
		->set_types( array(
			array(
				'type'      => 'post',
				'post_type' => 'product',
			),
		))->set_max( 1 )
	));
});

/*
**						# Create Fields in other Custom Types #
**
**			This add_action hook will comprise the creation of the following:
**				* Field image in Product Custom Type
**				* Field Posts in Product Custom Type
**				* Field Product in Posts Type
*/

add_action( 'graphql_register_types', function() {
	register_graphql_connection([
		'fromType'           => 'product',
		'toType'             => 'MediaItem',
		'fromFieldName'      => 'image',
		'connectionTypeName' => 'ProductImageConnection',
		'connectionArgs'     => \WPGraphQL\Connection\PostObjects::get_connection_args(),
		'resolve'            => function( \WPGraphQL\Model\Post $source, $args, $context, $info ) {
			$resolver          = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );
			$resolver->set_query_arg( 'post__in', array( get_post_meta( $source->ID, '_crb_image', true )) );
			$connection = $resolver->get_connection();
			return $connection;
		},
	]);

	register_graphql_connection([
		'fromType'           => 'product',
		'toType'             => 'Post',
		'fromFieldName'      => 'posts',
		'connectionTypeName' => 'ProductPostsConnection',
		'connectionArgs'     => \WPGraphQL\Connection\PostObjects::get_connection_args(),
		'resolve'            => function( \WPGraphQL\Model\Post $source, $args, $context, $info ) {
			$resolver          = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'post' );

			$associated_posts  = new WP_Query( array(
				'post_type'      => 'product',
				'meta_query'     => array(
					array(
						'key' => '_crb_association_post_product',
						'compare' => 'EXISTS',
					),
				),
			) );

			if ( empty( $associated_posts ) ) {
				return null;
			 }

			$resolver->set_query_arg( 'post_in', $associated_posts );
			$connection = $resolver->get_connection();
			return $connection;
		},
	]);

}, 99 );
