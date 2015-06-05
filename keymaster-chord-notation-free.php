<?php
/*
	Plugin Name: Keymaster Chord Notation Free
	Plugin URI: http://codecanyon.net/item/keymaster-chord-notation/10991332?ref=intelligent-design
	Description: Easily add chords and lyrics to any WordPress website using shortcodes!  Users can print just the content you choose (the lyrics and chords) instead of a bunch of useless HTML. Admins can choose multiple layout options and the premium version has a ton more features like transposing to any key and unlimited colors.
	Text Domain: keymaster-chord-notation-free
	Author: George Rood
	Version: 1.0.2
	Author URI: http://intelligentdesignbuffalo.com
	Demo URI: http://childrensbiblesongs.us/2011/09/oh-how-i-love-jesus/
	License: GPLv2 or later

*/


if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly!
}

require_once(plugin_dir_path(__FILE__).'/inc_php/KCN_Free_Widget.class.php');

class Keymaster_Chord_Notation_Free {
    
	/**
	 *
	 * @var string path to plugin dir
	 */
	private $path;
	/**
	 *
	 * @var string url to plugin page on wp.org 
	 */
	private $wp_plugin_page;
	/**
	 *
	 * @var string url to plugin page
	 */
	private $kcn_plugin_page;
	/**
	 *
	 * @var string friendly name of this plugin for re-use throughout
	 */
	private $kcn_plugin_name;
	/**
	 *
	 * @var string slug name of this plugin for re-use throughout
	 */
	private $kcn_plugin_slug;
	/**
	 *
	 * @var string reference name of the plugin for re-use throughout
	 */
	public $kcn_plugin_ref;

	/**
	 *
	 * @var array 
	 */
	private $options;

	/**
	 *
	 * @var array top,bottom
	 */
	private $content_buttons_locations = array();
	/**
	 *
	 * @var array show_hide, transpose_down, transpose_fs, transpose_up, print
	 */
	private $buttons = array();
	/**
	 *
	 * @var array xs,sm,md,lg
	 */
	private $button_sizes = array();
	
	/**
	 *
	 * @var string Used to identify the options page
	 */
	private $hook_suffix;
	
	/**
	 *
	 * @var bool
	 */
	private $showed_script;
	/**
	 * This runs when the plugin is activated and adds 2 option entries
	 */
	public static function install() {
	    
	    $inst = new self;
	    
	    // All the main options are serialized by WordPress
	    add_option("$inst->kcn_plugin_ref-options",array(
		'print_selector'=>'.KCN-content',
		'chord_border_radius'=>'30',
		'chord_margin_top'=>'24',
		'chord_padding_vertical'=>'0',
		'chord_padding_horizontal'=>'3',
		'chord_position_top'=>'18',
		'chord_position_left'=>'7',
		'chord_minimum_width'=>'23',
		'chord_font_size'=>'13',
		'vertical_button_spacing'=>'6',
		'button_layout'=>'horizontal',
		'button_size'=>'xs',
		'hide_buttons'=>array(),
		'content_buttons'=>array('top'=>'on'),
		'auto_raise'=>array('auto_raise'=>'on'),
		'chords_visible_by_default'=>array('chords_visible_by_default'=>'on')		
		));

	    
	}
	
