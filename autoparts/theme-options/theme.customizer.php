<?php
/**
 * Theme customizer
 *
 * @package WordPress
 * @subpackage AUTOPARTS
 * @since AUTOPARTS 1.0
 */



//--------------------------------------------------------------
//-- New panel in the Customizer Controls
//--------------------------------------------------------------

// Theme init priorities:
// 3 - add/remove Theme Options elements
if (!function_exists('autoparts_customizer_setup3')) {
	add_action( 'after_setup_theme', 'autoparts_customizer_setup3', 3 );
	function autoparts_customizer_setup3() {
		autoparts_storage_merge_array('options', '', array(
			'cpt' => array(
				"title" => esc_html__('Custom post types', 'autoparts'),
				"desc" => '',
				"type" => "panel"
				)
			)
		);
	}
}
// 3 - add/remove Theme Options elements
if (!function_exists('autoparts_customizer_setup9999')) {
	add_action( 'after_setup_theme', 'autoparts_customizer_setup9999', 9999 );
	function autoparts_customizer_setup9999() {
		autoparts_storage_merge_array('options', '', array(
			'cpt_end' => array(
				"type" => "panel_end"
				)
			)
		);
	}
}


//--------------------------------------------------------------
//-- Register Customizer Controls
//--------------------------------------------------------------

define('AUTOPARTS_CUSTOMIZE_PRIORITY', 200);		// Start priority for the new controls

if (!function_exists('autoparts_customizer_register_controls')) {
	add_action( 'customize_register', 'autoparts_customizer_register_controls', 11 );
	function autoparts_customizer_register_controls( $wp_customize ) {

		// Setup standard WP Controls
		// ---------------------------------
		
		// Remove unused sections
		$wp_customize->remove_section( 'colors');
		$wp_customize->remove_section( 'static_front_page');

		// Reorder standard WP sections
		$sec = $wp_customize->get_panel( 'nav_menus' );
		if (is_object($sec)) $sec->priority = 30;
		$sec = $wp_customize->get_panel( 'widgets' );
		if (is_object($sec)) $sec->priority = 40;
		$sec = $wp_customize->get_section( 'title_tagline' );
		if (is_object($sec)) $sec->priority = 50;
		$sec = $wp_customize->get_section( 'background_image' );
		if (is_object($sec)) $sec->priority = 60;
		$sec = $wp_customize->get_section( 'header_image' );
		if (is_object($sec)) $sec->priority = 80;
		$sec = $wp_customize->get_section( 'custom_css' );
		if (is_object($sec)) {
			$sec->title = '* ' . $sec->title;
			$sec->priority = 2000;
		}
		
		// Modify standard WP controls
		$sec = $wp_customize->get_control( 'blogname' );
		if (is_object($sec))
			$sec->description = esc_html__('Use "[[" and "]]" to modify style and color of parts of the text, "||" to break current line',
											'autoparts');
		$sec = $wp_customize->get_setting( 'blogname' );
		if (is_object($sec)) $sec->transport = 'postMessage';

		$sec = $wp_customize->get_setting( 'blogdescription' );
		if (is_object($sec)) $sec->transport = 'postMessage';
		
		$sec = $wp_customize->get_section( 'background_image' );
		if (is_object($sec)) {
			$sec->title = esc_html__('Background', 'autoparts');
			$sec->description = esc_html__('Used only if "Content - Body style" equal to "boxed"', 'autoparts');
		}
		
		// Move standard option 'Background Color' to the section 'Background Image'
		$wp_customize->add_setting( 'background_color', array(
			'default'        => get_theme_support( 'custom-background', 'default-color' ),
			'theme_supports' => 'custom-background',
			'transport'		 => 'postMessage',
			'sanitize_callback'    => 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' => 'maybe_hash_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'background_color', array(
			'label'   => esc_html__( 'Background color', 'autoparts' ),
			'section' => 'background_image',
		) ) );
		

		// Add Theme specific controls
		// ---------------------------------
		
		$panels = array('');
		$p = 0;
		$sections = array('');
		$s = 0;
		$i = 0;

		// Reload Theme Options before create controls
		if (is_admin()) {
			autoparts_storage_set('options_reloaded', true);
			autoparts_load_theme_options();
		}
		$options = autoparts_storage_get('options');
		
		foreach ($options as $id=>$opt) {
			
			$i++;
			
			if (!empty($opt['hidden'])) continue;
			
			if ($opt['type'] == 'panel') {

				$sec = $wp_customize->get_panel( $id );
				if ( is_object($sec) && !empty($sec->title) ) {
					$sec->title      = $opt['title'];
					$sec->description= $opt['desc'];
					if ( !empty($opt['priority']) )	$sec->priority = $opt['priority'];
				} else {
					$wp_customize->add_panel( esc_attr($id) , array(
						'title'      => $opt['title'],
						'description'=> $opt['desc'],
						'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i
					) );
				}
				array_push($panels, $id);
				$p++;

			} else if ($opt['type'] == 'panel_end') {

				array_pop($panels);
				$p--;

			} else if ($opt['type'] == 'section') {

				$sec = $wp_customize->get_section( $id );
				if ( is_object($sec) && !empty($sec->title) ) {
					$sec->title      = $opt['title'];
					$sec->description= $opt['desc'];
					if ( !empty($opt['priority']) )	$sec->priority = $opt['priority'];
				} else {
					$wp_customize->add_section( esc_attr($id) , array(
						'title'      => $opt['title'],
						'description'=> $opt['desc'],
						'panel'  => esc_attr($panels[$p]),
						'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i
					) );
				}
				array_push($sections, $id);
				$s++;

			} else if ($opt['type'] == 'section_end') {

				array_pop($sections);
				$s--;

			} else if ($opt['type'] == 'select') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'type'     => 'select',
					'choices'  => apply_filters('autoparts_filter_options_get_list_choises', $opt['options'], $id)
				) );

			} else if ($opt['type'] == 'radio') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'type'     => 'radio',
					'choices'  => apply_filters('autoparts_filter_options_get_list_choises', $opt['options'], $id)
				) );

			} else if ($opt['type'] == 'switch') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new AUTOPARTS_Customize_Switch_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'choices'  => apply_filters('autoparts_filter_options_get_list_choises', $opt['options'], $id)
				) ) );

			} else if ($opt['type'] == 'checkbox') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'type'     => 'checkbox'
				) );

			} else if ($opt['type'] == 'color') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'sanitize_hex_color',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else if ($opt['type'] == 'image') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else if (in_array($opt['type'], array('media', 'audio', 'video'))) {
				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else if ($opt['type'] == 'icon') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new AUTOPARTS_Customize_Icon_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'input_attrs' => array(
						'value' => autoparts_get_theme_option($id),
					)
				) ) );

			} else if ($opt['type'] == 'checklist') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new AUTOPARTS_Customize_Checklist_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'choices' => apply_filters('autoparts_filter_options_get_list_choises', $opt['options'], $id),
					'input_attrs' => array(
						'value' => autoparts_get_theme_option($id),
						'sortable' => !empty($opt['sortable']),
						'dir' => !empty($opt['dir']) ? $opt['dir'] : 'horizontal'
					)
				) ) );

			} else if ($opt['type'] == 'scheme_editor') {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( new AUTOPARTS_Customize_Scheme_Editor_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'input_attrs' => array(
						'value' => autoparts_get_theme_option($id),
					)
				) ) );

			} else if ($opt['type'] == 'button') {
			
				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );

				$wp_customize->add_control( new AUTOPARTS_Customize_Button_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'input_attrs' => array(
						'caption' => $opt['caption'],
						'action' => $opt['action']
					),
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else if ($opt['type'] == 'info') {
			
				$wp_customize->add_setting( $id, array(
					'default'           => '',
					'sanitize_callback' => 'autoparts_sanitize_value',
					'transport'         => 'postMessage'
				) );

				$wp_customize->add_control( new AUTOPARTS_Customize_Info_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else if ($opt['type'] == 'hidden') {
			
				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_html',
					'transport'         => 'postMessage'
				) );

				$wp_customize->add_control( new AUTOPARTS_Customize_Hidden_Control( $wp_customize, $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority' => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
				) ) );

			} else {	// if (in_array($opt['type'], array('text', 'textarea'))) {

				$wp_customize->add_setting( $id, array(
					'default'           => autoparts_get_theme_option($id),
					'sanitize_callback' => 'autoparts_sanitize_html',
					'transport'         => !isset($opt['refresh']) || $opt['refresh'] ? 'refresh' : 'postMessage'
				) );
			
				$wp_customize->add_control( $id, array(
					'label'    => $opt['title'],
					'description' => $opt['desc'],
					'section'  => esc_attr($sections[$s]),
					'priority'	 => !empty($opt['priority']) ? $opt['priority'] : AUTOPARTS_CUSTOMIZE_PRIORITY+$i,
					'type'     => $opt['type']	//'text' | 'textarea'
				) );
			}

		}
	}
}


