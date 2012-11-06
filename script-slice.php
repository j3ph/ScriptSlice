<?php
/*
Plugin Name: Script Slice
Plugin URI: http://gravityslice.com/
Description: A no-hassle way to load external scripts into your site. For example, to include Google Adwords Conversion code, just save the script on the settings page, then choose which page you would like it to load on and where (top or bottom of page) and you're done.
Version: 0.1
Author: Jeff Purcell
Author URI: http://gravityslice.com/
Author Email: jeffpurcell@gmail.com
License:

  Copyright 2012 ( jeffpurcell@gmail.com )

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

class ScriptSlice {


	var $plugin_name           	= 'Script Slice';
	var $plugin_slug           	= 'script-slice';
	var $option_name           	= 'script_slice_options';
	var $option_group          	= 'script_slice_option_group';
	var $post_meta				= 'script_slice_scripts';
	var $plugin_dir            	= "";
	var $plugin_url		= "";
	var $defaults     = array(
		    'version'           => '0.1',
		    'reset_to_defaults' => '0',
		    'scripts' => array(),
		);
	
	var $settings = array();
	 
	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	
	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		
		// Register admin styles and scripts
		add_action( 'admin_print_styles', array( &$this, 'register_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'register_admin_scripts' ) );
	
		// Register site styles and scripts
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_scripts' ) );
		
		// activation and deactivation stuff
		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		// menu stuff
		add_filter( 'admin_menu', array( &$this, 'create_menu' ) );

		// meta boxes
		add_action( 'add_meta_boxes', array( &$this, 'slice_add_meta_box' ) );
	    add_action( 'save_post', array( &$this, 'save_postdata' ) );

	    // actions to add scripts to front end
	    add_action( 'wp_footer', array( &$this, 'enqueue_footer_scripts' ) );
	    add_action( 'wp_head', array( &$this, 'enqueue_header_scripts' ) );

	} // end constructor
	
	/**
	 * Fired when the plugin is activated.
	 *
	 * @params	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function activate( $network_wide ) {

		if( ! get_option( $this->option_name ) ) {
			add_option( $this->option_name, $this->defaults );
		}

	} // end activate
	
	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @params	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function deactivate( $network_wide ) {
	} // end deactivate
	
	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {
	
		wp_register_style( 'jquery-ui-css', plugin_dir_url( __FILE__ ) . 'css/slice/jquery-ui-1.9.0.custom.min.css' );
		wp_enqueue_style( 'jquery-ui-css' );

		wp_register_style( 'script_slice_admin_css', plugin_dir_url( __FILE__ ) . 'css/admin.css' );
		wp_enqueue_style( 'script_slice_admin_css' );		
	
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */	
	public function register_admin_scripts() {
	
		wp_register_script( 'script_slice_admin', plugin_dir_url( __FILE__ ) . 'js/admin.js' );
		wp_enqueue_script( 'script_slice_admin' );

		wp_register_script( 'script_slice_ui', plugin_dir_url( __FILE__ ) . 'js/jquery.ui.min.js', array( 'jquery' ), '1.9.0' );
		wp_enqueue_script( 'script_slice_ui' );

	} // end register_admin_scripts
	
	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_plugin_styles() {
	
	} // end register_plugin_styles
	
	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {
	
	} // end register_plugin_scripts
	
	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/
	
	/*
	 *
	 * BEGIN WP-ADMIN STUFF
	 *
	 */  
	public function create_menu() {

		// Adding submenu if user has access
        add_options_page( $this->plugin_name , $this->plugin_name, 'manage_options', $this->plugin_slug, array( &$this, 'options_page' ) );

	}



	public function add_script( $x ) {

		if( empty( $x['name'] ) || empty( $x['script'] ) ) {
        	echo '<div class="error"><p><strong>' . __('You must provide a script name and value.', $this->plugin_slug ) . '</strong></p></div>';
        	return;
        }

        $tmp = get_option( $this->option_name );
        $exists = false;
        
        if( is_array( $tmp['scripts'] )  && count( $tmp['scripts'] ) > 0 ) {

	        foreach( $tmp['scripts'] as $key => $value ) {

	        	if( $value['name'] == $x['name'] ) {

	        		$exists = true;
	        		break;

	        	} else {

	        		$exists = false;

	        	}

	        }

	    } else {

	    	$tmp['scripts'] = array();

	    }

        if ( ! $exists ) {
        	array_push( $tmp['scripts'], $x );
        	update_option( $this->option_name, $tmp );
        	echo '<div class="updated"><p><strong>' . __('Script added successfully.', $this->plugin_slug ) . '</strong></p></div>';
        } else {
        	echo '<div class="error"><p><strong>' . __('A script with that name already exists.', $this->plugin_slug ) . '</strong></p></div>';
        }

	}



	public function update_script( $x ) {

		$tmp = get_option( $this->option_name );
		$i = 0;

		foreach( $tmp['scripts'] as $key => $value ) {

        	if( $value['name'] == $x['slice_script_name'] ) {

        		$tmp['scripts'][$key]['script'] 		= $x['slice_script_value'];
        		$tmp['scripts'][$key]['where']  		= $x['slice_script_where'];
        		$tmp['scripts'][$key]['every_page'] 	= $x['slice_script_every_page'];

        		if( update_option( $this->option_name, $tmp ) ) {
        			echo '<div class="updated"><p><strong>' . __('Script updated successfully.', $this->plugin_slug ) . '</strong></p></div>';	
        		} else {
        			echo '<div class="error"><p><strong>' . __('Script was not updated.', $this->plugin_slug ) . '</strong></p></div>';
        		}
        		
        		break;

        	} else {
        		$exists = false;
        	}

        	$i++;

        }

	}



	public function delete_script( $x ) {
		$tmp = get_option( $this->option_name );

		foreach( $tmp['scripts'] as $key => $value ) {

        	if( $value['name'] == $x['delete_name'] ) {
        		
        		unset( $tmp['scripts'][$key] );

        		if( update_option( $this->option_name, $tmp ) ) {
        			echo '<div class="updated"><p><strong>' . __('Script deleted successfully.', $this->plugin_slug ) . '</strong></p></div>';
        		} else {
        			echo '<div class="error"><p><strong>' . __('Script was not deleted.', $this->plugin_slug ) . '</strong></p></div>';
        		}

        		break;

        	} 

        }

	}



	public function options_page() {

		echo '<div class="wrap">';
		echo '<div id="icon-options-general" class="icon32"></div>';
		echo '<h2>' . __( $this->plugin_name . ' Options Page', $this->plugin_slug ) . '</h2>';

		# add form submission
		if( isset( $_POST['slice_hidden'] ) && $_POST[ 'slice_hidden' ] == 'Y' ) {
	        // Read their posted value
	        if( wp_verify_nonce( $_POST['slice_add_nonce'], 'add_slice_script' ) ) {
		        $x = array();
		        $x['name'] = $_POST['slice_script_name'];
		        $x['script'] = $_POST['slice_script_value'];
		        $x['where'] = $_POST['slice_script_where'];
		        $x['every_page'] = $_POST['slice_script_every_page'];

		        $this->add_script( $x );
	     	} else {
	     		echo '<div class="error"><p><strong>' . __('Your add nonce did not verify.', $this->plugin_slug ) . '</strong></p></div>';
	     	}
	    } else if( isset( $_POST['slice_update_hidden'] ) && $_POST[ 'slice_update_hidden' ] == 'Y' ) {
	    	#update form submssion
	    	if( wp_verify_nonce( $_POST['slice_update_nonce'], 'slice_update_script' ) ) {
	    		# nonce verified
	    		# proceed with update

	    		$this->update_script( $_POST );

	    	} else {
				echo '<div class="error"><p><strong>' . __('Your update nonce did not verify.', $this->plugin_slug ) . '</strong></p></div>';
	    	}
	    } else if( isset( $_POST['slice_delete_hidden'] ) && $_POST[ 'slice_delete_hidden' ] == 'Y' ) {
	    	#delete form submssion
	    	if( wp_verify_nonce( $_POST['slice_delete_nonce'], 'slice_delete_script' ) ) {
	    		# nonce verified
	    		# proceed with delete

	    		$this->delete_script( $_POST );

	    	} else {
	    		echo '<div class="error"><p><strong>' . __('Your delete nonce did not verify.', $this->plugin_slug ) . '</strong></p></div>';
	    	}
	    }

	    #list current scripts in option value
	    $this->display_add_form();
	    $this->list_current_scripts();
	    
		echo '</div>';

	}



	public function list_current_scripts() {

		$option = get_option( $this->option_name );

		$i = 1;
		if( count( $option['scripts'] ) > 0 ) :

			echo '<h3>' . __( 'Manage Existing Scripts', $this->plugin_slug ) . '</h3>';
			echo '<div id="accordion">';

			foreach ( $option['scripts'] as $script ) : 

				echo '<h3 class="script_slice">' . sanitize_text_field( $script['name'] ) . '</h3>';
				echo '<div>';
				echo '<form name="slice_update_form_'.$i.'" id="slice_update_form_'.$i.'" method="post" action="">';
				echo wp_nonce_field( 'slice_update_script', 'slice_update_nonce', true, false );
				echo '<input type="hidden" name="slice_update_hidden" value="Y">';
				echo '<table class="form-table">';
				
				echo '<input type="hidden" id="slice_script_name" name="slice_script_name" value="'.sanitize_text_field( $script['name'] ).'">';

				echo '<tr class="form-field form-required">';
				echo '<th scope="row"><label for="slice_script_value">' . __( "Script Value", $this->plugin_slug ) . '</label> <span class="description">(required)</span></th>';
				echo '<td><textarea id="slice_script_value" name="slice_script_value" rows="8">'. stripslashes( $script['script'] ).'</textarea></td>';
				echo '</tr>';

				echo '<tr class="form-field form-required">';
				echo '<th scope="row"><label for="slice_script_every_page">' . __( "Load Script on Every Page?", $this->plugin_slug ) . '</label></th>';
				echo '<td style="text-align:left;">';
				echo '<select id="slice_script_every_page" name="slice_script_every_page">';
				echo '<option value="no"'. selected( $script["every_page"], 'no', false) .'>'. __( "No", $this->plugin_slug ) .'</option>';
				echo '<option value="yes"'. selected( $script["every_page"], 'yes', false) .'>'. __( "Yes", $this->plugin_slug ) .'</option>';
				echo '</select>';

				echo '<tr class="form-field form-required">';
				echo '<th scope="row"><label for="slice_script_where">' . __( "Load Script at Top or Bottom of Page?", $this->plugin_slug ) . '</label></th>';
				echo '<td style="text-align:left;">';
				echo '<select name="slice_script_where">';
				echo '<option value="top"'. selected( $script["where"], 'top', false) .'>'. __( "Top, inside the head section.", $this->plugin_slug ) .'</option>';
				echo '<option value="bottom"'. selected( $script["where"], 'bottom', false) .'>'. __( "Bottom, just before the closing body tag", $this->plugin_slug ) .'</option>';
				echo '</select>';
				echo '</td>';
				echo '</tr>';
				
				echo '<tr><th scope="row"><input type="submit" name="Submit" class="button-primary" value="' . __('Update Script') . '">';
				echo '</form>';
				echo '<form id="slice_delete_'.$i.'" action="" method="post" style="display: inline;">';
				echo wp_nonce_field( 'slice_delete_script', 'slice_delete_nonce', true, false );
				echo '<input type="hidden" name="slice_delete_hidden" value="Y">';
				echo '<input type="hidden" class="hidden" name="delete_name" value="'.sanitize_text_field( $script['name'] ).'">';
				echo '<input type="submit" value="'. __( "Delete Script" ).'" name="delete" class="button-secondary"></th></tr>';

				echo '</table>';
				echo '</form>';
				echo '</div>';

				$i++;

			endforeach;

			echo '</div>';

		endif;	
	
	}



	public function display_add_form() {

		echo '<h3>' . __( 'Add a New Script', $this->plugin_slug ) . '</h3>';
		echo '<form name="sliceform" method="post" action="">';
		echo wp_nonce_field( 'add_slice_script', 'slice_add_nonce', true, false );
		echo '<input type="hidden" name="slice_hidden" value="Y">';
		echo '<table class="form-table">';
		
		echo '<tr class="form-field form-required">';
		echo '<th scope="row"><label for="slice_script_name">' . __( "Script Name", $this->plugin_slug ) . '</label> <span class="description">(required)</span></th>';
		echo '<td><input type="text" name="slice_script_name" value=""></td>';
		echo '</tr>';
		
		echo '<tr class="form-field form-required">';
		echo '<th scope="row"><label for="slice_script_value">' . __( "Script Value", $this->plugin_slug ) . '</label> <span class="description">(required)</span></th>';
		echo '<td><textarea name="slice_script_value" rows="8"></textarea></td>';
		echo '</tr>';
		
		echo '<tr class="form-field form-required">';
		echo '<th scope="row"><label for="slice_script_every_page">' . __( "Load Script on Every Page?", $this->plugin_slug ) . '</label></th>';
		echo '<td style="text-align:left;">';
		echo '<select name="slice_script_every_page">';
		echo '<option value="no">'. __( "No", $this->plugin_slug ) .'</option>';
		echo '<option value="yes">'. __( "Yes", $this->plugin_slug ) .'</option>';
		echo '</select>';

		echo '<tr class="form-field form-required">';
		echo '<th scope="row"><label for="slice_script_where">' . __( "Load Script at Top or Bottom of Page?", $this->plugin_slug ) . '</label></th>';
		echo '<td style="text-align:left;">';
		echo '<select name="slice_script_where">';
		echo '<option value="top">'. __( "Top, inside the head section.", $this->plugin_slug ) .'</option>';
		echo '<option value="bottom">'. __( "Bottom, just before the closing body tag", $this->plugin_slug ) .'</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';
		
		echo '<tr><th scope="row"><input type="submit" name="Submit" class="button-primary" value="' . __('Add Script') . '">';

		echo '</table>';
		echo '</form>';

	}



	/*
	 *
	 *
	 * Meta Box Section
	 *
	 *
	 */
	public function slice_add_meta_box() {
		add_meta_box( 'script_slice_sectionid', __( $this->plugin_name, $this->plugin_slug ), array( &$this, 'print_meta_box'), 'post' );
		add_meta_box( 'script_slice_sectionid', __( $this->plugin_name, $this->plugin_slug ), array( &$this, 'print_meta_box'), 'page' );
	}


	public function print_meta_box( $post ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'slice_meta_nonce' );

		// The actual fields for data entry
		#echo '<label for="myplugin_new_field">'. __("Description for this field", 'myplugin_textdomain' ) . '</label>';
		#echo '<input type="text" id="myplugin_new_field" name="myplugin_new_field" value="whatever" size="25" />';

		$tmp = get_option( $this->option_name );

		#show form
		echo '<p><strong>Manage Scripts For This Page</strong></p>';
		$this->load_scripts_into_checkboxes( $tmp['scripts'] );
	}


	public function load_scripts_into_checkboxes( $scripts ) {
		
		if( count( $scripts ) > 0 ) {

			global $post;

			$scripts_to_post = get_post_meta( $post->ID, $this->post_meta, true );
			$i = 0;

			if( is_array( $scripts_to_post ) ) {
				foreach( $scripts as $script ) {
					if( array_search( $script['name'], $scripts_to_post ) !== FALSE ) {
						echo '<input type="checkbox" name="script_slice_scripts[]" id="script_slice_scripts_'.$i.'" value="'.$script['name'].'" checked="checked"><label class="script_slice_label" for="script_slice_scripts_'.$i.'">'.$script["name"].'</label><br>';
					} else {
						echo '<input type="checkbox" name="script_slice_scripts[]" id="script_slice_scripts_'.$i.'" value="'.$script['name'].'"><label class="script_slice_label" for="script_slice_scripts_'.$i.'">'.$script["name"].'</label><br>';
					}
					$i++;
				}
			} else {
				foreach( $scripts as $script ) {
					if( $scripts_to_post == $script['name'] ) {
						echo '<input type="checkbox" name="script_slice_scripts[]" id="script_slice_scripts_'.$i.'" value="'.$script['name'].'" checked="checked"><label class="script_slice_label" for="script_slice_scripts_'.$i.'">'.$script["name"].'</label><br>';
					} else {
						echo '<input type="checkbox" name="script_slice_scripts[]" id="script_slice_scripts_'.$i.'" value="'.$script['name'].'"><label class="script_slice_label" for="script_slice_scripts_'.$i.'">'.$script["name"].'</label><br>';
					}
					$i++;
				}
			}

		} else {
			echo '<p>You need to <a href="options-general.php?page='.$this->plugin_slug.'">add a script</a> before you can attach it to thisi page.</p>';
		}

	}

	function save_postdata( $post_id ) {

		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		// verify this came from our screen and with proper authorization,
		// because save_post can be triggered at other times

		if ( isset( $_POST['slice_meta_nonce'] ) && !wp_verify_nonce( $_POST['slice_meta_nonce'], plugin_basename( __FILE__ ) ) )
			return;


		// Check permissions
		if ( isset($_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}

		// OK, we're authenticated: we need to find and save the data

		if( isset( $_POST['script_slice_scripts'] ) ) { 

			$mydata = $_POST['script_slice_scripts'];
			update_post_meta( $post_id, $this->post_meta, $mydata );
		}
	}


	/*
	 *
	 * Begin Public Facing Script Output Functions
	 *
	 */

	public function enqueue_footer_scripts() {
		global $post;

		$tmp = get_option( $this->option_name );
		$post_meta = get_post_meta( $post->ID, $this->post_meta, true);

		if( $post_meta == '') { 
			# may be home page, search, archive, etc.
			# load "every_page" scripts

			$tmp = get_option( $this->option_name );

			if( is_array( $tmp['scripts'] ) ) {
				foreach( $tmp['scripts'] as $script ) {
					if( 'yes' == $script['every_page'] && 'bottom' == $script['where'] ) {
						echo stripslashes( $script['script'] );
					}
				}
			}

		} else {
			#should be page/post/custom post type
			#post id exists

			foreach( $tmp['scripts'] as $script ) {
				if( 'bottom' == $script['where'] ) {
					if( 'yes' == $script['every_page'] ) {
						echo stripslashes( $script['script'] );
					} else if( array_search( $script['name'], $post_meta ) !== FALSE ) {
						echo stripslashes( $script['script'] );
					}
				}
			}
		}

	}

	public function enqueue_header_scripts() {
		global $post;

		$tmp = get_option( $this->option_name );
		$post_meta = get_post_meta( $post->ID, $this->post_meta, true);

		if( $post_meta == '') { 
			# may be home page, search, archive, etc.
			# load "every_page" scripts

			$tmp = get_option( $this->option_name );

			if( is_array( $tmp['scripts'] ) ) {
				foreach( $tmp['scripts'] as $script ) {
					if( 'yes' == $script['every_page'] && 'top' == $script['where'] ) {
						echo stripslashes( $script['script'] );
					}
				}
			}

		} else {
			#should be page/post/custom post type
			#post id exists

			foreach( $tmp['scripts'] as $script ) {
				if( 'top' == $script['where'] ) {
					if( 'yes' == $script['every_page'] ) {
						echo stripslashes( $script['script'] );
					} else if( array_search( $script['name'], $post_meta ) !== FALSE ) {
						echo stripslashes( $script['script'] );
					}
				}
			}
		}
	}

} // end class

new ScriptSlice();