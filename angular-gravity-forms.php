<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Planview
 * @since             0.0.0
 * @package           Angular_Gravity_Forms
 *
 * @wordpress-plugin
 * Plugin Name:       Angular.js for Gravity Forms
 * Plugin URI:        https://github.com/Planview/angular-gravity-forms/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress dashboard.
 * Version:           0.0.0
 * Author:            Steve Crockett
 * Author URI:        https://github.com/Planview/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       angular-gravity-forms
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-angular-gravity-forms-activator.php';

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-angular-gravity-forms-deactivator.php';

/** This action is documented in includes/class-angular-gravity-forms-activator.php */
register_activation_hook( __FILE__, array( 'Angular_Gravity_Forms_Activator', 'activate' ) );

/** This action is documented in includes/class-angular-gravity-forms-deactivator.php */
register_deactivation_hook( __FILE__, array( 'Angular_Gravity_Forms_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-angular-gravity-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.0
 */
function run_angular_gravity_forms() {

    $plugin = new Angular_Gravity_Forms();
    $plugin->run();

}
run_angular_gravity_forms();
