<?php
/*
Plugin Name: FacetWP - Elementor
Description: FacetWP and Elementor Integration
Version: 0.1.0
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-elementor
*/

defined( 'ABSPATH' ) or exit;

// setup constants.
define( 'FWEL_VER', '0.1.0' );

class FacetWP_El_Integration {

    private static $instance;
    private $elements;

    function __construct() {

        add_action( 'plugins_loaded', array( $this, 'setup_elementor' ) );
        add_filter( 'facetwp_is_main_query', array( $this, 'is_main_query' ), 10, 2 );

    }

    public static function init() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function setup_elementor() {

        $this->elements = apply_filters( 'facetwp_elementor_elements', array( 'posts', 'archive-posts' ) ); 

        add_action( 'elementor/element/after_section_end', array( $this, 'register_controls' ), 10, 3 );
        add_action( 'elementor/widget/before_render_content', array( $this, 'add_template_class' ) );

    }

    function register_controls( $element, $section_id, $args ) {
        
        if ( 'section_layout' === $section_id && in_array( $element->get_name(), $this->elements ) ) {

            $element->start_controls_section(
                'facetwp_section',
                [
                    'label' => __( 'FacetWP', 'plugin-name' ),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $element->add_control(
                'enable_facetwp',
                [
                    'label' => __( 'Enable FacetWP', 'fwp' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'no',
                    'options' => [
                        'no' => __( 'No', 'fwp' ),
                        'yes' => __( 'Yes', 'fwp' ),
                    ],
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Use the current query?
     */
    function is_main_query( $is_main_query, $query ) {

        if ( '' !== $query->get( 'facetwp' ) ) {
            $is_main_query = (bool) $query->get( 'facetwp' );
        }

        return $is_main_query;
    }

    /**
     * Add the FacetWP template CSS class if needed
     */
    function add_template_class( $widget ) {
        if ( in_array( $widget->get_name(), $this->elements ) ) {
            $settings = $widget->get_settings();
        
            if ( ! empty( $settings['enable_facetwp'] && 'yes' == $settings['enable_facetwp'] ) ) {

                $widget->add_render_attribute(
                    '_wrapper', 'class', [
                        'facetwp-template'
                    ]
                );

                if ( empty( $settings['posts_query_id'] ) ) {

                    $settings['posts_query_id'] = 'facetwp_query';
                    $widget->set_settings( $settings );

                }

                if ( 'posts' == $widget->get_name() ) {

                    add_action( "elementor_pro/posts/query/{$settings['posts_query_id']}", function( $query, $widget ) {
                        $query->set( 'facetwp', true );
                    }, 10, 2 );

                //} elseif ( 'archive-posts' == $widget->get_name() ) {

                    // don't think we need this hook
                    //add_action( 'elementor/theme/posts_archive/query_posts/query_vars', function( $query_vars ) {
                    //} );

                } else {

                    do_action( 'facetwp_elementor_query', $widget->get_name() );

                }

            }
        }
    }
}

FacetWP_El_Integration::init();