// Create custom controls for customizer
if (!function_exists('autoparts_customizer_custom_controls')) {
	add_action( 'customize_register', 'autoparts_customizer_custom_controls' );
	function autoparts_customizer_custom_controls( $wp_customize ) {
	
		class AUTOPARTS_Customize_Info_Control extends WP_Customize_Control {
			public $type = 'info';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				?></label><?php
			}
		}
	
		class AUTOPARTS_Customize_Switch_Control extends WP_Customize_Control {
			public $type = 'switch';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				if (is_array($this->choices) && count($this->choices)>0) {
					foreach ($this->choices as $k=>$v) {
						?><label><input type="radio" name="_customize-radio-<?php echo esc_attr($this->id); ?>" <?php
										$this->link();
										?> value="<?php echo esc_attr($k); ?>">
						<?php echo esc_html($v); ?></label><?php
					}
				}
				?></label><?php
			}
		}
	
		class AUTOPARTS_Customize_Icon_Control extends WP_Customize_Control {
			public $type = 'icon';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				?><span class="customize-control-field-wrap"><input type="text" <?php $this->link(); ?> /><?php
				autoparts_show_layout(autoparts_show_custom_field('_customize-icon-selector-'.esc_attr($this->id),
															array(
																'type'	 => 'icons',
																'button' => true,
																'icons'	 => true
															),
															$this->input_attrs['value']
															)
									);
				?></span></label><?php
			}
		}
	
		class AUTOPARTS_Customize_Checklist_Control extends WP_Customize_Control {
			public $type = 'checklist';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				?><span class="customize-control-field-wrap"><input type="hidden" <?php $this->link(); ?> /><?php
				autoparts_show_layout(autoparts_show_custom_field('_customize-checklist-'.esc_attr($this->id),
															array(
																'type'	 => 'checklist',
																'options' => $this->choices,
																'sortable' => !empty($this->input_attrs['sortable']),
																'dir' => !empty($this->input_attrs['dir']) ? $this->input_attrs['dir'] : 'horizontal'
															),
															$this->input_attrs['value']
															)
									);
				?></span></label><?php
			}
		}
	
		class AUTOPARTS_Customize_Button_Control extends WP_Customize_Control {
			public $type = 'button';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				?>
				<input type="button" 
						name="_customize-button-<?php echo esc_attr($this->id); ?>" 
						value="<?php echo esc_attr($this->input_attrs['caption']); ?>"
						data-action="<?php echo esc_attr($this->input_attrs['action']); ?>">
				</label>
				<?php
			}
		}

		class AUTOPARTS_Customize_Hidden_Control extends WP_Customize_Control {
			public $type = 'info';

			public function render_content() {
				?><input type="hidden" name="_customize-hidden-<?php echo esc_attr($this->id); ?>" <?php $this->link(); ?> value=""><?php
			}
		}
	
		class AUTOPARTS_Customize_Scheme_Editor_Control extends WP_Customize_Control {
			public $type = 'scheme_editor';

			public function render_content() {
				?><label><?php
				if (!empty($this->label)) {
					?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
				}
				if (!empty($this->description)) {
					?><span class="customize-control-description desctiption"><?php autoparts_show_layout( $this->description ); ?></span><?php
				}
				?><span class="customize-control-field-wrap"><input type="hidden" <?php $this->link(); ?> /><?php
				autoparts_show_layout(autoparts_show_custom_field('_customize-scheme-editor-'.esc_attr($this->id),
															array('type' => 'scheme_editor'),
															autoparts_unserialize($this->input_attrs['value'])
															)
									);
				?></span></label><?php
			}
		}
	
	}
}


// Sanitize plain value
if (!function_exists('autoparts_sanitize_value')) {
	function autoparts_sanitize_value($value) {
		return empty($value) ? $value : trim(strip_tags($value));
	}
}


// Sanitize html value
if (!function_exists('autoparts_sanitize_html')) {
	function autoparts_sanitize_html($value) {
		return empty($value) ? $value : wp_kses_post($value);
	}
}


//--------------------------------------------------------------
// Save custom settings in CSS file
//--------------------------------------------------------------