	public function __construct(){		
	    
		$this->path = plugin_dir_path( __FILE__ );
		$this->wp_plugin_page = "http://codecanyon.net/item/keymaster-chord-notation/10991332?ref=intelligent-design";
		$this->kcn_plugin_page = "http://codecanyon.net/item/keymaster-chord-notation/10991332?ref=intelligent-design";
		$this->kcn_plugin_name = __("Keymaster Chord Notation Free");
		$this->kcn_plugin_slug = "keymaster-chord-notation-free";
		$this->kcn_plugin_ref = "keymaster_chord_notation_free";
		$this->hook_suffix = "settings_page_$this->kcn_plugin_ref";
		$this->set_buttons_var();
		$this->content_buttons_locations = array('top'=>array('text'=>__('Top')),'bottom'=>array('text'=>__('Bottom')));
		$this->button_sizes = array(
		    'xs'=>array('text'=>__('Extra small')),
		    'sm'=>array('text'=>__('Small')),
		    'md'=>array('text'=>__('Medium')),
		    'lg'=>array('text'=>__('Large')),
		    );
		
		add_action( 'plugins_loaded', array($this, 'setup_plugin') );
		add_action( 'admin_init', array($this,'register_settings_fields') );		
		add_action( 'admin_menu', array($this,'register_settings_page'), 20 );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_assets') );
		add_action("admin_head-$this->hook_suffix",array($this,'admin_head'));
		add_action( 'wp_enqueue_scripts',  array($this, 'add_javascript')  );
		add_action('wp_head',array($this,'echo_styles'));
		add_shortcode( 'chord', array($this,'chord_shortcode') );
		add_shortcode( 'key', array($this,'key_shortcode') );
		add_shortcode('kcn_buttons', array($this,'kcn_buttons_shortcode'));
		add_filter('the_content', array($this,'the_content_filter'));
		
		add_action( 'admin_print_footer_scripts', array($this, 'add_javascript'), 100 );
		$this->options = get_option( "$this->kcn_plugin_ref-options" );
		
		// TODO: add custom actions to run on deactivation
		//register_deactivation_hook( __FILE__, array($this, 'deactivate_plugin_actions') );
	}
	/**
	 * Sets this object's buttons attribute
	 * show_hide, transpose_down, transpose_fs, transpose_up, print
	 */
	private function set_buttons_var(){
	    $this->buttons = array(
		    'show_hide'=>array(
			'group'=>'',
			'ref'=>'show_hide',
			'text'=>__('Show/Hide Chords'),
			'classes'=>'KCNshowHide',
			'attributes'=>''
		    ),'transpose_down'=>array(
			'group'=>'transposeGroup',
			'ref'=>'transpose_down',
			'text'=>'-',
			'classes'=>'transpose-btn',
			'attributes'=>'disabled="disabled"'
		    ),'transpose_fs'=>array(
			'group'=>'transposeGroup',
			'ref'=>'transpose_fs',
			'text'=>__('Transpose ♯↔♭'),
			'classes'=>'transpose-btn',
			'attributes'=>'disabled="disabled"'
		    ),'transpose_up'=>array(
			'group'=>'transposeGroup',
			'ref'=>'transpose_up',
			'text'=>'+',
			'classes'=>'transpose-btn',
			'attributes'=>'disabled="disabled"'
		    ),'print'=>array(
			'group'=>'',
			'ref'=>'print',
			'text'=>__('Print'),
			'classes'=>'KCNprint',
			'attributes'=>''
		    )
		);
	}
	/**
	 * Runs when options are updated, and recompiles LESS CSS
	 
	public function updated_options(){
	    $variables = array_intersect_key($this->options, array_flip($this->compiled_vars));
	    self::compile_css($variables);
	}*/
	
	/**
	 * This shows the front-end styles that are editable from the options page.
	 * It prints 2 style tags: one print, one all media queries.  The print media
	 * query will be copied to the popup window upon clicking the print button.
	 */
	public function echo_styles(){
	    
	    ob_start();
	   
	    ?>
	    <style type="text/css" media="all">
		.KCNchordWrap{
		    padding: <?php echo $this->options['chord_padding_vertical'] ?>px <?php echo $this->options['chord_padding_horizontal'] ?>px;
		    margin-left: 1px;
		    margin-right: 1px;
		    border-radius: <?php echo $this->options['chord_border_radius'] ?>%;
			    <?php
		    if(isset($this->options['auto_raise'])){ ?>	    
			margin-top: <?php echo $this->options['chord_margin_top'] ?>px;
			position: relative;
			top: -<?php echo $this->options['chord_position_top'] ?>px;
			left: -<?php echo $this->options['chord_position_left'] ?>px;
			margin-right: -<?php echo $this->options['chord_minimum_width'] ?>px;
			min-width: <?php echo $this->options['chord_minimum_width'] ?>px;
		    <?php } ?>
		    text-align: center;
		    font-size: <?php echo $this->options['chord_font_size'] ?>px;
		}
		.KCNbtn-group.KCNbtn-group-justified{
		    margin-top:<?php echo $this->options['vertical_button_spacing'] ?>px;
		    margin-bottom:<?php echo $this->options['vertical_button_spacing'] ?>px;
		}

	    </style>
	    <?php
	    echo preg_filter('@[\s]{2}@','',ob_get_clean());
	}

	
	/**
	 * Load plugin textdomain
	 */
	public function setup_plugin(){
	 	load_plugin_textdomain( $this->kcn_plugin_slug, false, $this->path."lang/" ); 
	}
	
