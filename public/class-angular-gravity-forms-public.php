<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/Planview
 * @since      0.0.0
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/public
 * @author     Steve Crockett <crockett95@gmail.com>
 */
class Angular_Gravity_Forms_Public {

    /**
     * The ID of this plugin.
     *
     * @since   0.0.0
     * @access  private
     * @var     string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since   0.0.0
     * @access  private
     * @var     string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The Gravity Forms config we're using
     *
     * @since   0.0.0
     * @access  private
     * @var     array   $form  Settings from Gravity Forms
     */
    private $form;

    /**
     * Initialize the class and set its properties.
     *
     * @since   0.0.0
     * @param   string    $plugin_name       The name of the plugin.
     * @param   string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Process the `ng-gravityform` shortcode
     *
     * @since   0.0.0
     * @param   array   $attributes     The array of attributes passed into the shortcode
     * @param   string  $content        The content between the shortcodes
     * @return  string  The processed shortcode content
     */
    public function shortcode_ng_gravityforms( $attributes ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ng-gravityforms-shortcode.php';

        return (string) new Angular_Gravity_Forms_Shortcode( $attributes, $this->plugin_name, $this->version );
    }

    /**
     * Process a public form submission
     *
     * @since   0.0.0
     */
    public function submit_form_public() {
        if ( ! $this->check_array_property( 'requireLogin', $this->form ) ) {
            $this->send_json_response( array('success' => false ), '401 Unauthorized' );
            return;
        }

        $this->submit_form();
    }

    /**
     * Process an ajax form submission
     *
     * @since   0.0.0
     */
    public function submit_form() {
        $data = $this->prepare_form_data();
        $lead_id = GFAPI::add_entry( $data );
        $lead = RGFormsModel::get_lead( $lead_id );

        GFCommon::send_form_submission_notifications($this->form, $lead);
        $this->send_json_response( $lead );
    }

    /**
     * Get the form data to use in processing the form
     *
     * @since   0.0.0
     */
    public function ajax_setup() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) return;
        if ( ! class_exists( 'GFAPI' ) ) throw new Exception("Gravity Forms is not loaded", 1);
        if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== "{$this->plugin_name}_submit") return;

        $form_id = intval( $_REQUEST['form'] );

        $this->form = GFAPI::get_form( $form_id );
    }

    /**
     * Prepare the form data
     *
     * @since   0.0.0
     * @return  array   Sanitized form data
     */
    protected function prepare_form_data() {
        $cleaned_fields = array();

        foreach ( $this->form['fields'] as $field ) {
            if ( 'checkbox' === $field['type'] ) {
                $inputs = $field['inputs'];
                $choices = $field['choices'];
                for ( $i=0; $i < count( $inputs ); $i+= 1 ) {
                    $id = sprintf( '%.01F', $inputs[ $i ]['id'] );
                    $request_id = 'input_' . str_replace( '.', '_', $id );
                    if ( $this->check_array_property( $request_id, $_REQUEST ) ) {
                        $cleaned_fields[ $id ] = $choices[ $i ]['value'];
                    }
                }
            } else {
                $clean = $this->clean_field( $field );
                if ( $clean ) {
                    $cleaned_fields[ $field['id'] ] = $clean;
                } elseif ( $this->check_array_property( 'isRequired', $field ) ) {
                    return $this->send_json_response(
                        array( 'success' => false ),
                        '400 Bad Request'
                    );
                }
            }
        }

        $cleaned_fields['form_id'] = $this->form['id'];
        $cleaned_fields['date_created'] = date('Y-m-d H:i');

        return $cleaned_fields;
    }

    /**
     * Sanitize the form fields
     *
     * @since   0.0.0
     * @param   array   $field  The meta data for the field
     * @return  string|null     The sanitized data for the field
     */
    protected function clean_field( $field ) {
        if ( ! in_array( $field['type'], array(
            'text', 'textarea', 'hidden', 'email', 'number', 'select',
            'multiselect', 'radio', 'checkbox' ) ) ) return null;

        $id = "input_{$field['id']}";

        if ( ! isset( $_REQUEST[ $id ] ) ) return null;

        $value = $_REQUEST[ $id ];

        switch ( $field['type'] ) {
            case 'email':
                return sanitize_email( $value );

            case 'number':
                if ( ! preg_match('/[,\.0-9]+/', $value) ) return null;
                return $value;

            case 'select':
            case 'radio':
                foreach ( $field['choices'] as $choice ) {
                    if ( $value === $choice['value'] ) return $value;
                }
                return null;

            case 'multiselect':
                $matched = array();

                foreach ( $field['choices'] as $choice ) {
                    if ( in_array( $choice['value'], $value ) ) $matched[] = $choice['value'];
                }

                if ( ! empty( $matched ) ) return implode( ', ', $matched );
                return null;

            case 'textarea':
                return $value ?: null;

            case 'text':
            case 'hidden':
            default:
                return sanitize_text_field( $value );
                break;

        }
    }

    /**
     * Return a boolean value from a string
     *
     * @since   0.0.0
     * @param   string|mixed    $test   The value to get a boolean from
     * @return  boolean         The value after the test
     */
    private function get_boolean( $test ) {
        return filter_var($test, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Return whether an array property is set and it's boolean
     *
     * @since   0.0.0
     * @param   string|mixed    $test   The value to get a boolean from
     * @return  boolean         The value after the test
     */
    private function check_array_property( $property, $array, $cast = true ) {
        if ( ! isset( $array[ $property ] ) ) return false;

        if ( $cast && 'boolean' !== gettype( $array[ $property ] ) ) {
            return $this->get_boolean( $array[ $property ] );
        }

        return !!$array[ $property ];
    }

    /**
     * Sends a JSON encoded response with a status, if applicable
     *
     * @since   0.0.0
     * @param   mixed   $message    The message to be encoded and sent
     * @param   string  $status     Optional. The status to be used in the response
     */
    private function send_json_response( $message, $status = '200 OK' ) {
        header("Status: $status");
        header('Content-Type: application/json');
        echo json_encode($message);
        die();
    }

}