// Save CSS with custom colors and fonts after save custom options
if (!function_exists('autoparts_customizer_action_save_after')) {
	add_action('customize_save_after', 'autoparts_customizer_action_save_after');
	function autoparts_customizer_action_save_after($api=false) {

		// Get saved settings
		$settings = $api->settings();

		// Store new schemes colors
		$schemes = autoparts_unserialize($settings['scheme_storage']->value());
		if (is_array($schemes) && count($schemes) > 0) 
			autoparts_storage_set('schemes', $schemes);

		// Store new fonts parameters
		$fonts = autoparts_get_theme_fonts();
		foreach ($fonts as $tag=>$v) {
			foreach ($v as $css_prop=>$css_value) {
				if (in_array($css_prop, array('title', 'description'))) continue;
				$fonts[$tag][$css_prop] = $settings["{$tag}_{$css_prop}"]->value();
			}
		}
		autoparts_storage_set('theme_fonts', $fonts);

		// Regenerate CSS with new colors
		autoparts_customizer_save_css();
	}
}

// Save CSS with custom colors and fonts after switch theme
if (!function_exists('autoparts_customizer_action_switch_theme')) {
	add_action('after_switch_theme', 'autoparts_customizer_action_switch_theme');
	function autoparts_customizer_action_switch_theme() {
		// Remove condition if you want regenerate css after switch to this theme
		if (false) autoparts_customizer_save_css();
	}
}

// Save CSS with custom colors and fonts into custom.css
if (!function_exists('autoparts_customizer_save_css')) {
	add_action('trx_addons_action_save_options', 'autoparts_customizer_save_css');
	function autoparts_customizer_save_css() {
		$msg = 	'/* ' . esc_html__("ATTENTION! This file was generated automatically! Don't change it!!!", 'autoparts') 
				. "\n----------------------------------------------------------------------- */\n";

		// Save CSS with custom colors and fonts into custom.css
		$css = autoparts_customizer_get_css();
		$file = autoparts_get_file_dir('css/__colors.css');
		if (file_exists($file)) autoparts_fpc($file, $msg . $css );

		// Merge stylesheets
		$list = apply_filters( 'autoparts_filter_merge_styles', array() );
		$css = '';
		foreach ($list as $f) {
			$css .= autoparts_fgc(autoparts_get_file_dir($f));
		}
		if ( $css != '') {
			autoparts_fpc( autoparts_get_file_dir('css/__styles.css'), $msg . apply_filters( 'autoparts_filter_prepare_css', $css, true ) );
		}

		// Merge scripts
		$list = apply_filters( 'autoparts_filter_merge_scripts', array(
																	'js/skip-link-focus.js',
																	'js/bideo.js',
																	'js/jquery.tubular.js',
																	'js/_utils.js',
																	'js/_init.js'
																	)
							);
		$js = '';
		foreach ($list as $f) {
			$js .= autoparts_fgc(autoparts_get_file_dir($f));
		}
		if ( $js != '') {
			autoparts_fpc( autoparts_get_file_dir('js/__scripts.js'), $msg . apply_filters( 'autoparts_filter_prepare_js', $js, true ) );
		}
	}
}


//--------------------------------------------------------------
// Border radius settings
//--------------------------------------------------------------

// Return current theme-specific border radius for form's fields and buttons
if ( !function_exists( 'autoparts_get_border_radius' ) ) {
	function autoparts_get_border_radius() {
		$rad = str_replace(' ', '', autoparts_get_theme_option('border_radius'));
		if (empty($rad)) $rad = 0;
		return autoparts_prepare_css_value($rad); 
	}
}


//--------------------------------------------------------------
// Color schemes manipulations
//--------------------------------------------------------------

// Load saved values into color schemes
if (!function_exists('autoparts_load_schemes')) {
	add_action('autoparts_action_load_options', 'autoparts_load_schemes');
	function autoparts_load_schemes() {
		$schemes = autoparts_storage_get('schemes');
		$storage = autoparts_unserialize(autoparts_get_theme_option('scheme_storage'));
		if (is_array($storage) && count($storage) > 0)  {
			foreach ($storage as $k=>$v) {
				if (isset($schemes[$k])) {
					$schemes[$k] = $v;
				}
			}
			autoparts_storage_set('schemes', $schemes);
		}
	}
}

// Return specified color from current (or specified) color scheme
if ( !function_exists( 'autoparts_get_scheme_color' ) ) {
	function autoparts_get_scheme_color($color_name, $scheme = '') {
		if (empty($scheme)) $scheme = autoparts_get_theme_option( 'color_scheme' );
		if (empty($scheme) || autoparts_storage_empty('schemes', $scheme)) $scheme = 'default';
		$colors = autoparts_storage_get_array('schemes', $scheme, 'colors');
		return $colors[$color_name];
	}
}

// Return colors from current color scheme
if ( !function_exists( 'autoparts_get_scheme_colors' ) ) {
	function autoparts_get_scheme_colors($scheme = '') {
		if (empty($scheme)) $scheme = autoparts_get_theme_option( 'color_scheme' );
		if (empty($scheme) || autoparts_storage_empty('schemes', $scheme)) $scheme = 'default';
		return autoparts_storage_get_array('schemes', $scheme, 'colors');
	}
}

// Return colors from all schemes
if ( !function_exists( 'autoparts_get_scheme_storage' ) ) {
	function autoparts_get_scheme_storage($scheme = '') {
		return serialize(autoparts_storage_get('schemes'));
	}
}


// Return schemes list
if ( !function_exists( 'autoparts_get_list_schemes' ) ) {
	function autoparts_get_list_schemes($prepend_inherit=false) {
		$list = array();
		$schemes = autoparts_storage_get('schemes');
		if (is_array($schemes) && count($schemes) > 0) {
			foreach ($schemes as $slug => $scheme) {
				$list[$slug] = $scheme['title'];
			}
		}
		return $prepend_inherit ? autoparts_array_merge(array('inherit' => esc_html__("Inherit", 'autoparts')), $list) : $list;
	}
}


//--------------------------------------------------------------
// Theme fonts
//--------------------------------------------------------------