	/**
	 * Tell WordPress to load its handy color-picker api
	 * @param string $page variable sent by WordPress admin_enqueue_scripts hook
	 */
	public function admin_assets($page){
		if( strpos($page, $this->kcn_plugin_ref) !== false  ){
		    if(is_admin()){
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script('wp-color-picker'); 
		    }
		}		
	}
	public function admin_head(){
	    ?><style>.KCNdisabled > * ,.KCNdisabled a{opacity: 0.5;cursor: not-allowed;}</style><?php
	}
	
	/**
	 * Set up all the fields for the settings page
	 * @todo Create and use a settings php class rather than shove it all into this class
	 */
	public function register_settings_fields() {
		add_settings_section(
			"$this->kcn_plugin_ref-general",// ID used to identify this section and with which to register options
			__('General Settings'),// Title to be displayed on the administration page
			false,// Callback used to render the description of the section
			$this->kcn_plugin_ref// Page on which to add this section of options
		);
		add_settings_section(
			"$this->kcn_plugin_ref-appearance",
			__('Appearance').'<br /><small>'.__('Note: In the free version, these do not affect the printed page\'s appearance, but they do affect your website\'s appearance.').'</small>',
			false,
			$this->kcn_plugin_ref
			);
		$textOptions = array('print_selector'=>array(
			'label'=>__('Print page CSS selector'),
			'section'=>'general',
			'placeholder'=>'.KCN-content',
			'description'=>__('When the print button is clicked, it will send only the contents of the selected element (section of the page) to the user\'s printer.')
		    )
		);
		$colorOptions = array(
		    'letter_notation_highlight_color'=>array(
			'label'=>__('Letter notation highlight color<br /><small>Premium plugin only</small>'),
			'section'=>'appearance',
			'description'=>__('Color of the small chord notation highlight'),
			'placeholder'=>'#00ade2'
		    ),'letter_notation_text_color'=>array(
			'label'=>__('Letter notation text color<br /><small>Premium plugin only</small>'),
			'section'=>'appearance',
			'description'=>__('Color of the small chord notation text'),
			'placeholder'=>'#ffffff'
		    ),'button_primary_color'=>array(
			'label'=>__('Button primary color<br /><small>Premium plugin only</small>'),
			'section'=>'appearance',
			'description'=>__('The primary color used on the control buttons'),
			'placeholder'=>'#00ade2'
		    ),'button_text_color'=>array(
			'label'=>__('Button text color<br /><small>Premium plugin only</small>'),
			'section'=>'appearance',
			'description'=>__('The color of the text used on the control buttons'),
			'placeholder'=>'#ffffff'
		    )
		);

		$numberOptions = array(
		    'chord_border_radius'=>array(
			'label'=>__('Chord roundness').'<br /><small>(border-radius %)</small>',
			'section'=>'appearance'
		    ),
		    'chord_margin_top'=>array(
			'label'=>__('Chord margin top'),
			'section'=>'appearance'
		    ),
		    'chord_padding_vertical'=>array(
			'label'=>__('Chord padding vertical'),
			'section'=>'appearance'
		    ),
		    'chord_padding_horizontal'=>array(
			'label'=>__('Chord padding horizontal'),
			'section'=>'appearance'
		    ),
		    'chord_position_top'=>array(
			'label'=>__('Chord position up'),
			'section'=>'appearance'
		    ),
		    'chord_position_left'=>array(
			'label'=>__('Chord position right'),
			'section'=>'appearance'
		    ),
		    'chord_minimum_width'=>array(
			'label'=>__('Chord minimum width'),
			'section'=>'appearance'
		    ),
		    'chord_font_size'=>array(
			'label'=>__('Chord font size'),
			'section'=>'appearance'
		    ),
		    'vertical_button_spacing'=>array(
			'label'=>__('Vertical button spacing'),
			'section'=>'appearance'
		    )
		);
		$checkboxOptions = array(
		    'hide_buttons'=>array(
			'label'=>__('Buttons to hide in content<small> (Transpose functions are only available in the premium version)</small>'),
			'section'=>'general',
			'source'=>$this->buttons
		    ),
		    'content_buttons'=>array(
			'label'=>__('Auto-include the buttons in the content'),
			'section'=>'general',
			'source'=>$this->content_buttons_locations
		    ),
		    'auto_raise'=>array(
			'label'=>__('Place each chord above the succeeding character automatically').' <small>('.__('using the above positioning values').')</small>',
			'section'=>'appearance'
		    ),
		    'chords_visible_by_default'=>array(
			'label'=>__('Chords are visible by default'),
			'section'=>'general'
		    )
		);
		$radioOptions = array(
		    'button_size'=>array(
			'label'=>__('Button size'),
			'section'=>'appearance',
			'source'=>$this->button_sizes
		    )
		);
		$this->add_settings_fields($radioOptions, 'radio');
		$this->add_settings_fields($numberOptions, 'number');
		$this->add_settings_fields($checkboxOptions,'checkboxes');
		$this->add_settings_fields($textOptions,'text');
		$this->add_settings_fields($colorOptions,'color');
		register_setting($this->kcn_plugin_ref, "$this->kcn_plugin_ref-options");
	}
	
