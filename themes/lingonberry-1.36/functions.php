<?php

// Theme setup
add_action( 'after_setup_theme', 'lingonberry_setup' );

function lingonberry_setup() {
	
	// Automatic feed
	add_theme_support( 'automatic-feed-links' );
	
	// Custom background
	add_theme_support( 'custom-background' );
	
	// Post formats
	add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	
	// Post thumbnails
	add_theme_support( 'post-thumbnails', array( 'post', 'page' ) );
	add_image_size( 'post-image', 766, 9999 );
	
	// Title tag
	add_theme_support( 'title-tag' );

	// Custom header (logo)
	$custom_header_args = array( 'width' => 200, 'height' => 200, 'header-text' => false );
	add_theme_support( 'custom-header', $custom_header_args );
	
	// Add nav menu
	register_nav_menu( 'primary', 'Primary Menu' );
	
	// Make the theme translation ready
	load_theme_textdomain('lingonberry', get_template_directory() . '/languages');
	
	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable($locale_file) )
	  require_once($locale_file);
	
}

// Enqueue Javascript files
function lingonberry_load_javascript_files() {

	if ( !is_admin() ) {
		wp_enqueue_script( 'lingonberry_flexslider', get_template_directory_uri().'/js/flexslider.min.js', array('jquery'), '', true  );
		wp_enqueue_script( 'lingonberry_global', get_template_directory_uri().'/js/global.js', array('jquery'), '', true );
		if ( is_singular() ) wp_enqueue_script( "comment-reply" );
	}
}

add_action( 'wp_enqueue_scripts', 'lingonberry_load_javascript_files' );


// Enqueue styles
function lingonberry_load_style() {
	if ( !is_admin() ) {
	    wp_enqueue_style( 'lingonberry_googleFonts', '//fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic|Raleway:600,500,400' );
	    wp_enqueue_style( 'lingonberry_style', get_stylesheet_uri() );
	}
}

add_action('wp_print_styles', 'lingonberry_load_style');


// Add editor styles
function lingonberry_add_editor_styles() {
    add_editor_style( 'lingonberry-editor-styles.css' );
    $font_url = '//fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic|Raleway:600,500,400';
    add_editor_style( str_replace( ',', '%2C', $font_url ) );
}
add_action( 'init', 'lingonberry_add_editor_styles' );


// Add footer widget areas
add_action( 'widgets_init', 'lingonberry_sidebar_reg' ); 

function lingonberry_sidebar_reg() {
	register_sidebar(array(
	  'name' => __( 'Footer A', 'lingonberry' ),
	  'id' => 'footer-a',
	  'description' => __( 'Widgets in this area will be shown in the first column in the footer.', 'lingonberry' ),
	  'before_title' => '<h3 class="widget-title">',
	  'after_title' => '</h3>',
	  'before_widget' => '<div class="widget %2$s"><div class="widget-content">',
	  'after_widget' => '</div><div class="clear"></div></div>'
	));	
	register_sidebar(array(
	  'name' => __( 'Footer B', 'lingonberry' ),
	  'id' => 'footer-b',
	  'description' => __( 'Widgets in this area will be shown in the second column in the footer.', 'lingonberry' ),
	  'before_title' => '<h3 class="widget-title">',
	  'after_title' => '</h3>',
	  'before_widget' => '<div class="widget %2$s"><div class="widget-content">',
	  'after_widget' => '</div><div class="clear"></div></div>'
	));
	register_sidebar(array(
	  'name' => __( 'Footer C', 'lingonberry' ),
	  'id' => 'footer-c',
	  'description' => __( 'Widgets in this area will be shown in the third column in the footer.', 'lingonberry' ),
	  'before_title' => '<h3 class="widget-title">',
	  'after_title' => '</h3>',
	  'before_widget' => '<div class="widget %2$s"><div class="widget-content">',
	  'after_widget' => '</div><div class="clear"></div></div>'
	));
}
	
// Add theme widgets
require_once (get_template_directory() . "/widgets/dribbble-widget.php");  
require_once (get_template_directory() . "/widgets/flickr-widget.php");  
require_once (get_template_directory() . "/widgets/video-widget.php");


// Set content-width
if ( ! isset( $content_width ) ) $content_width = 766;


// Check whether the browser supports javascript
function lingonberry_html_js_class() {
    echo '<script>document.documentElement.className = document.documentElement.className.replace("no-js","js");</script>'. "\n";
}
add_action( 'wp_head', 'lingonberry_html_js_class', 1 );


// Add classes to next_posts_link and previous_posts_link
add_filter('next_posts_link_attributes', 'lingonberry_posts_link_attributes_1');
add_filter('previous_posts_link_attributes', 'lingonberry_posts_link_attributes_2');

function lingonberry_posts_link_attributes_1() {
    return 'class="post-nav-older"';
}
function lingonberry_posts_link_attributes_2() {
    return 'class="post-nav-newer"';
}


