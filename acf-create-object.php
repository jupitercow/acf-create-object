<?php

/**
 * @link              https://github.com/jupitercow/
 * @since             1.1.0
 * @package           Acf_Create_Object
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Custom Fields: Create Object
 * Plugin URI:        http://Jupitercow.com/
 * Description:       Very basic plugin that allows a basic interface for creating posts or users from the front end through ACF 4.
 * Version:           1.1.1
 * Author:            Jupitercow
 * Author URI:        http://Jupitercow.com/
 * Contributor:       Jake Snyder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       acf_create_object
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$class_name = 'Acf_Create_Object';
if (! class_exists($class_name) ) :

class Acf_Create_Object
{
	/**
	 * The unique prefix for ACF.
	 *
	 * @since    1.1.0
	 * @access   protected
	 * @var      string    $prefix         The string used to uniquely prefix for Sewn In.
	 */
	protected $prefix;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $settings       The array used for settings.
	 */
	protected $settings;

	/**
	 * Store any errors
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $errors       The array used for errors.
	 */
	protected $errors;

	/**
	 * Load the plugin.
	 *
	 * @since	1.1.0
	 * @return	void
	 */
	public function run()
	{
		$this->settings();

		add_action( 'init',                          array($this, 'init') );
	}

	/**
	 * Make sure that any neccessary dependancies exist
	 *
	 * @author  Jake Snyder
	 * @since	1.1.0
	 * @return	bool True if everything exists
	 */
	public function test_requirements()
	{
		// Look for ACF
		if ( ! class_exists('acf') && ! class_exists('Acf') ) { return false; }
		return true;
	}

	/**
	 * Class settings
	 *
	 * @author  Jake Snyder
	 * @since	1.1.0
	 * @return	void
	 */
	public function settings()
	{
		$this->prefix      = 'acf';
		$this->plugin_name = strtolower(__CLASS__);
		$this->version     = '1.1.0';
		$this->settings    = array(
			'loaded'      => false,
			'post_status' => 'draft',
			'post_title'  => "New Post",
			'post_type'   => 'post',
			'strings' => array(
				'username'         => __( "Please enter a username.", $this->plugin_name ),
				'username_invalid' => __( "This username is invalid because it uses illegal characters. Please enter a valid username.", $this->plugin_name ),
				'username_exists'  => __( "This username is already registered. Please choose another one.", $this->plugin_name ),
				'email'            => __( "Please type your e-mail address.", $this->plugin_name ),
				'email_invalid'    => __( "The email address isn&#8217;t correct.", $this->plugin_name ),
				'email_exists'     => __( "This email is already registered, please choose another one.", $this->plugin_name ),
				'pass1'            => __( "Password is required.", $this->plugin_name ),
				'pass2'            => __( "The passwords did not match.", $this->plugin_name ),
				'empty'            => __( "One or more fields below are required.", $this->plugin_name ),
				'register'         => sprintf( __( "Couldn&#8217;t register you&hellip; please contact the <a href=\"mailto:%s\">site administrator</a>!", $this->plugin_name ), get_option('admin_email') ),
			),
			'profile_fields' => array(
				'username',
				'login',
				'user_login',
				'email',
				'user_email',
				'first_name',
				'last_name',
				'description',
				'user_url',
				'jabber',
				'aim',
				'yim',
				'show_admin_bar_front',
			),
			'pass_fields' => array(
				'pass1',
				'pass2'
			),
		);
		$this->errors = array();
	}

	/**
	 * On plugins_loaded test if we can use sewn_notifications
	 *
	 * @author  Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public function plugins_loaded()
	{
		// Have the login plugin use frontend notifictions plugin
		if ( apply_filters( "{$this->prefix}/create_object/use_sewn_notifications", true ) )
		{
			if (! class_exists('Sewn_Notifications') ) {
				add_filter( "{$this->prefix}/create_object/use_sewn_notifications", '__return_false', 9999 );
			}
		}
	}

	/**
	 * Initialize the Class
	 *
	 * @author  Jake Snyder
	 * @since	1.0.0
	 * @return	void
	 */
	public function init()
	{
		if (! $this->test_requirements() ) { return false; }

		$this->settings = apply_filters( "{$this->prefix}/create_object/settings", $this->settings );

		$this->plugins_loaded();

		// Create the new object
		add_filter( 'acf/pre_save_post',             array($this, 'create_object') );

		// If there was a problem creating a user, stop the process and redirect
		add_action( 'acf/save_post',                 array($this, 'acf_save_post'), 1 );

		// Load values from user's profile (non-meta)
		add_filter( 'acf/load_value',                array($this, 'load_profile_fields'), 10, 3 );
		add_filter( 'acf/load_value',                array($this, 'remove_new_post_value'), 99, 3 );
	}

	/**
	 * Remove "new_post" values
	 *
	 * When 'new_post' gets stored in options, this keeps it from loading up by default
	 *
	 * @author  Jake Snyder
	 * @type	filter
	 */
	public function remove_new_post_value( $value, $post_id, $field )
	{
		if ( 'new_post' == $post_id ) {
			$value = false;
		}
		return $value;
	}

	/**
	 * Handle Errors to Stop Redirect
	 *
	 * If an error, don't allow the page to be redirected.
	 *
	 * @author  Jake Snyder
	 * @type	action
	 */
	public function acf_save_post( $post_id )
	{
		if ( 'error' == $post_id ) {
			unset($_POST['return']);
		}
	}

	/**
	 * Load the profile fields
	 *
	 * @author  Jake Snyder
	 * @type	filter
	 */
	public function load_profile_fields( $value, $post_id, $field )
	{
		if ( ! $value && in_array($field['name'], $this->settings['profile_fields']) )
		{
			$user = get_user_by( 'id', $post_id );
			if ( $user )
			{
				if ( 'username' == $field['name'] || 'login' == $field['name'] ) {
					$key = 'user_login';
				} elseif ( 'email' == $field['name'] ) {
					$key = 'user_email';
				}

				if (! empty($user->$key) ) {
					$value = $user->$key;
				}
			}
		}
		return $value;
	}

	/**
	 * Allow Post/User Creation
	 *
	 * This takes the "post_id" argument passed to the "acf_form()" form, and if, instead of a number, it is "new" or "post", 
	 * it will create a new post and return that id for editing. Or if it is 'user', a new user will be created and their id 
	 * (in user_{user_id} format) will be returned.
	 *
	 * @author  Jake Snyder
	 * @type	filter
	 * @param	int|string	$post_id id of the post you want to edit, or a string to instruct creating a new object
	 * @return	void
	 */
	public function create_object( $post_id )
	{
		$create_types = array( 'new_post', 'new_user' );

		// check if this is to be a new object
		if (! in_array($post_id, $create_types) && 0 !== strpos($post_id, 'user_') ) { return $post_id; }

		/**
		 * Load the fields from $_POST
		 */
		if ( empty($_POST['fields']) || ! is_array($_POST['fields']) )
		{
			#$this->errors['empty'] = apply_filters( "{$this->prefix}/create_object/error_empty", $this->settings['strings']['empty'] );
		}
		else
		{
			foreach( $_POST['fields'] as $k => $v )
			{
				// get field
				$f = apply_filters('acf/load_field', false, $k );
				$f['value'] = $v;
				$this->settings['fields'][$f['name']] = $f;
			}
		}

		/**
		 * Allow profile update
		 */
		if ( 0 === strpos($post_id, 'user_') )
		{
			$update_args = array();

			$all_fields = array_merge($this->settings['profile_fields'], $this->settings['pass_fields']);
			foreach ( $all_fields as $profile_field )
			{
				if (! empty($this->settings['fields'][$profile_field]['value']) && 'pass2' != $profile_field )
				{
					if ( 'username' == $profile_field || 'login' == $profile_field ) {
						$upate_key = 'user_login';
					} elseif ( 'email' == $profile_field ) {
						$upate_key = 'user_email';
					} elseif ( 'pass1' == $profile_field ) {
						if ( isset($this->settings['fields']['pass2']['value']) && ! empty($this->settings['fields']['pass1']['value']) && $this->settings['fields']['pass1']['value'] != $this->settings['fields']['pass2']['value'] ) {
							$this->errors['pass2'] = apply_filters( "{$this->prefix}/create_object/error/pass2", $this->settings['strings']['pass2'] );
						}
						$upate_key = 'user_pass';
					} else {
						$upate_key = $profile_field;
					}

					$update_args[$upate_key] = $this->settings['fields'][$profile_field]['value'];
				}
			}

			// If there are errors, output them, keep the form from processing, and remove any redirect
			if ( $this->errors )
			{
				// Output the messges area on the form
				add_filter( 'acf/get_post_id', array($this, 'add_error') );
				// Turn the post_id into an error
				$post_id = 'error';
			}
			elseif ( $update_args )
			{
				$id_array = explode('_', $post_id);
				$user_id  = ( $id_array ) ? end( $id_array ) : $post_id;
				$update_args['ID'] = $user_id;
				wp_update_user($update_args);
			}

			// Delete the profile fields so they don't get added to meta
			$this->unset_profile_fields();
		}
		/**
		 * Register a new user
		 */
		elseif ( 'new_user' == $post_id )
		{
			// Whether or not password should be created by default, it will be required from a field otherwise
			$create_password = apply_filters( "{$this->prefix}/create_object/user/create_password", false );

			// Required fields for user registration
			$required = apply_filters( "{$this->prefix}/create_object/user/required", array(
				'username',
				'email'
			) );
			// If password isn't being created, then a password field is required
			if (! $create_password ) {
				$required[] = 'pass1';
			}

			// Allow extra processing / updating to the fields
			$this->settings['fields'] = apply_filters( "{$this->prefix}/create_object/user/fields", $this->settings['fields'] );

			// Test that required fields exist
			foreach ( $required as $key )
			{
				if ( empty($this->settings['fields'][$key]) || empty($this->settings['fields'][$key]['value']) ) {
					$this->errors[$key] = apply_filters( "{$this->prefix}/create_object/error/$key", $this->settings['strings'][$key] );
				}
			}

			// If everything is there, lets get more detailed
			if (! $this->errors )
			{
				// Check the username
				$sanitized_user_login = $this->check_username( $this->settings['fields']['username']['value'] );

				// Check the e-mail address
				$user_email = $this->check_email( $this->settings['fields']['email']['value'] );

				// Test that passwords match if there are two password fields
				if ( isset($this->settings['fields']['pass2']['value']) && ! empty($this->settings['fields']['pass1']['value']) && $this->settings['fields']['pass1']['value'] != $this->settings['fields']['pass2']['value'] ) {
					$this->errors['pass2'] = apply_filters( "{$this->prefix}/create_object/error/pass2", $this->settings['strings']['pass2'] );
				}
			}

			// Allow errors to be manipulated as a whole before creating user
			$this->errors = apply_filters( "{$this->prefix}/create_object/user/validation", $this->errors, $this->settings['fields'] );

			// If there are no errors, create the user
			if (! $this->errors )
			{
				if ( $create_password && empty($this->settings['fields']['pass1']['value']) )
				{
					$user_pass = wp_generate_password( 12, false);
					$generated_pass = true;
				}
				else
				{
					$user_pass = $this->settings['fields']['pass1']['value'];
					$generated_pass = false;
				}

				$user_pass = $this->settings['fields']['pass1']['value'];
				$user_id = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
				if (! $user_id )
				{
					$this->errors = apply_filters( "{$this->prefix}/create_object/error/register", $this->settings['strings']['register'] );
				}
				else
				{
					$post_id = 'user_'. $user_id;

					$update_args = array();
					foreach ( $this->settings['profile_fields'] as $key ) {
						if (! empty($this->settings['fields'][$key]['value']) ) $update_args[$key] = $this->settings['fields'][$key]['value'];
					}

					if ( $update_args ) {
						$update_args['ID'] = $user_id;
						wp_update_user($update_args);
					}

					// Add a password nag if the password was created
					if ( $generated_pass ) {
						update_user_option( $user_id, 'default_password_nag', true, true );
					}

					// Notify user with log in details
					wp_new_user_notification( $user_id, $user_pass );

					do_action( "{$this->prefix}/create_object/user/created", $post_id, $this->settings['fields'], $user_pass );
				}
			}

			// If there are errors, output them, keep the form from processing, and remove any redirect
			if ( $this->errors )
			{
				if ( apply_filters( "{$this->prefix}/create_object/use_sewn_notifications", true ) && class_exists('Sewn_Notifications') )
				{
					foreach ( $this->errors as $key => $error ) {
						do_action( 'sewn/notifications/add', $error, 'error=1' );
					}
				}
				else
				{
					// Output the messges area on the form
					add_filter( 'acf/get_post_id', array($this, 'add_error') );
				}

				// Turn the post_id into an error
				$post_id = 'error';

				// Add submitted values to fields
				$this->add_submitted_values_to_fields();
			}

			// Delete the profile fields so they don't get added to meta
			$this->unset_profile_fields();
		}
		/**
		 * Create a new post
		 */
		else
		{
			$post = array(
				'post_status' => (! empty($_POST['post_status']) ) ? $_POST['post_status'] : apply_filters( "{$this->prefix}/create_object/post/status", $this->settings['post_status'] ),
				'post_title'  => (! empty($_POST['post_title'])  ) ? $_POST['post_title']  : apply_filters( "{$this->prefix}/create_object/post/title", $this->settings['post_title'] ),
				'post_type'   => (! empty($_POST['post_type'])   ) ? $_POST['post_type']   : apply_filters( "{$this->prefix}/create_object/post/type", $this->settings['post_type'] )
			);

			$post_title_keys  = array( 'form_post_title', 'post_title' );
			$post_status_keys = array( 'form_post_status', 'post_status' );
			$post_type_keys   = array( 'form_post_type', 'post_type' );
			foreach ( $this->settings['fields'] as $field )
			{
				if ( in_array($field['name'], $post_title_keys) ) {
					$post['post_title'] = $field['value'];
				} elseif ( in_array($field['name'], $post_status_keys) ) {
					$post['post_status'] = $field['value'];
				} elseif ( in_array($field['name'], $post_type_keys) ) {
					$post['post_type'] = $field['value'];
				}
			}

			if ( $post['post_title'] ) $post['post_name'] = sanitize_title($post['post_title']);

			// insert the post
			$post_id = wp_insert_post( $post );
			do_action( "{$this->prefix}/create_object/post/created", $post_id );
		}

		do_action( "{$this->prefix}/create_object/object/created", $post_id );

		return $post_id;
	}

	public function add_submitted_values_to_fields()
	{
		foreach ( $this->settings['fields'] as $field )
		{
			add_filter( "acf/load_value/name={$field['name']}",   array($this, 'add_submitted_value_to_field'), 99, 3 );
			add_filter( "acf/format_value/name={$field['name']}", array($this, 'add_submitted_value_to_field'), 99, 3 );
		}
	}

	public function add_submitted_value_to_field( $value, $post_id, $field )
	{
		return $this->settings['fields'][$field['name']]['value'];
	}

	/**
	 * Remove profile fields from $_POST, so they don't get auto stored into meta by ACF
	 *
	 * @author  Jake Snyder
	 * @type	function
	 * @return 	void
	 */
	public function unset_profile_fields()
	{
		if (! $this->settings['fields'] || empty($_POST['fields']) ) { return false; }

		$all_fields = array_merge($this->settings['profile_fields'], $this->settings['pass_fields']);

		foreach ( $all_fields as $key ) {
			if (! empty($this->settings['fields'][$key]) ) {
				unset( $_POST['fields'][$this->settings['fields'][$key]['key']] );
			}
		}

		if ( empty($_POST['fields']) ) {
			unset($_POST['fields']);
		}
	}

	/**
	 * Remove profile fields from $fields, so they don't get auto stored into meta by ACF
	 *
	 * @author  Jake Snyder
	 * @type	action
	 * @return 	void
	 */
	public function delete_profile_fields( $fields, $post_id )
	{
		foreach ( $fields as &$field )
		{
			if ( array_key_exists($field['name'], $this->settings['fields']) && 'pass1' != $field['name'] && 'pass2' != $field['name'] ) {
				$field['value'] = $this->settings['fields'][$field['name']];
			}
		}

		return $fields;
	}

	/**
	 * Outputs error messages
	 *
	 * Adds them in a pretty hacky way for now because there is no action built in.
	 *
	 * @author  Jake Snyder
	 * @type	filter
	 * @return  int $post_id because this is actually an unrelated filter that we are using to toss up the error messages
	 */
	public function add_error( $post_id )
	{
		echo '<div id="message" class="error">';
		foreach ( $this->errors as $key => $error ) {
			echo "<p class=\"$key\">$error</p>";
		}
		echo '</div>';

		return $post_id;
	}

	/**
	 * Test username similar to wp-login.php
	 *
	 * @author  Jake Snyder
	 * @param   string $username the submitted username
	 * @return  string the sanitized username or empty if something was wrong
	 */
	public function check_username( $username )
	{
		// Check the username
		$sanitized_user_login = sanitize_user( $username );
		if (! validate_username( $username ) ) {
			$this->errors['username'] = apply_filters( "{$this->prefix}/create_object/error/username", $this->settings['strings']['username'] );
			$sanitized_user_login = '';
		} elseif ( username_exists( $sanitized_user_login ) ) {
			$this->errors['username'] = apply_filters( "{$this->prefix}/create_object/error/username_exists", $this->settings['strings']['username_exists'] );
		}

		return $sanitized_user_login;
	}

	/**
	 * Test email address similar to wp-login.php
	 *
	 * @author  Jake Snyder
	 * @param   string $email the submitted email address
	 * @return  string the approved email or empty if something was wrong
	 */
	public function check_email( $email )
	{
		// Check the e-mail address
		$user_email = apply_filters( 'user_registration_email', $email );
		if (! is_email( $user_email ) ) {
			$this->errors['email'] = apply_filters( "{$this->prefix}/create_object/error/email_invalid", $this->settings['strings']['email_invalid'] );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$this->errors['email'] = apply_filters( "{$this->prefix}/create_object/error/email_exists", $this->settings['strings']['email_exists'] );
		}
		return $user_email;
	}
}

$$class_name = new $class_name;
$$class_name->run();
unset($class_name);

endif;