	/**
	 * 
	 * @param array $options A set of option references each with its own array of optional label, section, description, placeholder and source
	 * @param string $type One of several types of settings fields defined in this class, such as radio, number, checkboxes, text, color, hidden
	 */
	private function add_settings_fields($options,$type){
	    foreach($options as $id=>$textOption){
		$description = isset($textOption['description'])? $textOption['description'] : '';
		$placeholder = isset($textOption['placeholder'])? $textOption['placeholder'] : '';
		$section = isset($textOption['section'])? $textOption['section'] : 'general';
		$label = isset($textOption['label'])? $textOption['label'] : '';
		$source = isset($textOption['source'])? $textOption['source']: array();
		add_settings_field(
		    "$this->kcn_plugin_ref-$id",
		    $label,
		    array($this,"show_settings_field_$type"),
		    $this->kcn_plugin_ref,
		    $this->kcn_plugin_ref.'-'.$section,
		    array('label_for'=>$id,'field_name'=>$id,'field_placeholder'=>$placeholder,'field_description'=>$description,'source'=>$source)
		);		    
	    }
	}
	/* These 6 functions are pretty self explanatory:  They show their inputs that they specialize in based on their function name */
	
	public function show_settings_field_number($args){
		$saved_value =  $this->options[$args['field_name']] ;
		
		?><input class="small-text" type="number" placeholder="<?php echo $args['field_placeholder'] ?>" name="<?php echo "$this->kcn_plugin_ref-options" ?>[<?php echo $args['field_name'] ?>]" id="<?php echo $args['field_name'] ?>" value="<?php echo $saved_value ?>" /><br/>
		<p class="description"><?php echo wptexturize($args['field_description']); ?></p><?php
	}
	public function show_settings_field_checkboxes($args){
	    $saved_values =  isset($this->options[$args['field_name']])? $this->options[$args['field_name']]: '' ;
	    $args['source'] = empty($args['source'])? array($args['field_name']=>array('text'=>'')) : $args['source'];
	    foreach($args['source'] as $name=>$array){
		
		$checked = (is_array($saved_values)&&array_key_exists($name, $saved_values))? 'checked="checked"' : ''
		?><input type="checkbox" id="<?php echo $args['field_name'].'-'.$name ?>" name="<?php echo "$this->kcn_plugin_ref-options" ?>[<?php echo $args['field_name'] ?>][<?php echo $name ?>]" <?php echo $checked ?> />
		<label for="<?php echo $args['field_name'].'-'.$name ?>"><?php echo $array['text'] ?></label><br /><?php
	    }
	}
	public function show_settings_field_radio($args){
	    $saved_value =  $this->options[$args['field_name']] ;
	    $args['source'] = empty($args['source'])? array($args['field_name']=>array('text'=>'')) : $args['source'];
	    foreach($args['source'] as $name=>$array){
		
		$checked = ($saved_value===$name)? 'checked="checked"' : ''
		?><input type="radio" id="<?php echo $args['field_name'].'-'.$name ?>" name="<?php echo "$this->kcn_plugin_ref-options" ?>[<?php echo $args['field_name'] ?>]" value="<?php echo $name ?>" <?php echo $checked ?> />
		<label for="<?php echo $args['field_name'].'-'.$name ?>"><?php echo $array['text'] ?></label><br /><?php
	    }
	}