// Load saved values into fonts list
if (!function_exists('autoparts_load_fonts')) {
	add_action('autoparts_action_load_options', 'autoparts_load_fonts');
	function autoparts_load_fonts() {
		// Fonts to load when theme starts
		$fonts = array();
		for ($i=1; $i<=autoparts_get_theme_setting('max_load_fonts'); $i++) {
			if (($name = autoparts_get_theme_option("load_fonts-{$i}-name")) != '') {
				$fonts[] = array(
					'name'	 => $name,
					'family' => autoparts_get_theme_option("load_fonts-{$i}-family"),
					'styles' => autoparts_get_theme_option("load_fonts-{$i}-styles")
				);
			}
		}
		autoparts_storage_set('load_fonts', $fonts);
		autoparts_storage_set('load_fonts_subset', autoparts_get_theme_option("load_fonts_subset"));
		
		// Font parameters of the main theme's elements
		$fonts = autoparts_get_theme_fonts();
		foreach ($fonts as $tag=>$v) {
			foreach ($v as $css_prop=>$css_value) {
				if (in_array($css_prop, array('title', 'description'))) continue;
				$fonts[$tag][$css_prop] = autoparts_get_theme_option("{$tag}_{$css_prop}");
			}
		}
		autoparts_storage_set('theme_fonts', $fonts);
	}
}

// Return slug of the loaded font
if (!function_exists('autoparts_get_load_fonts_slug')) {
	function autoparts_get_load_fonts_slug($name) {
		return str_replace(' ', '-', $name);
	}
}

// Return load fonts parameter's default value
if (!function_exists('autoparts_get_load_fonts_option')) {
	function autoparts_get_load_fonts_option($option_name) {
		$rez = '';
		$parts = explode('-', $option_name);
		$load_fonts = autoparts_storage_get('load_fonts');
		if ($parts[0] == 'load_fonts' && count($load_fonts) > $parts[1]-1 && isset($load_fonts[$parts[1]-1][$parts[2]])) {
			$rez = $load_fonts[$parts[1]-1][$parts[2]];
		}
		return $rez;
	}
}

// Return load fonts subset's default value
if (!function_exists('autoparts_get_load_fonts_subset')) {
	function autoparts_get_load_fonts_subset($option_name) {
		return autoparts_storage_get('load_fonts_subset');
	}
}

// Return load fonts list
if (!function_exists('autoparts_get_list_load_fonts')) {
	function autoparts_get_list_load_fonts($prepend_inherit=false) {
		$list = array();
		$load_fonts = autoparts_storage_get('load_fonts');
		if (is_array($load_fonts) && count($load_fonts) > 0) {
			foreach ($load_fonts as $font) {
				$list[sprintf('%s%s', 
								strpos($font['name'], ' ')!==false ? sprintf('"%s"', $font['name']) : $font['name'],
								!empty($font['family']) ? ', '.trim($font['family']): '')] = $font['name'];
			}
		}
		return $prepend_inherit ? autoparts_array_merge(array('inherit' => esc_html__("Inherit", 'autoparts')), $list) : $list;
	}
}

// Return font settings of the theme specific elements
if ( !function_exists( 'autoparts_get_theme_fonts' ) ) {
	function autoparts_get_theme_fonts() {
		return autoparts_storage_get('theme_fonts');
	}
}

// Return theme fonts parameter's default value
if (!function_exists('autoparts_get_theme_fonts_option')) {
	function autoparts_get_theme_fonts_option($option_name) {
		$rez = '';
		$parts = explode('_', $option_name);
		$theme_fonts = autoparts_storage_get('theme_fonts');
		if (!empty($theme_fonts[$parts[0]][$parts[1]])) {
			$rez = $theme_fonts[$parts[0]][$parts[1]];
		}
		// For the font-families update options list also
		if ($parts[1] == 'font-family') {
			autoparts_storage_set_array2('options', $option_name, 'options', autoparts_get_list_load_fonts(true));
		}
		return $rez;
	}
}


//--------------------------------------------------------------
// Customizer JS and CSS
//--------------------------------------------------------------

// Binds JS listener to make Customizer color_scheme control.
// Passes color scheme data as colorScheme global.
if ( !function_exists( 'autoparts_customizer_control_js' ) ) {
	add_action( 'customize_controls_enqueue_scripts', 'autoparts_customizer_control_js' );
	function autoparts_customizer_control_js() {
		wp_enqueue_style( 'autoparts-customizer', autoparts_get_file_url('theme-options/theme.customizer.css') );
		wp_enqueue_script( 'autoparts-customizer-color-scheme-control',
									autoparts_get_file_url('theme-options/theme.customizer.color-scheme.js'),
									array( 'customize-controls', 'iris', 'underscore', 'wp-util' ), null, true );
		wp_localize_script( 'autoparts-customizer-color-scheme-control', 'autoparts_color_schemes', autoparts_storage_get('schemes') );
		wp_localize_script( 'autoparts-customizer-color-scheme-control', 'autoparts_theme_fonts', autoparts_storage_get('theme_fonts') );
		wp_localize_script( 'autoparts-customizer-color-scheme-control', 'autoparts_customizer_vars', array(
			'max_load_fonts' => autoparts_get_theme_setting('max_load_fonts'),
			'msg_refresh' => esc_html__('Refresh', 'autoparts'),
			'msg_reset' => esc_html__('Reset', 'autoparts'),
			'msg_reset_confirm' => esc_html__('Are you sure you want to reset all Theme Options?', 'autoparts'),
			) );
		wp_localize_script( 'autoparts-customizer-color-scheme-control', 'autoparts_dependencies', autoparts_get_theme_dependencies() );
	}
}

// Binds JS handlers to make the Customizer preview reload changes asynchronously.
if ( !function_exists( 'autoparts_customizer_preview_js' ) ) {
	add_action( 'customize_preview_init', 'autoparts_customizer_preview_js' );
	function autoparts_customizer_preview_js() {
		wp_enqueue_script( 'autoparts-customize-preview',
							autoparts_get_file_url('theme-options/theme.customizer.preview.js'), 
							array( 'customize-preview' ), null, true );
	}
}