// Menu walker adding "has-children" class to menu li's with children menu items
class lingonberry_nav_walker extends Walker_Nav_Menu {
    function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
        $id_field = $this->db_fields['id'];
        if ( !empty( $children_elements[ $element->$id_field ] ) ) {
            $element->classes[] = 'has-children';
        }
        Walker_Nav_Menu::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
}


// Add class to body if the post/page has a featured image
add_action('body_class', 'lingonberry_if_featured_image_class' );

function lingonberry_if_featured_image_class($classes) {
	if ( has_post_thumbnail() ) { 
		array_push($classes, 'has-featured-image');
	}
	return $classes;
}


// Custom more-link text
add_filter( 'the_content_more_link', 'lingonberry_custom_more_link', 10, 2 );

function lingonberry_custom_more_link( $more_link, $more_link_text ) {
	return str_replace( $more_link_text, __('Continue reading', 'lingonberry'), $more_link );
}


// Flexslider-ify function for format-gallery
function lingonberry_flexslider($size = thumbnail) {

	if ( is_page()) :
		$attachment_parent = $post->ID;
	else : 
		$attachment_parent = get_the_ID();
	endif;

	if($images = get_posts(array(
		'post_parent'    => $attachment_parent,
		'post_type'      => 'attachment',
		'numberposts'    => -1, // show all
		'post_status'    => null,
		'post_mime_type' => 'image',
                'orderby'        => 'menu_order',
                'order'           => 'ASC',
	))) { ?>
	
		<div class="flexslider">
		
			<ul class="slides">
	
				<?php foreach($images as $image) { 
					$attimg = wp_get_attachment_image($image->ID,$size); ?>
					
					<li>
						<?php echo $attimg; ?>
						<?php if ( !empty($image->post_excerpt)) : ?>
							<div class="media-caption-container">
								<p class="media-caption"><?php echo $image->post_excerpt ?></p>
							</div>
						<?php endif; ?>
					</li>
					
				<?php }; ?>
		
			</ul>
			
		</div><?php
		
	}
}


function lingonberry_meta() { ?>
	
	<div class="post-meta">
	
		<span class="post-date"><a href="<?php the_permalink(); ?>" title="<?php the_time(get_option('time_format')); ?>"><?php the_time(get_option('date_format')); ?></a></span>
		
		<span class="date-sep"> / </span>
			
		<span class="post-author"><?php the_author_posts_link(); ?></span>
		
		<?php if ( comments_open() ) : ?>
		
			<span class="date-sep"> / </span>
			
			<?php comments_popup_link( '<span class="comment">' . __( '0 Comments', 'lingonberry' ) . '</span>', __( '1 Comment', 'lingonberry' ), __( '% Comments', 'lingonberry' ) ); ?>
		
		<?php endif; ?>
		
		<?php if( is_sticky() && !has_post_thumbnail() ) { ?> 
		
			<span class="date-sep"> / </span>
		
			<?php _e('Sticky', 'lingonberry'); ?>
		
		<?php } ?>
		
		<?php edit_post_link(__('Edit', 'lingonberry'), '<span class="date-sep"> / </span>'); ?>
								
	</div> <!-- /post-meta -->
<?php	
}


// Style the admin area
function lingonberry_custom_colors() {
   echo '<style type="text/css">
   
#postimagediv #set-post-thumbnail img {
	max-width: 100%;
	height: auto;
}

         </style>';
}

add_action('admin_head', 'lingonberry_custom_colors');


// Lingonberry comment function
if ( ! function_exists( 'lingonberry_comment' ) ) :
function lingonberry_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
	?>
	
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
	
		<?php __( 'Pingback:', 'lingonberry' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( '(Edit)', 'lingonberry' ), '<span class="edit-link">', '</span>' ); ?>
		
	</li>
	<?php
			break;
		default :
		global $post;
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
	
		<div id="comment-<?php comment_ID(); ?>" class="comment">
		
			<div class="comment-meta comment-author vcard">
							
				<?php echo get_avatar( $comment, 120 ); ?>

				<div class="comment-meta-content">
											
					<?php printf( '<cite class="fn">%1$s %2$s</cite>',
						get_comment_author_link(),
						( $comment->user_id === $post->post_author ) ? '<span class="post-author"> ' . __( '(Post author)', 'lingonberry' ) . '</span>' : ''
					); ?>
					
					<p><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ) ?>"><?php echo get_comment_date() . ' &mdash; ' . get_comment_time() ?></a></p>
					
				</div> <!-- /comment-meta-content -->
				
				<div class="comment-actions">
				
					<?php edit_comment_link( __( 'Edit', 'lingonberry' ), '', '' ); ?>
					
					<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'lingonberry' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
									
				</div> <!-- /comment-actions -->
				
				<div class="clear"></div>
				
			</div> <!-- /comment-meta -->

			<div class="comment-content post-content">
			
				<?php if ( '0' == $comment->comment_approved ) : ?>
				
					<p class="comment-awaiting-moderation"><?php __( 'Your comment is awaiting moderation.', 'lingonberry' ); ?></p>
					
				<?php endif; ?>
			
				<?php comment_text(); ?>
				
				<div class="comment-actions">
				
					<?php edit_comment_link( __( 'Edit', 'lingonberry' ), '', '' ); ?>
					
					<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'lingonberry' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
					
					<div class="clear"></div>
				
				</div> <!-- /comment-actions -->
				
			</div><!-- /comment-content -->

		</div><!-- /comment-## -->
	<?php
		break;
	endswitch;
}
endif;


