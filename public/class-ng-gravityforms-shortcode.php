<?php
/**
 * The shortcode functionality of the plugin.
 *
 * @link       https://github.com/Planview
 * @since      0.0.0
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/public
 */

/**
 * The shortcode functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Angular_Gravity_Forms
 * @subpackage Angular_Gravity_Forms/public
 * @author     Steve Crockett <crockett95@gmail.com>
 */
class Angular_Gravity_Forms_Shortcode {

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
     * Count the number of forms showing
     *
     * @since   0.0.0
     * @access  public
     * @static
     * @var     int     $count      The number of times we've displayed a form
     */
    public static $count;

    /**
     * Keep track of this form
     *
     * @since   0.0.0
     * @access  public
     * @var     int     $id      The id of this form
     */
    public $id;

    /**
     * Keep the settings available
     *
     * @since   0.0.0
     * @access  private
     * @var     array   $settings   The settings for displaying the shortcode
     */
    private $settings;

    /**
     * Save the template
     *
     * @since   0.0.0
     * @access  private
     * @var     string  $template   The compiled template
     */
    private $template;

    /**
     * Keep track of the Gravity Forms form
     *
     * @since   0.0.0
     * @access  private
     * @var     array   $form       The Gravity Forms form that we're using
     */
    private $form;

    /**
     * Keep track of the fields after we transform them
     *
     * @since   0.0.0
     * @access  private
     * @var     array   $fields     The form fields after transformation
     */
    private $fields;

    /**
     * An array of the default field values passed in
     *
     * @since   0.0.0
     * @access  private
     * @var     array   $field_values   The form field defaults that were passed in
     */
    private $field_values;

    /**
     * Make a shortcode
     *
     * @since   0.0.0
     * @param   array   $attributes     The attributes passed in
     * @param   string  $content        The content passed in
     */
    public function __construct( $attributes, $plugin_name, $version ) {
        self::$count += 1;
        $this->id = self::$count;
        $this->version = $version;
        $this->plugin_name = $plugin_name;

        $this->set_config( $attributes );
        $this->set_form();
        $this->transform_fields();
        $this->compile_template();
        $this->add_scripts();
    }

    /**
     * Cast the object to a string for display
     *
     * @since   0.0.0
     * @return  string  The display string for the shortcode
     */
    public function __toString() {
        return $this->template;
    }

    /**
     * Enqueues the necessary scripts for the form to work
     *
     * @since   0.0.0
     */
    public function enqueue_scripts() {

        // First Register Angular.js
        wp_register_script( 'angular', plugin_dir_url( dirname( __FILE__ ) ) .
            'bower_components/angular/angular.min.js', array( 'jquery' ), '1.3.3', true );
        // Then register our Application
        wp_register_script( 'ng-gravityforms-app', plugin_dir_url( __FILE__ ) .
            'js/app.js', array( 'angular' ), $this->version, true );

        // Localize to pass some configuration
        wp_localize_script( 'ng-gravityforms-app', 'NG_GRAVITY_SETTINGS', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action' => "{$this->plugin_name}_submit",
            'form' => $this->form['id']
        ) );