// Output an Underscore template for generating CSS for the color scheme.
// The template generates the css dynamically for instant display in the Customizer preview.
if ( !function_exists( 'autoparts_customizer_css_template' ) ) {
	add_action( 'customize_controls_print_footer_scripts', 'autoparts_customizer_css_template' );
	function autoparts_customizer_css_template() {
		$colors = array();
		foreach (autoparts_get_scheme_colors() as $k=>$v)
			$colors[$k] = '{{ data.'.esc_attr($k).' }}';

		$tmpl_holder = 'script';

		$schemes = array_keys(autoparts_get_list_schemes());
		if (count($schemes) > 0) {
			foreach ($schemes as $scheme) {
				echo '<' . esc_html($tmpl_holder) . ' type="text/html" id="tmpl-autoparts-color-scheme-'.esc_attr($scheme).'">'
						. autoparts_customizer_get_css( $colors, false, false, $scheme )
					. '</' . esc_html($tmpl_holder) . '>';
			}
		}


		// Fonts
		$fonts = autoparts_get_theme_fonts();
		if (is_array($fonts) && count($fonts) > 0) {
			foreach ($fonts as $tag => $font) {
				$fonts[$tag]['font-family']		= '{{ data["'.$tag.'"]["font-family"] }}';
				$fonts[$tag]['font-size']		= '{{ data["'.$tag.'"]["font-size"] }}';
				$fonts[$tag]['line-height']		= '{{ data["'.$tag.'"]["line-height"] }}';
				$fonts[$tag]['font-weight']		= '{{ data["'.$tag.'"]["font-weight"] }}';
				$fonts[$tag]['font-style']		= '{{ data["'.$tag.'"]["font-style"] }}';
				$fonts[$tag]['text-decoration']	= '{{ data["'.$tag.'"]["text-decoration"] }}';
				$fonts[$tag]['text-transform']	= '{{ data["'.$tag.'"]["text-transform"] }}';
				$fonts[$tag]['letter-spacing']	= '{{ data["'.$tag.'"]["letter-spacing"] }}';
				$fonts[$tag]['margin-top']		= '{{ data["'.$tag.'"]["margin-top"] }}';
				$fonts[$tag]['margin-bottom']	= '{{ data["'.$tag.'"]["margin-bottom"] }}';
			}
			echo '<'.trim($tmpl_holder).' type="text/html" id="tmpl-autoparts-fonts">'
					. trim(autoparts_customizer_get_css( false, $fonts, false, false ))
				. '</'.trim($tmpl_holder).'>';
		}

	}
}


// Add scheme name in each selector in the CSS (priority 100 - after complete css)
if (!function_exists('autoparts_customizer_add_scheme_in_css')) {
	add_action( 'autoparts_filter_get_css', 'autoparts_customizer_add_scheme_in_css', 100, 4 );
	function autoparts_customizer_add_scheme_in_css($css, $colors, $fonts, $scheme) {
		if ($colors && !empty($css['colors'])) {
			$rez = '';
			$in_comment = $in_rule = false;
			$allow = true;
			$scheme_class = sprintf('.scheme_%s ', $scheme);
			$self_class = '.scheme_self';
			$self_class_len = strlen($self_class);
			$css_str = str_replace(array('{{', '}}'), array('[[',']]'), $css['colors']);
			for ($i=0; $i<strlen($css_str); $i++) {
				$ch = $css_str[$i];
				if ($in_comment) {
					$rez .= $ch;
					if ($ch=='/' && $css_str[$i-1]=='*') {
						$in_comment = false;
						$allow = !$in_rule;
					}
				} else if ($in_rule) {
					$rez .= $ch;
					if ($ch=='}') {
						$in_rule = false;
						$allow = !$in_comment;
					}
				} else {
					if ($ch=='/' && $css_str[$i+1]=='*') {
						$rez .= $ch;
						$in_comment = true;
					} else if ($ch=='{') {
						$rez .= $ch;
						$in_rule = true;
					} else if ($ch==',') {
						$rez .= $ch;
						$allow = true;
					} else if (strpos(" \t\r\n", $ch)===false) {
						if ($allow) {
							$pos_comma = strpos($css_str, ',', $i+1);
							$pos_bracket = strpos($css_str, '{', $i+1);
							$pos = $pos_comma === false
										? $pos_bracket
										: ($pos_bracket === false
												? $pos_comma
												: min($pos_comma, $pos_bracket)
											);
							$selector = $pos > 0 ? substr($css_str, $i, $pos-$i) : '';
							if (strpos($selector, $self_class) !== false) {
								$rez .= str_replace($self_class, trim($scheme_class), $selector);
								$i += strlen($selector) - 1;
							} else {
								$rez .= $scheme_class . trim($ch);
							}
							$allow = false;
						} else
							$rez .= $ch;
					} else {
						$rez .= $ch;
					}
				}
			}
			$rez = str_replace(array('[[',']]'), array('{{', '}}'), $rez);
			$css['colors'] = $rez;
		}
		return $css;
	}
}
	



// -----------------------------------------------------------------
// -- Page Options section
// -----------------------------------------------------------------

if ( !function_exists('autoparts_init_meta_box') ) {
	add_action( 'after_setup_theme', 'autoparts_init_meta_box' );
	function autoparts_init_meta_box() {
		if ( is_admin() ) {
			add_action("admin_enqueue_scripts", 'autoparts_add_meta_box_scripts');
			add_action('save_post',			'autoparts_save_meta_box');
			add_action('add_meta_boxes',	'autoparts_add_meta_box');
		}
	}
}
	
// Load required styles and scripts for admin mode
if ( !function_exists( 'autoparts_add_meta_box_scripts' ) ) {
	add_action("admin_enqueue_scripts", 'autoparts_add_meta_box_scripts');
	function autoparts_add_meta_box_scripts() {
		// If current screen is 'Edit Page' - load fontello
		$screen = function_exists('get_current_screen') ? get_current_screen() : false;
		if (is_object($screen) && autoparts_allow_meta_box(!empty($screen->post_type) ? $screen->post_type : $screen->id)) {
			wp_enqueue_style( 'autoparts-fontello',  autoparts_get_file_url('css/fontello/fontello-embedded.css') );
			wp_enqueue_script('jquery-ui-tabs', false, array('jquery', 'jquery-ui'), null, true);
			wp_enqueue_script( 'autoparts-meta-box', autoparts_get_file_url('theme-options/theme.meta-box.js'), array('jquery'), null, true );
			wp_localize_script( 'autoparts-meta-box', 'autoparts_dependencies', autoparts_get_theme_dependencies() );
		}
	}
}


// Check if meta box is allow
if (!function_exists('autoparts_allow_meta_box')) {
	function autoparts_allow_meta_box($post_type) {
		return apply_filters('autoparts_filter_allow_meta_box', in_array($post_type, array('page', 'post')), $post_type);
	}
}

// Add meta box
if (!function_exists('autoparts_add_meta_box')) {
	//Handler of the add_action('add_meta_boxes', 'autoparts_add_meta_box');
	function autoparts_add_meta_box() {
		global $post_type;
		if (autoparts_allow_meta_box($post_type)) {
			add_meta_box(sprintf('autoparts_meta_box_%s', $post_type), 
						esc_html__('Theme Options', 'autoparts'),
						'autoparts_show_meta_box',
						$post_type,
						$post_type=='post' ? 'side' : 'advanced',
						'default');
		}
	}
}

