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

include plugin_dir_path( __FILE__ ) . 'url-to-query.php';

class Headless_REST_Controller extends WP_REST_Posts_Controller
{
    public function __construct () {
        $this->namespace = '/headless/v1';
        $this->types = [
            'author',
            'category',
            'page',
            'single',
            'tag',
            'tax'
        ];
    }

    public function register_routes () {
        register_rest_route( $this->namespace, '/url_to_query/(?P<url>\S+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'url_to_query' )
        ));

        register_rest_route( $this->namespace, '/home_page', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_home_page' )
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
    public function url_to_query ( $request ) {
        $url = esc_sql($request['url']);
        $posts = new WP_Query( url_to_query( $url ) );

        if ( !$posts->have_posts() ) {
            return new WP_Error(
                'no_post',
                'Invalid url',
                array( 'status' => 404 )
            );
        }

        $data = array();

        foreach ( $posts->posts as $post ) {
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }

        $reponse = new stdClass();

        foreach ($this->types as $type) {
            if ( $posts->{'is_' . $type} ) {
                $reponse->type = $type;
            }
        }

        $reponse->posts = $data;

        return $reponse;
    }

    /**
    * Get front page content
    *
    * @param WP_REST_Request $request Current request.
    * @return string Content type
    */
    public function get_home_page ( $request ) {
        $posts;

        if (get_option('page_on_front') > 0) {
            $posts = new WP_Query( array( 'page_id' => get_option('page_on_front') ) );
        }
        else {
            $posts = new WP_Query( array( 'post_type' => 'post' ) );
        }

        if ( !$posts->have_posts() ) {
            return new WP_Error(
                'no_post',
                'Invalid url',
                array( 'status' => 404 )
            );
        }

        $data = array();

        foreach ( $posts->posts as $post ) {
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }

        $reponse = new stdClass();

        $reponse->type = 'home';
        $reponse->posts = $data;

        return $reponse;
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

	/**
	 * Prepares a single post output for response.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$GLOBALS['post'] = $post;

		setup_postdata( $post );

		// Base fields for every post.
		$data = array();

        $data['id'] = $post->ID;
        $data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );


        $data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );

        $data['slug'] = $post->post_name;
        $data['status'] = $post->post_status;
        $data['type'] = $post->post_type;
        $data['link'] = get_permalink( $post->ID );
        $data['title'] = get_the_title( $post->ID );
        $data['content'] = apply_filters( 'the_content', $post->post_content );
        $data['excerpt'] = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
        $data['author'] = (int) $post->post_author;
        $data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
        $data['parent'] = (int) $post->post_parent;
        $data['comment_status'] = $post->comment_status;
        $data['sticky'] = is_sticky( $post->ID );
        if ( $template = get_page_template_slug( $post->ID ) ) {
            $data['template'] = $template;
        } else {
            $data['template'] = '';
        }

        $data['format'] = get_post_format( $post->ID );

        // Fill in blank post format.
        if ( empty( $data['format'] ) ) {
            $data['format'] = 'standard';
        }

		$taxonomies = wp_list_filter(
            get_object_taxonomies( $this->post_type, 'objects' ),
            array( 'show_in_rest' => true )
        );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
            $terms = get_the_terms( $post, $taxonomy->name );
            $data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'term_id' ) ) : array();
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $post ) );

		/**
		 * Filters the post data for a response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     Post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );
	}
}

function headless_register_rest_routes () {
    $controller = new Headless_REST_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'headless_register_rest_routes' );
