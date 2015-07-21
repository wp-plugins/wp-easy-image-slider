<?php
/*
Plugin Name: WP Easy Image Slider
Plugin URI: http://www.idreamwebsolutions.com/
Description: A Simple Image Slider plugin
Version: 1.0
Author: Ahir Hemant
Author URI: http://www.idreamwebsolutions.com/
Contributors: Ahir Hemant
*/
?>

<?php 

function wp_easy_slider_activation() {
}
register_activation_hook(__FILE__, 'wp_easy_slider_activation');

function wp_easy_slider_deactivation() {
}
register_deactivation_hook(__FILE__, 'wp_easy_slider_deactivation');



/* Slider Images */

function call_Multi_Image_Uploader()
{
    new Multi_Image_Uploader();
}

function miu_get_images($post_id=null)
{
    global $post;
    if ($post_id == null)
    {
        $post_id = $post->ID;
    }

    $value = get_post_meta($post_id, 'miu_images', true);
    $images = unserialize($value);
    $result = array();
    if (!empty($images))
    {
        foreach ($images as $image)
        {
            $result[] = $image;
        }
    }
    return $result;
}

if (is_admin())
{
    add_action('load-post.php', 'call_Multi_Image_Uploader');
    add_action('load-post-new.php', 'call_Multi_Image_Uploader');
}

/**
 * Multi_Image_Uploader
 */
class Multi_Image_Uploader
{

    var $post_types = array();

    /**
     * Initialize Multi_Image_Uploader
     */
    public function __construct()
    {
        $this->post_types = array('slider');     //limit meta box to certain post types you can add post,pages
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box($post_type)
    {

        if (in_array($post_type, $this->post_types))
        {
            add_meta_box(
                    'multi_image_upload_meta_box'
                    , __('Upload Image Slider', 'miu_textdomain')
                    , array($this, 'set_meta_box_content')
                    , $post_type
                    , 'advanced'
                    , 'high'
            );
        }
    }

    public function save($post_id)
    {
        if (!isset($_POST['miu_inner_custom_box_nonce']))
            return $post_id;

        $nonce = $_POST['miu_inner_custom_box_nonce'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'miu_inner_custom_box'))
            return $post_id;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        if ('page' == $_POST['post_type'])
        {

            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else
        {

            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        $posted_images = $_POST['miu_images'];
        $miu_images = array();
        if (!empty($posted_images))
        {
            foreach ($posted_images as $image_url)
            {
                if (!empty($image_url))
                    $miu_images[] = esc_url_raw($image_url);
            }
        }

        // Update the miu_images meta field.
        update_post_meta($post_id, 'miu_images', serialize($miu_images));
    }

    public function set_meta_box_content($post)
    {

        // Add an nonce field so we can check for it later.
        wp_nonce_field('miu_inner_custom_box', 'miu_inner_custom_box_nonce');

        // Use get_post_meta to retrieve an existing value from the database.
        $value = get_post_meta($post->ID, 'miu_images', true);

        $metabox_content = '<div id="miu_images"></div><input type="button" onClick="addRow()" value="Add Image" class="button" />';
        echo $metabox_content;

        $images = unserialize($value);

        $script = "<script>
            itemsCount= 0;";
        if (!empty($images))
        {
            foreach ($images as $image)
            {
                $script.="addRow('{$image}');";
            }
        }
        $script .="</script>";
        echo $script;
    }

    function enqueue_scripts($hook)
    {
        if ('post.php' != $hook && 'post-edit.php' != $hook && 'post-new.php' != $hook)
            return;
        wp_enqueue_script('hm_script', plugin_dir_url(__FILE__) . 'hm_script.js', array('jquery'));
    }

}

/* end here */


add_action('wp_enqueue_scripts', 'slider_scripts');
function slider_scripts() {
	wp_enqueue_script('jquery');
	wp_register_script('slidesjs_core', plugins_url('js/jquery.slides.min.js', __FILE__),array("jquery"));
	wp_enqueue_script('slidesjs_core');
	
	wp_register_script('slidesjs_init', plugins_url('js/slidesjs.initialize.js', __FILE__));
	wp_enqueue_script('slidesjs_init');
	
}

add_action('wp_enqueue_scripts', 'slider_styles');
function slider_styles() {
	wp_register_style('easy-slider', plugins_url('css/easy-slider.css', __FILE__));
	wp_enqueue_style('easy-slider');	
	
}

add_shortcode("wp_easy_image_slider", "display__image_slider");
function display__image_slider($attr,$content) {
	extract(shortcode_atts(array(
			'id' => ''
			), $attr));
		$args = array(
			'post_type' => 'slider',
			'numberposts' => -1,
			'post_parent' => $id
		);
		$images = miu_get_images($id); 
		 //echo "<pre>";print_r($images);
	 
		if ( $images ){
			$html = '<div class="container"><div class="easy-slides">';
        	foreach ( $images as $img ){
			//echo $img;
				//echo $attachment->ID;
				//$gallery_images = wp_get_attachment_image( $img->ID, 'full' );
				$gallery_images = "<img src='{$img}'>";
				 $html .= $gallery_images;
			}
			$html .= '</div></div>';  
		}
		return $html;
}

add_action('init', 'wp_easy_slider');
function wp_easy_slider() {
	$labels = array(
		'name' => 'All Slider',
		'menu_name' => 'WP Image Slider',
		'add_new' => 'Add New Slider',
        'add_new_item' => 'Add New Slider',
        'edit_item' => 'Edit Slider'
        
	);
	$args = array(
		
		'labels' => $labels,
		'hierarchical' => true,
		'description' => 'Slider',
		'supports' => array('title', 'editor'),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'has_archive' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'supports' => array( 'title', 'thumbnail') 
	);
	register_post_type('slider', $args);
}

add_filter('manage_edit-slider_columns', 'simple_set_custom_edit_slider_columns');
add_action('manage_slider_posts_custom_column', 'simple_custom_slider_column', 10, 2);

function simple_set_custom_edit_slider_columns($columns) {
	return $columns
	+ array('slider_shortcode' => __('Shortcode'));
}

function simple_custom_slider_column($column, $post_id) {
	$slider_meta = get_post_meta($post_id, "_slider_meta", true);
	$slider_meta = ($slider_meta != '') ? json_decode($slider_meta) : array();

	switch ($column){
		case 'slider_shortcode':
			echo "[wp_easy_image_slider id=$post_id]";
		break;
    }
}

add_action('save_post', 'simple_save_slider_info');
function simple_save_slider_info($post_id) {
	if(isset($_POST['wp_easy_box_nonce']) && $_POST['post_type'])
	{		
		if (!wp_verify_nonce($_POST['wp_easy_box_nonce'], basename(__FILE__))){
			return $post_id;
		}
	
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $post_id;
		}
		if ('slider' == $_POST['post_type'] && current_user_can('edit_post', $post_id)){
			$gallery_images = (isset($_POST['slider_img']) ? $_POST['slider_img'] : '');
			$gallery_images = strip_tags(json_encode($gallery_images));
			update_post_meta($post_id, "_easy_gallery_images", $gallery_images);
		}else{
			return $post_id;
		}
	}
 
}
?>