// Callback function to show fields in meta box
if (!function_exists('autoparts_show_meta_box')) {
	function autoparts_show_meta_box() {
		global $post, $post_type;
		if (autoparts_allow_meta_box($post_type)) {
			// Load saved options 
			$meta = get_post_meta($post->ID, 'autoparts_options', true);
			$tabs_titles = $tabs_content = array();
			global $AUTOPARTS_STORAGE;
			// Refresh linked data if this field is controller for the another (linked) field
			// Do this before show fields to refresh data in the $AUTOPARTS_STORAGE
			foreach ($AUTOPARTS_STORAGE['options'] as $k=>$v) {
				if (!isset($v['override']) || strpos($v['override']['mode'], $post_type)===false) continue;
				if (!empty($v['linked'])) {
					$v['val'] = isset($meta[$k]) ? $meta[$k] : 'inherit';
					if (!empty($v['val']) && !autoparts_is_inherit($v['val']))
						autoparts_refresh_linked_data($v['val'], $v['linked']);
				}
			}
			// Show fields
			foreach ($AUTOPARTS_STORAGE['options'] as $k=>$v) {
				if (!isset($v['override']) || strpos($v['override']['mode'], $post_type)===false) continue;
				if (empty($v['override']['section']))
					$v['override']['section'] = esc_html__('General', 'autoparts');
				if (!isset($tabs_titles[$v['override']['section']])) {
					$tabs_titles[$v['override']['section']] = $v['override']['section'];
					$tabs_content[$v['override']['section']] = '';
				}
				$v['val'] = isset($meta[$k]) ? $meta[$k] : 'inherit';
				$tabs_content[$v['override']['section']] .= autoparts_show_meta_box_field($k, $v);
			}
			if (count($tabs_titles) > 0) {
				?>
				<div class="autoparts_meta_box">
					<input type="hidden" name="meta_box_post_nonce" value="<?php echo esc_attr(wp_create_nonce(admin_url())); ?>" />
					<input type="hidden" name="meta_box_post_type" value="<?php echo esc_attr($post_type); ?>" />
					<div id="autoparts_meta_box_tabs">
						<ul><?php
							$cnt = 0;
							foreach ($tabs_titles as $k=>$v) {
								$cnt++;
								?><li><a href="#autoparts_meta_box_<?php echo esc_attr($cnt); ?>"><?php echo esc_html($v); ?></a></li><?php
							}
						?></ul>
						<?php
							$cnt = 0;
							foreach ($tabs_content as $k=>$v) {
								$cnt++;
								?>
								<div id="autoparts_meta_box_<?php echo esc_attr($cnt); ?>" class="autoparts_meta_box_section">
									<?php autoparts_show_layout($v); ?>
								</div>
								<?php
							}
						?>
					</div>
				</div>
				<?php		
			}
		}
	}
}

// Display single option's field
if ( !function_exists('autoparts_show_meta_box_field') ) {
	function autoparts_show_meta_box_field($name, $field) {

		if ($field['type'] == 'hidden') return '';

		$inherit_state = autoparts_is_inherit($field['val']);
		$output = '<div class="autoparts_meta_box_item autoparts_meta_box_item_'.esc_attr($field['type'])
								. ' autoparts_meta_box_inherit_'.($inherit_state ? 'on' : 'off' )
								. '">'
						. '<h4 class="autoparts_meta_box_item_title">'
							. esc_html($field['title'])
							. '<span class="autoparts_meta_box_inherit_lock" id="autoparts_meta_box_inherit_'.esc_attr($name).'"></span>'
						. '</h4>'
						. '<div class="autoparts_meta_box_item_data">'
							. '<div class="autoparts_meta_box_item_field" data-param="'.esc_attr($name).'"'
									. (!empty($field['linked']) ? ' data-linked="'.esc_attr($field['linked']).'"' : '')
									. '>';
	
		// Type 'checkbox'
		if ($field['type']=='checkbox') {
			$output .= '<label class="autoparts_meta_box_item_label">'
						. '<input type="checkbox" name="autoparts_meta_box_field_'.esc_attr($name).'" value="1"'
								.($field['val']==1 ? ' checked="checked"' : '')
								.' />'
						. esc_html($field['title'])
					. '</label>';
		
		// Type 'switch' (2 choises) or 'radio' (3+ choises)
		} else if (in_array($field['type'], array('switch', 'radio'))) {
			$field['options'] = apply_filters('autoparts_filter_options_get_list_choises', $field['options'], $name);
			foreach ($field['options'] as $k=>$v) {
				$output .= '<label class="autoparts_meta_box_item_label">'
							. '<input type="radio" name="autoparts_meta_box_field_'.esc_attr($name).'"'
									. ' value="'.esc_attr($k).'"'.($field['val']==$k ? ' checked="checked"' : '')
									. ' />'
							. esc_html($v)
						. '</label>';
			}

		// Type 'text' or 'time' or 'date'
		} else if (in_array($field['type'], array('text', 'time', 'date'))) {
			$output .= '<input type="text" name="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' value="'.esc_attr(autoparts_is_inherit($field['val']) ? '' : $field['val']).'"'
							. ' />';
		
		// Type 'textarea'
		} else if ($field['type']=='textarea') {
			$output .= '<textarea name="autoparts_meta_box_field_'.esc_attr($name).'">'
							. esc_html(autoparts_is_inherit($field['val']) ? '' : $field['val'])
						. '</textarea>';
			
		// Type 'select'
		} else if ($field['type']=='select') {
			$field['options'] = apply_filters('autoparts_filter_options_get_list_choises', $field['options'], $name);
			$output .= '<select size="1" name="autoparts_meta_box_field_'.esc_attr($name).'">';
			foreach ($field['options'] as $k=>$v) {
				$output .= '<option value="'.esc_attr($k).'"'.($field['val']==$k ? ' selected="selected"' : '').'>'.esc_html($v).'</option>';
			}
			$output .= '</select>';

		// Type 'image', 'media', 'video' or 'audio'
		} else if (in_array($field['type'], array('image', 'media', 'video', 'audio'))) {
			$output .= (!empty($field['multiple'])
						? '<input type="hidden" id="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' name="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' value="'.esc_attr(autoparts_is_inherit($field['val']) ? '' : $field['val']).'"'
							. ' />'
						: '<input type="text" id="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' name="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' value="'.esc_attr(autoparts_is_inherit($field['val']) ? '' : $field['val']).'"'
							. ' />')
					. autoparts_show_custom_field('autoparts_meta_box_field_'.esc_attr($name).'_button',
												array(
													'type'			 => 'mediamanager',
													'multiple'		 => !empty($field['multiple']),
													'data_type'		 => $field['type'],
													'linked_field_id'=> 'autoparts_meta_box_field_'.esc_attr($name)
												),
												autoparts_is_inherit($field['val']) ? '' : $field['val']);
		
		// Type 'icon'
		} else if ($field['type']=='icon') {
			$output .= '<input type="text" id="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' name="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' value="'.esc_attr(autoparts_is_inherit($field['val']) ? '' : $field['val']).'"'
							. ' />'
						. autoparts_show_custom_field('autoparts_meta_box_field_'.esc_attr($name).'_button',
													array(
														'type'	 => 'icons',
														'button' => true,
														'icons'	 => true
													),
													$field['val']);
		
		// Type 'checklist'
		} else if ($field['type']=='checklist') {
			$output .= '<input type="hidden" id="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' name="autoparts_meta_box_field_'.esc_attr($name).'"'
							. ' value="'.esc_attr(autoparts_is_inherit($field['val']) ? '' : $field['val']).'"'
							. ' />'
						. autoparts_show_custom_field('autoparts_meta_box_field_'.esc_attr($name).'_list',
													array(
														'type'	 => 'checklist',
														'options' => $field['options'],
														'sortable' => !empty($field['sortable']),
														'dir' => !empty($field['dir']) ? $field['dir'] : 'horizontal'
													),
													$field['val']);
		}
		
		$output .= '<div class="autoparts_meta_box_inherit_cover'.(!$inherit_state ? ' autoparts_hidden' : '').'">'
							. '<span class="autoparts_meta_box_inherit_label">' . esc_html__('Inherit', 'autoparts') . '</span>'
							. '<input type="hidden" name="autoparts_meta_box_inherit_'.esc_attr($name).'"'
									. ' value="'.esc_attr($inherit_state ? 'inherit' : '').'"'
									. ' />'
						. '</div>'
					. '</div>'
					. '<div class="autoparts_meta_box_item_description">'
						. (!empty($field['override']['desc']) 	// param 'desc' already processed with wp_kses()!
								? $field['override']['desc'] 
								: $field['desc'])
					. '</div>'
				. '</div>'
			. '</div>';
		return $output;
	}
}