        // Queue it up
        wp_enqueue_script( 'ng-gravityforms-app' );
    }

    /**
     * Checks if scripts have already been enqueued, and registers hook or enqueues them directly
     *
     * @since   0.0.0
     */
    private function add_scripts() {
        if ( ! did_action( 'wp_enqueue_scripts' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        } else {
            $this->enqueue_scripts();
        }
    }

    /**
     * Compile the template
     */
    private function compile_template() {
        $template = $this->get_config( 'template' );

        if ( file_exists( basename( __FILE__ ) . "/partials/$template.php") ) {
            $this->compile_from_file( $template );
        } else {
            $this->compile_from_file( 'default' );
        }
    }

    /**
     * Compile template from file
     */
    private function compile_from_file( $template_name ) {
        $compiled = '';
        $file_name = dirname( __FILE__ ) . "/partials/$template_name.php";

        ob_start();
        include $file_name;
        $compiled = ob_get_clean();

        $this->template = $compiled;
    }

    /**
     * Set up the settings array for processing the shortcode
     *
     * @since   0.0.0
     * @param   array   $attributes     The attributes passed into the shortcode
     */
    private function set_config( $attributes ) {
        // WP standard shortcode_atts
        $settings = shortcode_atts( array(
            'id' => 1,
            'name' => null,     // Added for compatibility with GF shortcode
            'title' => 'false',
            'description' => 'false',
            'template' => 'default',
            'field_values' => null
        ), $attributes );

        // Cast booleans away from being strings
        $settings['title'] = $this->get_boolean( $settings['title'] );
        $settings['description'] = $this->get_boolean( $settings['description'] );

        $this->settings = $settings;
    }

    /**
     * Convenience method to get a setting
     *
     * @since   0.0.0
     * @param   string  $option     The setting to be retrieved
     * @return  mixed   The value of the option
     */
    private function get_config( $option ) {
        return $this->settings[ $option ];

        if ( ! empty( $this->settings['field_values'] ) ) {
            $result = array();
            $intermediate = explode( '&', $this->settings['field_values'] );

            foreach ($intermediate as $pair) {
                list($k, $v) = explode( '=', $pair );
                $result[ $k ] = $v;
            }
        }
    }

    /**
     * Get the form configuration and set it to the object's property
     *
     * @since 0.0.0
     */
    private function set_form() {
        if ( ! class_exists('GFAPI') ) {
            throw new Exception("Gravity Forms Must be installed", 1);
        }

        $this->form = GFAPI::get_form( $this->get_config('id') );
    }

    /**
     * Return the default confirmation
     *
     * @since   0.0.0
     * @return  string  The default confirmation text
     * @todo    Handle Page and Redirect confirmations
     * @todo    Handle multiple (conditional) confirmations
     * @todo    Handle JS in the confirmation
     */
    private function get_confirmation() {
        $confirmations = $this->form['confirmations'];

        $confirmation = reset( $confirmations );

        foreach ( $confirmations as $data ) {
            if ( $data['isDefault'] ) {
                $confirmation = $data;
            }
        }

        if ( 'message' !== $confirmation['type'] ) {
            return __(
                'Thanks for contacting us! We will get in touch with you shortly.',
                'angular-gravity-forms'
            );
        }

        $message = $confirmation['message'];

        if ( ! $this->check_array_property( 'disableAutoFormatting', $confirmation ) ) {
            $message = wpautop( $message );
        }

        return $message;
    }

    /**
     * Transform the fields into something we can use
     *
     * @since   0.0.0
     * @todo    Handle compound inputs
     */
    private function transform_fields() {
        $fields = $this->form['fields'];
        $transformed = array();

        foreach ( $fields as $field ) {
            $type = $field['type'];

            $template = '';
            $additional = '';

            switch ( $type ) {
                case 'number':
                    $type = 'text';
                case 'text':
                case 'email':
                case 'hidden':
                    if ( $this->check_array_property( 'enablePasswordInput', $field ) ) {
                        $type = 'password';
                    }

                    $template = sprintf(
                        '<input type="%s" %s%s />',
                        esc_attr( $type ),
                        $this->get_field_attributes( $field ),
                        $this->get_field_value( $field )
                    );

                    break;

                case 'textarea':
                    $template = sprintf(
                        '<textarea %s>%s</textarea>',
                        $this->get_field_attributes( $field ),
                        $this->get_field_value( $field, false )
                    );
                    break;

                case 'multiselect':
                    $additional = ' multiple';
                case 'select':
                    $template = sprintf(
                        '<select %s%s%s>%s</select>',
                        $this->get_field_attributes( $field ),
                        $this->get_field_value( $field ),
                        $additional,
                        $this->get_select_options( $field )
                    );
                    break;

                case 'html':
                    $template = $field['content'];
                    break;

                case 'section':
                    $template = "<h4>{$field['label']}</h4><hr />";
                    break;

                case 'radio':
                    $template = array();

                    foreach ( $field['choices'] as $choice ) {
                        $template[] = sprintf(
                            '<label><input type="radio" value="%s" %s%s /> %s</label>',
                            esc_attr( $choice['value'] ),
                            $choice['isSelected'] ? 'checked ' : '',
                            $this->get_field_attributes( $field, false ),
                            $choice['text']
                        );
                    }
                    break;

                case 'checkbox':
                    $template = array();
                    $num_choices = count( $field['choices'] );
                    $choices = $field['choices'];
                    $inputs = $field['inputs'];
                    $class = '';

                    if ( $this->check_array_property( 'cssClass', $field, false ) ) {
                        $class .= ' class="' . $field['cssClass'] . ' %s" ';
                    } else {
                        $class .= ' class="%s" ';
                    }

                    for ( $i = 0; $i < $num_choices; $i += 1 ) {
                        $id = sprintf('input_%s_%.01F', $this->id, $inputs[ $i ]['id'] );
                        $id = str_replace('.', '_', $id);
                        $template[] = sprintf(
                            '<label><input type="checkbox" value="%s" id="%s"' .
                                ' data-ng-model="formData[\'%.01F\']" name="%s"%s%s /> %s</label>',
                            esc_attr( $choices[ $i ]['value'] ),
                            $id,
                            $inputs[ $i ]['id'],
                            $id,
                            $class,
                            $choices[ $i ]['isSelected'] ? ' checked' : '',
                            $choices[ $i ]['text']
                        );
                    }
                    $id = null;
            }

            $transformed_field = array(
                'type' => $field['type'],
                'label' => $field['label'],
                'template' => $template,
                'id' => $this->get_field_id( $field )
            );

            $transformed_field['description'] = isset( $field['description'] ) ?
                $field['description'] : '';

            if ( ! $this->check_array_property( 'adminOnly', $field ) ) {
                $transformed[] = $transformed_field;
            }
        }

        $this->fields = $transformed;
    }

    /**
     * Get the attributes for a field in the form
     *
     * @since   0.0.0
     * @param   array   $field      The array of the field's data
     * @param   boolean $with_id    Whether to include the id attribute
     * @return  string  The HTML attributes to be added to the field's markup
     */
    private function get_field_attributes( $field, $with_id = true ) {
        $attributes = '';
        $attributes .= "data-ng-model=\"formData['{$field['id']}']\" ";
        $attributes .= "name=\"{$this->get_field_id( $field )}\" ";

        if ( $with_id ) {
            $attributes .= "id=\"{$this->get_field_id( $field )}\" ";
        }

        if ( $this->check_array_property( 'cssClass', $field, false ) ) {
            $attributes .= 'class="' . $field['cssClass'] . ' %s" ';
        } else {
            $attributes .= 'class="%s" ';
        }

        if ( $this->check_array_property( 'isRequired', $field ) ) {
            $attributes .= 'required ';
        }

        return trim( $attributes );
    }

    /**
     * Return the HTML id for a field
     */
    private function get_field_id( $field ) {
        return "input_{$this->id}_{$field['id']}";
    }

    /**
     * Get the prepopulated value for a field
     *
     * @since   0.0.0
     * @param   array   $field      The field data
     * @param   boolean $attribute  Whether to return an HTML attribute or the value only
     * @return  string  The value for the field, accounting for prepopulate values
     */
    private function get_field_value( $field, $attribute = true ) {
        $value = '';

        if ( $this->check_array_property( 'defaultValue', $field, false ) ) {
            $value = $field['defaultValue'];
        }

        if ( $this->check_array_property( 'allowsPrepopulate', $field ) ) {
            $name = $field['inputName'];

            // Look for values passed in to the shortcode
            if ( $this->get_field_settings_value( $name ) ) {
                $value = $this->get_field_settings_value( $name );
            }

            // Look for query string values
            if ( $this->check_array_property( $name, $_GET, false ) ) {
                $value = esc_html( $_GET[ $name ] );
            }
        }

        if ( ! $attribute || ! $value ) {
            return $value;
        }

        return sprintf( 'value="%s"', esc_attr( $value ) );
    }

    /**
     * Get a list of all the `<option>` tags for a `<select>`
     *
     * @since   0.0.0
     * @param   array   $field  The array of field data
     * @return  string  The HTML for all the choices
     */
    private function get_select_options( $field ) {
        $list = '';
        if ( ! $this->check_array_property( 'choices', $field, false ) ) return $list;

        foreach ($field['choices'] as $choice ) {
            $list .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $choice['value'] ),
                $choice['isSelected'] ? ' selected' : '',
                $choice['text']
            );
        }

        return $list;
    }

    /**
     * Get the value for the field from shortcode attributes, if available
     *
     * @since   0.0.0
     * @param   string  $field_name     The name of the field
     * @return  mixed   Returns (bool) false if unavailable or (string) value supplied
     */
    private function get_field_settings_value( $field_name ) {
        if ( empty( $this->settings['field_values'] ) ||
            ! $this->check_array_property( $field_name, $this->field_values, false ) ) return false;

        return $this->field_values[ $field_name ];
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

}
