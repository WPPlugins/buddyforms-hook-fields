<?php
/*
 Plugin Name: BuddyForms Hook Fields
 Plugin URI: https://themekraft.com/products/buddyforms-hook-fields/
 Description: BuddyForms Hook Fields
 Version: 1.2
 Author: ThemeKraft
 Author URI: https://themekraft.com/buddyforms/
 Licence: GPLv3
 Network: false

 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */

add_filter( 'buddyforms_formbuilder_fields_options', 'buddyforms_hook_options_into_formfields', 2, 3 );
function buddyforms_hook_options_into_formfields( $form_fields, $field_type, $field_id ) {
	global $post;

	$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );

	$hook_field_types = array(
		'text',
		'textarea',
		'link',
		'mail',
		'dropdown',
		'radiobutton',
		'checkbox',
		'taxonomy',
		'number',
		'date',
		'user_website'
	);

	$hook_field_types = apply_filters( 'buddyforms_hook_field_types', $hook_field_types );

	if ( ! in_array( $field_type, $hook_field_types ) ) {
		return $form_fields;
	}

	$hooks = array( 'no', 'before_the_title', 'after_the_title', 'before_the_content', 'after_the_content' );
	$hooks = apply_filters( 'buddyforms_form_element_hooks', $hooks );

	$form_fields['hooks']['html_display'] = new Element_HTML( '<div class="bf_element_display">' );

	$display = 'false';
	if ( isset( $buddyform['form_fields'][ $field_id ]['display'] ) ) {
		$display = $buddyform['form_fields'][ $field_id ]['display'];
	}

	$form_fields['hooks']['display'] = new Element_Select( "Display? <i>This only works for the single view</i>", "buddyforms_options[form_fields][" . $field_id . "][display]", $hooks, array( 'value' => $display ) );

	$hook = '';
	if ( isset( $buddyform['form_fields'][ $field_id ]['hook'] ) ) {
		$hook = $buddyform['form_fields'][ $field_id ]['hook'];
	}

	$form_fields['hooks']['hook'] = new Element_Textbox( "Hook: <i>Add hook name works global</i>", "buddyforms_options[form_fields][" . $field_id . "][hook]", array( 'value' => $hook ) );

	$display_name = 'false';
	if ( isset( $buddyform['form_fields'][ $field_id ]['display_name'] ) ) {
		$display_name = $buddyform['form_fields'][ $field_id ]['display_name'];
	}
	$form_fields['hooks']['display_name'] = new Element_Checkbox( "Display name?", "buddyforms_options[form_fields][" . $field_id . "][display_name]", array( '' ), array(
		'value' => $display_name,
		'id'    => "buddyforms_options[form_fields][" . $field_id . "][display_name]"
	) );

	$form_fields['hooks']['html_display_end'] = new Element_HTML( '</div>' );

	return $form_fields;
}

function buddyforms_form_display_element_frontend() {
	global $buddyforms, $post, $bf_hooked;

	if ( is_admin() ) {
		return;
	}

	if ( ! isset( $post->ID ) ) {
		return;
	}

	if ( $bf_hooked ) {
		return;
	}

	$form_slug = get_post_meta( $post->ID, '_bf_form_slug', true );

	if ( ! isset( $form_slug ) ) {
		return;
	}

	if ( ! isset( $buddyforms[ $form_slug ] ) ) {
		return;
	}

	if ( ! isset( $buddyforms[ $form_slug ]['form_fields'] ) ) {
		return;
	}

	$before_the_title   = false;
	$after_the_title    = false;
	$before_the_content = false;
	$after_the_content  = false;

	foreach ( $buddyforms[ $form_slug ]['form_fields'] as $key => $customfield ) :

		if ( ! empty( $customfield['slug'] ) && ( ! empty( $customfield['hook'] ) || is_single() ) ) :

			$customfield_value = get_post_meta( $post->ID, $customfield['slug'], true );

			if ( ! empty( $customfield_value ) ) {
				$post_meta_tmp = '<div class="post_meta ' . $customfield['slug'] . '">';

				if ( isset( $customfield['display_name'] ) ) {
					$post_meta_tmp .= '<label>' . $customfield['name'] . '</label>';
				}


				if ( is_array( $customfield_value ) ) {
					$meta_tmp = "<p>" . implode( ',', $customfield_value ) . "</p>";
				} else {
					$meta_tmp = "<p>" . $customfield_value . "</p>";
				}


				switch ( $customfield['type'] ) {
					case 'taxonomy':
						$meta_tmp = get_the_term_list( $post->ID, $customfield['taxonomy'], "<p>", ' - ', "</p>" );
						break;
					case 'link':
						$meta_tmp = "<p><a href='" . $customfield_value . "' " . $customfield['name'] . ">" . $customfield_value . " </a></p>";
						break;
					case 'user_website':
						$meta_tmp = "<p><a href='" . $customfield_value . "' " . $customfield['name'] . ">" . $customfield_value . " </a></p>";
						break;
					default:
						$meta_tmp = apply_filters( 'buddyforms_form_element_display_frontend', $meta_tmp, $customfield );
						break;
				}

				if ( $meta_tmp ) {
					$post_meta_tmp .= $meta_tmp;
				}

				$post_meta_tmp .= '</div>';

				$post_meta_tmp = apply_filters( 'buddyforms_form_element_display_frontend_before_hook', $post_meta_tmp );


				if ( isset( $customfield['hook'] ) && ! empty( $customfield['hook'] ) ) {
					add_action( $customfield['hook'], create_function( '', 'echo  "' . addcslashes( $post_meta_tmp, '"' ) . '";' ) );
				}

				if ( is_single() && isset( $customfield['display'] ) ) {
					switch ( $customfield['display'] ) {
						case 'before_the_title':
							$before_the_title .= $post_meta_tmp;
							break;
						case 'after_the_title':
							$after_the_title .= $post_meta_tmp;
							break;
						case 'before_the_content':
							$before_the_content .= $post_meta_tmp;
							break;
						case 'after_the_content':
							$after_the_content .= $post_meta_tmp;
							break;
					}
				}

			}

		endif;

	endforeach;

	if ( is_single() ) {

		if ( $before_the_title ) {
			add_filter( 'the_title', create_function( '$content,$id', 'if(is_single() && $id == get_the_ID()) { return "' . addcslashes( $before_the_title, '"' ) . '$content"; } return $content;' ), 10, 2 );
		}

		if ( $after_the_title ) {
			add_filter( 'the_title', create_function( '$content,$id', 'if(is_single() && $id == get_the_ID()) { return "$content' . addcslashes( $after_the_title, '"' ) . '"; } return $content;' ), 10, 2 );
		}

		if ( $before_the_content ) {
			add_filter( 'the_content', create_function( '', 'return "' . addcslashes( $before_the_content . $post->post_content, '"' ) . '";' ) );
		}

		if ( $after_the_content ) {
			add_filter( 'the_content', create_function( '', 'return "' . addcslashes( $post->post_content . $after_the_content, '"' ) . '";' ) );
		}

	}
	$bf_hooked = true;

}

