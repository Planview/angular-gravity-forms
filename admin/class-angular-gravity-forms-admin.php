<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://github.com/Planview
 * @since      0.0.0
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/admin
 * @author     Steve Crockett <crockett95@gmail.com>
 */
class Angular_Gravity_Forms_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    0.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since   0.0.0
     * @access  private
     * @var     string  $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since   0.0.0
     * @param   string    $plugin_name       The name of this plugin.
     * @param   string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the Dashboard.
     *
     * @since   0.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Angular_Gravity_Forms_Admin_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Angular_Gravity_Forms_Admin_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/angular-gravity-forms-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the dashboard.
     *
     * @since   0.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Angular_Gravity_Forms_Admin_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Angular_Gravity_Forms_Admin_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/angular-gravity-forms-admin.js', array( 'jquery' ), $this->version, false );

    }

}