// Save data from meta box
if (!function_exists('autoparts_save_meta_box')) {
	//Handler of the add_action('save_post', 'autoparts_save_meta_box');
	function autoparts_save_meta_box($post_id) {

		// verify nonce
		if ( !wp_verify_nonce( autoparts_get_value_gp('meta_box_post_nonce'), admin_url() ) )
			return $post_id;

		// check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		$post_type = isset($_POST['meta_box_post_type']) ? $_POST['meta_box_post_type'] : $_POST['post_type'];

		// check permissions
		$capability = 'page';
		$post_types = get_post_types( array( 'name' => $post_type), 'objects' );
		if (!empty($post_types) && is_array($post_types)) {
			foreach ($post_types  as $type) {
				$capability = $type->capability_type;
				break;
			}
		}
		if (!current_user_can('edit_'.($capability), $post_id)) {
			return $post_id;
		}

		// Save meta
		$meta = array();
		$options = autoparts_storage_get('options');
		foreach ($options as $k=>$v) {
			// Skip not overriden options
			if (!isset($v['override']) || strpos($v['override']['mode'], $post_type)===false) continue;
			// Skip inherited options
			if (!empty($_POST['autoparts_meta_box_inherit_' . $k])) continue;
			// Get option value from POST
			$meta[$k] = isset($_POST['autoparts_meta_box_field_' . $k])
							? $_POST['autoparts_meta_box_field_' . $k]
							: ($v['type']=='checkbox' ? 0 : '');
		}
		update_post_meta($post_id, 'autoparts_options', $meta);
		
		// Save separate meta options to search template pages
		if ($post_type=='page' && !empty($_POST['page_template']) && $_POST['page_template']=='blog.php') {
			update_post_meta($post_id, 'autoparts_options_post_type', isset($meta['post_type']) ? $meta['post_type'] : 'post');
			update_post_meta($post_id, 'autoparts_options_parent_cat', isset($meta['parent_cat']) ? $meta['parent_cat'] : 0);
		}
	}
}

// Refresh data in the linked field
// according the main field value
if (!function_exists('autoparts_refresh_linked_data')) {
	function autoparts_refresh_linked_data($value, $linked_name) {
		if ($linked_name == 'parent_cat') {
			$tax = autoparts_get_post_type_taxonomy($value);
			$terms = !empty($tax) ? autoparts_get_list_terms(false, $tax) : array();
			$terms = autoparts_array_merge(array(0 => esc_html__('- Select category -', 'autoparts')), $terms);
			autoparts_storage_set_array2('options', $linked_name, 'options', $terms);
		}
	}
}

// AJAX: Refresh data in the linked fields
if (!function_exists('autoparts_callback_get_linked_data')) {
	add_action('wp_ajax_autoparts_get_linked_data', 		'autoparts_callback_get_linked_data');
	add_action('wp_ajax_nopriv_autoparts_get_linked_data','autoparts_callback_get_linked_data');
	function autoparts_callback_get_linked_data() {
		if ( !wp_verify_nonce( autoparts_get_value_gp('nonce'), admin_url('admin-ajax.php') ) )
			die();
		$chg_name = $_REQUEST['chg_name'];
		$chg_value = $_REQUEST['chg_value'];
		$response = array('error' => '');
		if ($chg_name == 'post_type') {
			$tax = autoparts_get_post_type_taxonomy($chg_value);
			$terms = !empty($tax) ? autoparts_get_list_terms(false, $tax) : array();
			$response['list'] = autoparts_array_merge(array(0 => esc_html__('- Select category -', 'autoparts')), $terms);
		}
		echo json_encode($response);
		die();
	}
}