// Lingonberry theme options
class lingonberry_Customize {

   public static function lingonberry_register ( $wp_customize ) {
   
      //1. Define a new section (if desired) to the Theme Customizer
      $wp_customize->add_section( 'lingonberry_options', 
         array(
            'title' => __( 'Options for Lingonberry', 'lingonberry' ), //Visible title of section
            'priority' => 35, //Determines what order this appears in
            'capability' => 'edit_theme_options', //Capability needed to tweak
            'description' => __('Allows you to customize theme settings for Lingonberry.', 'lingonberry'), //Descriptive tooltip
         ) 
      );
            
      
      //2. Register new settings to the WP database...
      $wp_customize->add_setting( 'accent_color', //No need to use a SERIALIZED name, as `theme_mod` settings already live under one db record
         array(
            'default' => '#FF706C', //Default setting/value to save
            'type' => 'theme_mod', //Is this an 'option' or a 'theme_mod'?
            'transport' => 'postMessage', //What triggers a refresh of the setting? 'refresh' or 'postMessage' (instant)?
            'sanitize_callback' => 'sanitize_hex_color'
         ) 
      );
	  
	  $wp_customize->add_setting( 'lingonberry_logo', 
      	array( 
      		'sanitize_callback' => 'esc_url_raw'
      	) 
      );
      
      
      //3. Finally, we define the control itself (which links a setting to a section and renders the HTML controls)...
      $wp_customize->add_control( new WP_Customize_Color_Control( //Instantiate the color control class
         $wp_customize, //Pass the $wp_customize object (required)
         'lingonberry_accent_color', //Set a unique ID for the control
         array(
            'label' => __( 'Accent Color', 'lingonberry' ), //Admin-visible name of the control
            'section' => 'colors', //ID of the section this control should render in (can be one of yours, or a WordPress default section)
            'settings' => 'accent_color', //Which setting to load and manipulate (serialized is okay)
            'priority' => 10, //Determines the order this control appears in for the specified section
         ) 
      ) );
      
      //4. We can also change built-in settings by modifying properties. For instance, let's make some stuff use live preview JS...
      $wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
      $wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
   }

   public static function lingonberry_header_output() {
      ?>
      
	      <!-- Customizer CSS --> 
	      
	      <style type="text/css">
	           <?php self::lingonberry_generate_css('body a', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('body a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.header', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-bubbles a:hover', 'background-color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-nav a:hover', 'background-color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.comment-meta-content cite a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.comment-meta-content p a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.comment-actions a:hover', 'background-color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget-content .textwidget a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_archive li a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_categories li a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_meta li a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_nav_menu li a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_rss .widget-content ul a.rsswidget:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('#wp-calendar thead', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.widget_tag_cloud a:hover', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.search-button:hover .genericon', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.flexslider:hover .flex-next:active', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.flexslider:hover .flex-prev:active', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-title a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content a', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content a:hover', 'border-bottom-color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content fieldset legend', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content input[type="submit"]:hover', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content input[type="button"]:hover', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.post-content input[type="reset"]:hover', 'background', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.comment-header h4 a:hover', 'color', 'accent_color'); ?>
	           <?php self::lingonberry_generate_css('.form-submit #submit:hover', 'background-color', 'accent_color'); ?>	           
	      </style> 
	      
	      <!--/Customizer CSS-->
	      
      <?php
   }
   
   public static function lingonberry_live_preview() {
      wp_enqueue_script( 
           'lingonberry-themecustomizer', // Give the script a unique ID
           get_template_directory_uri() . '/js/theme-customizer.js', // Define the path to the JS file
           array(  'jquery', 'customize-preview' ), // Define dependencies
           '', // Define a version (optional) 
           true // Specify whether to put in footer (leave this true)
      );
   }

   public static function lingonberry_generate_css( $selector, $style, $mod_name, $prefix='', $postfix='', $echo=true ) {
      $return = '';
      $mod = get_theme_mod($mod_name);
      if ( ! empty( $mod ) ) {
         $return = sprintf('%s { %s:%s; }',
            $selector,
            $style,
            $prefix.$mod.$postfix
         );
         if ( $echo ) {
            echo $return;
         }
      }
      return $return;
    }
}

// Setup the Theme Customizer settings and controls...
add_action( 'customize_register' , array( 'lingonberry_Customize' , 'lingonberry_register' ) );

// Output custom CSS to live site
add_action( 'wp_head' , array( 'lingonberry_Customize' , 'lingonberry_header_output' ) );

// Enqueue live preview javascript in Theme Customizer admin screen
add_action( 'customize_preview_init' , array( 'lingonberry_Customize' , 'lingonberry_live_preview' ) );


?>