	public function show_settings_field_text($args){
		$saved_value =  $this->options[$args['field_name']] ;
		
		?><input type="text" placeholder="<?php echo $args['field_placeholder'] ?>" name="<?php echo "$this->kcn_plugin_ref-options" ?>[<?php echo $args['field_name'] ?>]" id="<?php echo $args['field_name'] ?>" value="<?php echo $saved_value ?>" /><br/>
		<p class="description"><?php echo wptexturize($args['field_description']); ?></p><?php
	}
	public function show_settings_field_color($args){
		$saved_value =  $args['field_placeholder'];
		
		?><div class="KCNdisabled"><input disabled="disabled" data-default-color="<?php echo $args['field_placeholder'] ?>" type="text" class="KCNcolorPicker" name="<?php echo "$this->kcn_plugin_ref-options" ?>[<?php echo $args['field_name'] ?>]" id="<?php echo $args['field_name'] ?>" value="<?php echo $saved_value ?>" /><br/>
		<p class="description"><?php echo wptexturize($args['field_description']); ?></p></div><?php
	}

	/**
	 * Tell WordPress to give us our own spot on the settings menu
	 */
	public function register_settings_page(){
		add_submenu_page(
			'options-general.php',					// Parent menu item slug	
			__($this->kcn_plugin_name, $this->kcn_plugin_name),	// Page Title
			__($this->kcn_plugin_name, $this->kcn_plugin_name),	// Menu Title
			'manage_options',					// Capability
			$this->kcn_plugin_ref,					// Menu Slug
			array( $this, 'show_settings_page' )			// Callback function
		);
	}
	/**
	 * Show the settings page
	 */
	public function show_settings_page(){
		?>
		<div class="wrap">
			
		    <h2>Keymaster Chord Notation </h2><small>
		    <a href="http://codecanyon.net/item/keymaster-chord-notation/10991332?ref=intelligent-design" target="_blank"><?php _e('Upgrade to Premium Plugin') ?></a></small>

		    <!-- BEGIN Left Column -->
		    <div class="kcn-col-left">
			    <form method="POST" action="options.php" style="width: 100%;">
				    <?php settings_fields($this->kcn_plugin_ref); ?>
				    <?php do_settings_sections($this->kcn_plugin_ref); ?>
				    <?php submit_button(); ?>
			    </form>
		    </div>
		    <script>jQuery(document).ready(function($){$('.KCNcolorPicker').wpColorPicker();});</script>
		    <!-- END Left Column -->

		    <!-- BEGIN Right Column -->			
		    <div class="kcn-col-right">
			<?php //@todo put something useful here ?>
		    </div>
		    <!-- END Right Column -->

		</div>
		<?php
	}
	/**
	 * 
	 * @param mixed $atts The shorcode's attributes (if specified)
	 * @return string output to frontend
	 */
	public function kcn_buttons_shortcode($atts){
	    $atts = empty($atts)? array() : $atts;
	    $auto_included = (array_search('auto_included', $atts)!==false)? true : false;	    
	    if($auto_included){
		//show the thing with whatever options were saved
		$layout = isset($this->options['button_layout'])? $this->options['button_layout'] : 'horizontal';
		$size = isset($this->options['button_size'])? $this->options['button_size'] : 'md';
		$hide_buttons = isset($this->options['hide_buttons'])? $this->options['hide_buttons'] : '';
		$hide = (is_array($hide_buttons))? array_keys($hide_buttons) : array();
	    }else{
		//show it vertically with everything unless the $hide var has something to say
		$layout = array_key_exists('layout', $atts)? $atts['layout'] : 'horizontal';
		$size = array_key_exists('size', $atts)? $atts['size'] : 'md';
		$hide = array_key_exists('hide', $atts)? explode(',',$atts['hide']) : array();
	    }
	    $out = '<div class="KCNtranspose">';
	    $group_div = false;
	    $trimmedHide = array_map('trim',$hide);
	    foreach($this->buttons as $button_ref=>$button){
		// don't do it if it's in the hide list		
		if(array_search($button_ref,$trimmedHide)===false){
		    if(!empty($button['group'])){
			if(!$group_div){
			    $group_div = true;
			    if($layout==='vertical'){
				$out .= '<div class="KCNbtn-group KCNbtn-group-justified">';
			    }
			    $out .= '<div class="KCNbtn-group '.$button['group'].'">';
			}elseif($layout==='vertical'){
			    $out .= '</div><div class="KCNbtn-group '.$button['group'].'">';
			}
		    }if(empty($button['group'])){
			if($group_div){
			    $group_div = false;
			    $out .= '</div>';
			    if($layout==='vertical'){
				$out .= '</div>';
			    }
			}
		    }elseif($layout==='vertical'){
			//Relax--we don't need to do anything here!
		    }
		    $_layout = $group_div? 'horizontal' : $layout;
		    $out .= $this->show_button($button,$_layout,$size);
		}
	    }
	    // in case a group is at the end of the loop
	    if($group_div){
		$group_div = false;
		$out .= '</div>';
		if($layout==='vertical'){
		    $out .= '</div>';
		}
	    }

	    return "$out</div>";
	    
	}
	/**
	 * Display one of the control buttons
	 * @param array $button
	 * @param string $layout vertical or horizontal
	 * @param string $size one of 4 available button sizes
	 * @return string the output for display on frontend
	 */
	private function show_button($button,$layout,$size){
	    $block = $layout==='vertical'? 'KCNbtn-block' : '';
	    
	    ob_start();
	    ?><button class="KCNbtn KCNbtn-<?php echo $size ?> KCNbtn-primary <?php echo "$block ".$button['classes'] ?>" <?php echo $button['attributes'] ?>><?php echo $button['text'] ?></button><?php
	    return ob_get_clean();
	}
	