// Show theme specific fields in the meta box
function autoparts_show_custom_field($id, $field, $value) {
	$output = '';
	switch ($field['type']) {
		
		case 'mediamanager':
			wp_enqueue_media( );
			$title = empty($field['data_type']) || $field['data_type']=='image'
							? esc_html__( 'Choose Image', 'autoparts')
							: esc_html__( 'Choose Media', 'autoparts');
			$output .= '<a id="'.esc_attr($id).'"'
							. ' class="button mediamanager autoparts_media_selector"'
							. '	data-param="' . esc_attr($id) . '"'
							. '	data-choose="'.esc_attr(!empty($field['multiple']) ? esc_html__( 'Choose Images', 'autoparts') : $title).'"'
							. ' data-update="'.esc_attr(!empty($field['multiple']) ? esc_html__( 'Add to Gallery', 'autoparts') : $title).'"'
							. '	data-multiple="'.esc_attr(!empty($field['multiple']) ? '1' : '0').'"'
							. '	data-type="'.esc_attr(!empty($field['data_type']) ? $field['data_type'] : 'image').'"'
							. '	data-linked-field="'.esc_attr($field['linked_field_id']).'"'
							. '>'
							. (!empty($field['multiple'])
									? (empty($field['data_type']) || $field['data_type']=='image'
										? esc_html__( 'Add Images', 'autoparts')
										: esc_html__( 'Add Files', 'autoparts')
										)
									: esc_html($title)
								)
							. '</a>';
			$output .= '<span class="autoparts_meta_box_field_preview">';
			$images = explode('|', $value);
			if (is_array($images)) {
				foreach ($images as $img)
					$output .= $img && !autoparts_is_inherit($img)
							? '<span>'
									. (in_array(autoparts_get_file_ext($img), array('gif', 'jpg', 'jpeg', 'png'))
											? '<img src="' . esc_url($img) . '" alt="">'
											: '<a href="' . esc_attr($img) . '">' . esc_html(basename($img)) . '</a>'
										)
								. '</span>' 
							: '';
			}
			$output .= '</span>';
			break;

		case 'icons':
			$icons_type = !empty($field['style']) 
							? $field['style'] 
							: autoparts_get_theme_setting('icons_type');
			if (empty($field['return']))
				$field['return'] = 'full';
			$autoparts_icons = $icons_type=='images'
								? autoparts_get_list_images()
								: autoparts_array_from_list(autoparts_get_list_icons());
			if (is_array($autoparts_icons)) {
				if (!empty($field['button']))
					$output .= '<span id="'.esc_attr($id).'"'
									. ' class="autoparts_list_icons_selector'
											. ($icons_type=='icons' && !empty($value) ? ' '.esc_attr($value) : '')
											.'"'
									. ' title="'.esc_attr__('Select icon', 'autoparts').'"'
									. ' data-style="'.($icons_type=='images' ? 'images' : 'icons').'"'
									. ($icons_type=='images' && !empty($value) 
										? ' style="background-image: url('.esc_url($field['return']=='slug' 
																							? $autoparts_icons[$value] 
																							: $value).');"' 
											: '')
								. '></span>';
				if (!empty($field['icons'])) {
					$output .= '<div class="autoparts_list_icons">';
					foreach($autoparts_icons as $slug=>$icon) {
						$output .= '<span class="'.esc_attr($icons_type=='icons' ? $icon : $slug)
								. (($field['return']=='full' ? $icon : $slug) == $value ? ' autoparts_list_active' : '')
								. '"'
								. ' title="'.esc_attr($slug).'"'
								. ' data-icon="'.esc_attr($field['return']=='full' ? $icon : $slug).'"'
								. ($icons_type=='images' ? ' style="background-image: url('.esc_url($icon).');"' : '')
								. '></span>';
					}
					$output .= '</div>';
				}
			}
			break;

		case 'checklist':
			if (!empty($field['sortable']))
				wp_enqueue_script('jquery-ui-sortable', false, array('jquery', 'jquery-ui-core'), null, true);
			$output .= '<div class="autoparts_checklist autoparts_checklist_'.esc_attr($field['dir'])
						. (!empty($field['sortable']) ? ' autoparts_sortable' : '') 
						. '">';
			if (!is_array($value)) {
				if (!empty($value) && !autoparts_is_inherit($value)) parse_str(str_replace('|', '&', $value), $value);
				else $value = array();
			}
			// Sort options by values order
			if (!empty($field['sortable']) && is_array($value)) {
				$field['options'] = autoparts_array_merge($value, $field['options']);
			}
			foreach ($field['options'] as $k=>$v) {
				$output .= '<label class="autoparts_checklist_item_label' 
								. (!empty($field['sortable']) ? ' autoparts_sortable_item' : '') 
								. '">'
							. '<input type="checkbox" value="1" data-name="'.$k.'"'
								.( isset($value[$k]) && (int) $value[$k] == 1 ? ' checked="checked"' : '')
								.' />'
							. (substr($v, 0, 4)=='http' ? '<img src="'.esc_url($v).'">' : esc_html($v))
						. '</label>';
			}
			$output .= '</div>';
			break;
			
		case 'scheme_editor':
			if (!is_array($value)) break;
			$output .= '<select class="autoparts_scheme_editor_selector">';
			foreach ($value as $scheme=>$v)
				$output .= '<option value="' . esc_attr($scheme) . '">' . esc_html($v['title']) . '</option>';
			$output .= '</select>';
			$groups = autoparts_storage_get('scheme_color_groups');
			$colors = autoparts_storage_get('scheme_color_names');
			$output .= '<div class="autoparts_scheme_editor_colors">';
			foreach ($value as $scheme=>$v) {
				$output .= '<div class="autoparts_scheme_editor_header">'
								. '<span class="autoparts_scheme_editor_header_cell"></span>';
				foreach ($groups as $group_name=>$group_data) {
					$output .= '<span class="autoparts_scheme_editor_header_cell" title="'.esc_html($group_data['description']).'">' 
								. esc_html($group_data['title'])
								. '</span>';
				}
				$output .= '</div>';
				foreach ($colors as $color_name=>$color_data) {
					$output .= '<div class="autoparts_scheme_editor_row">'
								. '<span class="autoparts_scheme_editor_row_cell" title="'.esc_html($color_data['description']).'">'
								. esc_html($color_data['title'])
								. '</span>';
					foreach ($groups as $group_name=>$group_data) {
						$slug = $group_name == 'main' 
									? $color_name 
									: str_replace('text_', '', "{$group_name}_{$color_name}");
						$output .= '<span class="autoparts_scheme_editor_row_cell">'
									. (isset($v['colors'][$slug])
										? "<input type=\"text\" name=\"{$slug}\" class=\"iColorPicker\" value=\"".esc_attr($v['colors'][$slug])."\">"
										: ''
										)
									. '</span>';
					}
					$output .= '</div>';
				}
				break;
			}
			break;
	}
	return apply_filters('autoparts_filter_show_custom_field', $output, $id, $field, $value);
}



//--------------------------------------------------------------
//-- Load Options list and styles
//--------------------------------------------------------------
require_once AUTOPARTS_THEME_DIR . 'theme-specific/theme.setup.php';
require_once AUTOPARTS_THEME_DIR . 'theme-options/theme.options.php';
require_once AUTOPARTS_THEME_DIR . 'theme-specific/theme.styles.php';
?>