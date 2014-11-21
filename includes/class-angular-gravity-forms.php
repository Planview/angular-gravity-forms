<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://github.com/Planview
 * @since      0.0.0
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.0
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/includes
 * @author     Steve Crockett <crockett95@gmail.com>
 */
class Angular_Gravity_Forms {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    0.0.0
     * @access   protected
     * @var      Angular_Gravity_Forms_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    0.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    0.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the Dashboard and
     * the public-facing side of the site.
     *
     * @since    0.0.0
     */
    public function __construct() {

        $this->plugin_name = 'angular-gravity-forms';
        $this->version = '0.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Angular_Gravity_Forms_Loader. Orchestrates the hooks of the plugin.
     * - Angular_Gravity_Forms_i18n. Defines internationalization functionality.
     * - Angular_Gravity_Forms_Admin. Defines all hooks for the dashboard.
     * - Angular_Gravity_Forms_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    0.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-angular-gravity-forms-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-angular-gravity-forms-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the Dashboard.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-angular-gravity-forms-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-angular-gravity-forms-public.php';

        $this->loader = new Angular_Gravity_Forms_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Angular_Gravity_Forms_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    0.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Angular_Gravity_Forms_i18n();
        $plugin_i18n->set_domain( $this->get_plugin_name() );

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the dashboard functionality
     * of the plugin.
     *
     * @since    0.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Angular_Gravity_Forms_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    0.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Angular_Gravity_Forms_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_shortcode( 'ng-gravityform', $plugin_public, 'shortcode_ng_gravityforms' );
        $this->loader->add_shortcode( 'ng-gravityforms', $plugin_public, 'shortcode_ng_gravityforms' );
        $this->loader->add_action( "wp_ajax_{$this->plugin_name}_submit", $plugin_public, 'submit_form' );
        $this->loader->add_action( "wp_ajax_nopriv_{$this->plugin_name}_submit", $plugin_public, 'submit_form_public' );
        $this->loader->add_action( 'plugins_loaded', $plugin_public, 'ajax_setup' );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    0.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     0.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     0.0.0
     * @return    Angular_Gravity_Forms_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     0.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
