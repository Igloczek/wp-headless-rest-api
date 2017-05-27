<?php
/*
Plugin Name: Headless REST API
Version: 1.0.0
Description: Additional WP REST API endpoints, useful for building a headless Wordpress instances, for example Single Page Applications, Progressive Web Application or Mobile applications
Author: Bartek Igielski
Author URI: https://iglo.tech
License: GPLv3
Licence URI: https://www.gnu.org/licenses/gpl.html
*/

class Headless_REST_Controller
{
    public function __construct () {
        $this->namespace = '/headless/v1';
    }

    public function register_routes () {
        register_rest_route( $this->namespace, '/type-by-url/(?P<url>\S+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_type_by_url' )
        ));

        register_rest_route( $this->namespace, '/post-by-url/(?P<url>\S+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_post_by_url' )
        ));

        register_rest_route( $this->namespace, '/menu/(?P<id>[a-zA-Z(-]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_menu' )
        ));
    }

    /**
    * Get the post type by URL
    *
    * @param WP_REST_Request $request Current request.
    * @return string Content type
    */
    public function get_type_by_url ( $request ) {
        $type = get_post_type( url_to_postid( $request['url'] ) );

        if ( empty( $type ) ) {
            return new WP_Error(
                'no_post',
                'Invalid url to post',
                array( 'status' => 404 )
            );
        }

        return $type;
    }


    /**
    * Get the post object by URL
    *
    * @param WP_REST_Request $request Current request.
    * @return array Post object
    */
    public function get_post_by_url ( $request ) {
        $post = get_post( url_to_postid( $request['url'] ) );

        if ( empty( $post ) ) {
            return new WP_Error(
                'no_post',
                'Invalid url to post',
                array( 'status' => 404 )
            );
        }

        return $post;
    }

    /**
    * Get the menu by id|name|slug
    *
    * @param WP_REST_Request $request Current request.
    * @return array Menu object
    */
    public function get_menu ( $request ) {
        $menu = wp_get_nav_menu_items( $request['id'] );

        if ( empty( $menu ) ) {
            return new WP_Error(
                'no_menu',
                'Menu with that id|name|slug doesn\'t exist',
                array( 'status' => 404 )
            );
        }

        return $menu;
    }
}

function headless_register_rest_routes () {
    $controller = new Headless_REST_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'headless_register_rest_routes' );