	/**
	 * 
	 * @param mixed $atts The shorcode's attributes (if specified)
	 * @return string
	 */
	public function chord_shortcode($atts){
	    if(is_array($atts)){
		$chord = strtolower($atts[0]);
	    }else {
		return '';
	    }
	    $quality = isset($atts[1])? $atts[1] : '';
	    // This snippet sets a var to tell the rest of the app that we're on a page with a chord shortcode.
	    if(!$this->showed_script){
		$this->showed_script = true;
		$script = "<script>var KCNhasChords = true;</script>";
	    }else{
		$script = '';
	    }
	    $chordPretty = (strlen($chord)>1&&substr($chord, 1,1)==='b')? strtoupper(substr($chord, 0,1)).'♭' : strtoupper($chord);
	    $chordClass = str_replace('#', 's', $chord);
	    $qualityClass = empty($quality)? '' : "-$quality";
	    $display = isset($this->options['chords_visible_by_default'])? 'inline-block': 'none';
	    return "<span class='KCNchordWrap' style='display:$display;'><span  class='KCNchord KCNchord-$chordClass$qualityClass'>$chordPretty</span><span class='KCNchordWrapQuality'>$quality</span></span>$script";
	}
	/**
	 * 
	 * @param mixed $atts The shorcode's attributes (if specified)
	 * @return string
	 */
	public function key_shortcode($atts){
	    if(is_array($atts)){
		$key = strtolower($atts[0]);
		$keyPretty = (strlen($atts[0])>1&&substr($atts[0], 1,1)==='b')? strtoupper(substr($atts[0], 0,1)).'♭' : strtoupper($atts[0]);
		$quality = isset($atts[1])? $atts[1] : '';
	    }else {
		return '';
	    }
	    return "<h3 class='KCNkeyTitle'>".__("Key of")." <span class='KCNkey'>$keyPretty</span> <span class='KCNkeyQuality'>$quality</span></h3>";
	}
	/**
	 * Filter out old fashioned chord notations that used spans and replace them with
	 * the appropriate shortcodes if they exist.  Also this is where it adds the button
	 * shortcode into the content if the options are set to do so.
	 * 
	 * @param string $content WordPress's precious the_content (handle with care)
	 * @return string
	 */
	public function the_content_filter($content){
	    
	    $_content = preg_filter('@<span class="chord ([a-z])(m?)(7?)">.*?</span>@s', '[chord $1 $2$3]', $content);
	    if(!empty($_content)){
		$content = $_content;
	    }
	    // That was fun--let's do it again for the old img style chord indicator
	    $_content = preg_filter('@<img class="chord" .*?/([a-z])(m?)(7?)\.png.*? />@s', '[chord $1 $2$3]', $content);
	    if(!empty($_content)){
		$content = $_content;
	    }
	    $_content = preg_filter('@<a .*?Show/hide chords.*?</a>@s', '', $content);
	    if(!empty($_content)){
		$content = $_content;
	    }
	    if(is_single()||is_page()){
		$content .= '<script>var KCN_print_selector = "'.  $this->options['print_selector'].'";</script>';
		if(isset($this->options['content_buttons']['top'])){
		    $content = do_shortcode('[kcn_buttons auto_included]').$content;
		}
		if(isset($this->options['content_buttons']['bottom'])){
		    $content .= do_shortcode('[kcn_buttons auto_included]');
		}
		$content = "<div class='KCN-content'>$content</div>";
	    }
	    return $content;
	}
	
	/**
	 * Power up your frontend with some scripts and a stylesheet
	 */
	public function add_javascript(){
	    wp_register_script( $this->kcn_plugin_slug, plugins_url("js/keymaster-chord-notation.js",__FILE__), array('jquery'), '1.0.2',true );
	    wp_register_script( $this->kcn_plugin_slug.'_print', plugins_url("js/print-area.jquery.js",__FILE__), array('jquery'), '1.0.2',true );
	    wp_enqueue_script( $this->kcn_plugin_slug );
	    wp_enqueue_script( $this->kcn_plugin_slug.'_print');
	    // And our only stylesheet
	    wp_register_style( $this->kcn_plugin_slug.'_bs1', plugins_url("css/buttons_fixed.css",__FILE__), false, '1.0.2' );
	    wp_enqueue_style( $this->kcn_plugin_slug.'_bs1');	
	    wp_register_style( $this->kcn_plugin_slug.'_print', plugins_url("css/print.css",__FILE__), false, '1.0.2','print' );
	    wp_enqueue_style( $this->kcn_plugin_slug.'_print');	

	}
	
}
register_activation_hook( __FILE__, array( 'Keymaster_Chord_Notation_Free', 'install' ) );
new Keymaster_Chord_Notation_Free();
