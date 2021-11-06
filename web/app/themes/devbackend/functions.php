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

add_action( 'init', 'create_post_type');

add_action( 'init', 'create_product_in_graphql');

add_action('carbon_fields_register_fields', 'create_image_and_relationship' );

add_action( 'graphql_register_types', 'register_graphql_product_image_connection', 99 );

function  create_post_type() {

  $labels = array(
    'name'                => _x( 'Products', 'devbackend' ),
    'singular_name'       => _x( 'Product', 'devbackend' ),
    'menu_name'           => __( 'Products' ),
    'parent_item_colon'   => __( 'Parent Product' ),
    'all_items'           => __( 'All Products' ),
    'view_item'           => __( 'View Product' ),
    'add_new_item'        => __( 'Add New Product' ),
    'add_new'             => __( 'Add New Product' ),
    'edit_item'           => __( 'Edit Product' ),
    'update_item'         => __( 'Update Product' ),
    'search_items'        => __( 'Search Product' ),
  );

  $args = array(
    'label'               => __( 'Products' ),
    'labels'              => $labels,
    'description'         => __( 'Holds your products and product-specific data' ),
    'supports'            => array( 'title', 'editor', 'excerpt', 'author',
      'thumbnail', 'comments', 'revisions', 'custom-fields', 'page-attributes',
      'post-formats' ),
    'public'              => true,
    'menu_position'       => 3,
    'has_archive'         => true,
    'show_in_rest'        => true,
    // 'show_in_admin_bar'   => true,
    // 'show_in_nav_menus'   => true,
    'rewrite'             => array('slug' => 'product'),
    // 'capacility_type'     => 'post',
  );

  register_post_type( 'product', $args );
}

function create_product_in_graphql() {
  register_post_type( 'product', [
    'show_ui' => true,
    'labels'  => [
      'menu_name' => __( 'Products', 'http://localhost:8080' ),
    ],
    'show_in_graphql'     => true,
    'hierarchical'        => true,
    'graphql_single_name' => 'product',
    'graphql_plural_name' => 'products',
  ] );
};

function create_image_and_relationship() {
  Container::make( 'post_meta', 'Custom Data' )
    ->where( 'post_type', '=', 'product' )
    ->add_fields( array(
      Field::make( 'image', 'crb_image', __( 'Image' )  ),
      Field::make( 'association', 'crb_association', __( 'Association' ) )
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
      Field::make( 'association', 'crb_association', __( 'Association' ) )
        ->set_types( array(
          array(
            'type'      => 'post',
            'post_type' => 'product',
          ),
        )),
    ));
};

function register_graphql_product_image_connection() {

  $config = [
  'fromType'           => 'product',
  'toType'             => 'MediaItem',
  'fromFieldName'      => 'image',
  'connectionTypeName' => 'ProductImageConnection',
  'connectionArgs' => \WPGraphQL\Connection\PostObjects::get_connection_args(),
  'resolve'            => function( \WPGraphQL\Model\Post $source, $args, $context, $info ) {
    $resolver   = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );
    $resolver->set_query_arg( 'post__in', array( get_post_meta($source->ID, '_crb_image', true )) );
    $connection = $resolver->get_connection();

    return $connection;
    },
  ];

  register_graphql_connection( $config );

};