add_action( 'the_post', 'buddyforms_form_display_element_frontend' );

//
// Check the plugin dependencies
//
add_action( 'init', function () {

	// Only Check for requirements in the admin
	if ( ! is_admin() ) {
		return;
	}

	// Require TGM
	require( dirname( __FILE__ ) . '/includes/resources/tgm/class-tgm-plugin-activation.php' );

	// Hook required plugins function to the tgmpa_register action
	add_action( 'tgmpa_register', function () {

		// Create the required plugins array
		if ( ! defined( 'BUDDYFORMS_PRO_VERSION' ) ) {
			$plugins['buddyforms'] = array(
				'name'     => 'BuddyForms',
				'slug'     => 'buddyforms',
				'required' => true,
			);


			$config = array(
				'id'           => 'buddyforms-tgmpa',
				'parent_slug'  => 'plugins.php',
				'capability'   => 'manage_options',
				'has_notices'  => true,
				'dismissable'  => false,
				'is_automatic' => true,
			);

			// Call the tgmpa function to register the required plugins
			tgmpa( $plugins, $config );
		}
	} );
}, 1, 1 );

// Create a helper function for easy SDK access.
function bhf_fs() {
	global $bhf_fs;

	if ( ! isset( $bhf_fs ) ) {
		// Include Freemius SDK.
		if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php';
		} else if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from premium parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php';
		} else {
			require_once dirname(__FILE__) . '/includes/resources/freemius/start.php';
		}

		$bhf_fs = fs_dynamic_init( array(
			'id'                  => '412',
			'slug'                => 'buddyforms-hook-fields',
			'type'                => 'plugin',
			'public_key'          => 'pk_834e229dbe701030d3c9d497b9ad0',
			'is_premium'          => false,
			'has_paid_plans'      => false,
			'parent'              => array(
				'id'         => '391',
				'slug'       => 'buddyforms',
				'public_key' => 'pk_dea3d8c1c831caf06cfea10c7114c',
				'name'       => 'BuddyForms',
			),
			'menu'                => array(
				'slug'           => 'edit.php?post_type=buddyforms-hook-fields',
				'first-path'     => 'edit.php?post_type=buddyforms&page=buddyforms_welcome_screen',
				'support'        => false,
			),
		) );
	}

	return $bhf_fs;
}
function bhf_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'buddyforms_core_fs' );
}

function bhf_fs_is_parent_active() {
	$active_plugins_basenames = get_option( 'active_plugins' );

	foreach ( $active_plugins_basenames as $plugin_basename ) {
		if ( 0 === strpos( $plugin_basename, 'buddyforms/' ) ||
		     0 === strpos( $plugin_basename, 'buddyforms-premium/' )
		) {
			return true;
		}
	}

	return false;
}

function bhf_fs_init() {
	if ( bhf_fs_is_parent_active_and_loaded() ) {
		// Init Freemius.
		bhf_fs();

		// Parent is active, add your init code here.
	} else {
		// Parent is inactive, add your error handling here.
	}
}

if ( bhf_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	bhf_fs_init();
} else if ( bhf_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'buddyforms_core_fs_loaded', 'bhf_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	bhf_fs_init();
}