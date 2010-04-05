<?php
/*
Plugin Name: NextGEN Gallery Sidebar Widget
Plugin URI: http://ailoo.net
Description: A widget to show random galleries with preview image.
Author: Mathias Geat
Version: 0.3
Author URI: http://ailoo.net/
*/

/**
 * Changelog
 * ---------
 *
 * 0.3              Wordpress 2.8+ Widget API
 *                  Gallery exclusion option
 * 0.2.2            Add gallery_thumbnail option to select thumbnail image (preview, first, random)
 */
 
add_action('widgets_init', create_function('', 'return register_widget("NextGEN_Gallery_Sidebar_Widget");'));
 
class NextGEN_Gallery_Sidebar_Widget extends WP_Widget
{
    function NextGEN_Gallery_Sidebar_Widget()
    {
		$widget_ops = array('classname' => 'ngg-sidebar-widget', 'description' => 'A widget to show random galleries with preview image.');
		$this->WP_Widget('ngg-sidebar-widget', 'NextGEN Gallery Sidebar Widget', $widget_ops);
    }
    
	function widget($args, $instance)
    {
        global $wpdb;
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
   
        switch($instance['gallery_order']) {
            case 'added_desc':
                $order = 'gid DESC';
                break;
            case 'added_asc':
                $order = 'gid ASC';
                break;
            default:
                $order = 'RAND()';
                break;
        }
        
        $excluded_galleries = array();
        $exc = explode(',', $instance['excluded_galleries']);
        foreach($exc as $ex) {
            $ex = trim($ex);
            if(is_numeric($ex)) {
                $excluded_galleries[] = $ex;
            }
        }        
                
        $results = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY " . $order . " LIMIT " . $instance['max_galleries']);
        if(is_array($results) && count($results) > 0) {
            $galleries = array();
            foreach($results as $result) {
                if(!in_array($result->gid, $excluded_galleries)) {
                    if($wpdb->get_var("SELECT COUNT(pid) FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "'") > 0) {
                        if($instance['gallery_thumbnail'] == 'preview' && (int)$result->previewpic > 0) {
                            // ok
                        } elseif($instance['gallery_thumbnail'] == 'random') {
                            $result->previewpic = $wpdb->get_var("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "' ORDER BY RAND() LIMIT 1");
                        } else {
                            // else take the first image
                            $result->previewpic = $wpdb->get_var("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "' ORDER BY sortorder ASC, pid ASC LIMIT 1");
                        }
                        
                        $galleries[] = $result;
                    }
                }
            }
            
            if(count($galleries) > 0) {
                $title = $instance['title'];
                if(isset($instance['default_link']) && trim($instance['default_link']) != '') {
                    $title = '<a href="' . get_permalink($instance['default_link']) . '">' . $instance['title'] . '</a>';
                }
            
                $output = "\n";
                $output .= $args['before_widget'] . "\n";
                $output .= $args['before_title'] . $title . $args['after_title'] . "\n";
                
                foreach($galleries as $gallery) {
                    $imagerow = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '" . $gallery->previewpic . "'");
                    foreach($gallery as $key => $value) {
                        $imagerow->$key = $value;
                    }
                    
                    $image = new nggImage($imagerow);
                    
                    if($gallery->pageid > 0) {
                        $gallery_link = get_permalink($gallery->pageid);
                    } elseif(!empty($instance['default_link'])) {
                        $gallery_link = get_permalink($instance['default_link']);
                    } else {
                        $gallery_link = get_permalink(1);
                    }
                    
                    $output .= '<a href="' . $gallery_link . '">';
                    
                    if(function_exists('getphpthumburl') && trim($instance['autothumb_params']) != '') {
                        $output .= '<img src="' . getphpthumburl($image->imageURL, $instance['autothumb_params']) . '" title="' . $gallery->title . '" alt="' . $gallery->title . '" />';
                    } else {
                        $output .= '<img src="' . $image->thumbURL . '" title="' . $gallery->title . '" alt="' . $gallery->title . '" width="' . $instance['output_width'] . '" height="' . $instance['output_height'] . '" />';               
                    }
                    
                    $output .= '</a>';
                }
                
                $output .= '<br style="clear: both" />';
                $output .= "\n" . $args['after_widget'] . "\n";
                echo $output;
            }
        }
	}

	function form($instance)
    {
		$instance = wp_parse_args((array) $instance, array( 
            'title' => 'Galleries',
            'max_galleries' => 6,
            'gallery_order' => 'random',
            'gallery_thumbnail' => 'first',
            'autothumb_params' => '',
            'output_width' => 100,
            'output_height' => 75,
            'default_link' => 1,
            'excluded_galleries' => ''
        ));
    ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Widget Title</label><br />
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title'] ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('max_galleries'); ?>">Maximum Galleries</label><br />
            <input type="text" id="<?php echo $this->get_field_id('max_galleries'); ?>" name="<?php echo $this->get_field_name('max_galleries'); ?>" value="<?php echo $instance['max_galleries'] ?>" />
        <p>
            <label for="<?php echo $this->get_field_id('gallery_order'); ?>">Gallery Order</label><br />
            <select id="<?php echo $this->get_field_name('gallery_order'); ?>" name="<?php echo $this->get_field_name('gallery_order'); ?>">';
                <option value="random" <?php echo ($instance['gallery_order'] == 'random') ? ' selected="selected"' : ''; ?>>Random</option>
                <option value="added_asc" <?php echo ($instance['gallery_order'] == 'added_asc') ? ' selected="selected"' : ''; ?>>Date added ASC</option>
                <option value="added_desc" <?php echo ($instance['gallery_order'] == 'added_desc') ? ' selected="selected"' : ''; ?>>Date added DESC</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('gallery_thumbnail'); ?>">Gallery thumbnail image</label><br />
            <select id="<?php echo $this->get_field_name('gallery_thumbnail'); ?>" name="<?php echo $this->get_field_name('gallery_thumbnail'); ?>">';
              <option value="preview" <?php echo ($instance['gallery_thumbnail'] == 'preview') ? ' selected="selected"' : ''; ?>>Gallery Preview (set in NGG)</option>
              <option value="first" <?php echo ($instance['gallery_thumbnail'] == 'first') ? ' selected="selected"' : ''; ?>>First</option>
              <option value="random" <?php echo ($instance['gallery_thumbnail'] == 'random') ? ' selected="selected"' : ''; ?>>Random</option>
            </select>
        </p>
        <p>
              <label for="<?php echo $this->get_field_id('autothumb_params'); ?>">Autothumb Parameters (if installed)</label><br />
              <input type="text" id="<?php echo $this->get_field_id('autothumb_params'); ?>" name="<?php echo $this->get_field_name('autothumb_params'); ?>" value="<?php echo $instance['autothumb_params'] ?>" />
        </p>    
        <p>
              <label for="<?php echo $this->get_field_id('output_width'); ?>">Output width</label><br />
              <input type="text" id="<?php echo $this->get_field_id('output_width'); ?>" name="<?php echo $this->get_field_name('output_width'); ?>" value="<?php echo $instance['output_width'] ?>" />
        </p>    
        <p>
              <label for="<?php echo $this->get_field_id('output_height'); ?>">Output height</label><br />
              <input type="text" id="<?php echo $this->get_field_id('output_height'); ?>" name="<?php echo $this->get_field_name('output_height'); ?>" value="<?php echo $instance['output_height'] ?>" />
        </p>
        <p>
              <label for="<?php echo $this->get_field_id('default_link'); ?>">Default Link Id (galleries without image page)</label><br />
              <input type="text" id="<?php echo $this->get_field_id('default_link'); ?>" name="<?php echo $this->get_field_name('default_link'); ?>" value="<?php echo $instance['default_link'] ?>" />
        </p>
        <p>
              <label for="<?php echo $this->get_field_id('excluded_galleries'); ?>">Excluded gallery IDs (comma separated)</label><br />
              <input type="text" id="<?php echo $this->get_field_id('excluded_galleries'); ?>" name="<?php echo $this->get_field_name('excluded_galleries'); ?>" value="<?php echo $instance['excluded_galleries'] ?>" />
        </p>
    <?php
	}

	function update($new_instance, $old_instance)
    {
        $new_instance['title'] = htmlspecialchars($new_instance['title']);
        return $new_instance;
	}
} 


 

//add_action('plugins_loaded', 'init_ngg_sidebar_gallery_widget');

/*function init_ngg_sidebar_gallery_widget()
{
    // check if NextGEN Gallery is installed
    if (class_exists('nggLoader')) {
        register_sidebar_widget('NextGEN Gallery Sidebar Widget', 'ngg_sidebar_gallery_widget');
        register_widget_control('NextGEN Gallery Sidebar Widget', 'ngg_sidebar_gallery_widget_control');
    }
}

function ngg_sidebar_gallery_widget($args)
{
    global $wpdb;
	extract($args);    
	$options = ngg_sidebar_gallery_widget_get_options();
            
    switch($options['gallery_order']) {
        case 'added_desc':
            $order = 'gid DESC';
            break;
        case 'added_asc':
            $order = 'gid ASC';
            break;
        default:
            $order = 'RAND()';
            break;
    }
            
    $results = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY " . $order . " LIMIT " . $options['max_galleries']);
    if(is_array($results) && count($results) > 0) {
        $galleries = array();
        foreach($results as $result) {
            if($wpdb->get_var("SELECT COUNT(pid) FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "'") > 0) {
                if($options['gallery_thumbnail'] == 'preview' && (int)$result->previewpic > 0) {
                    // ok
                } elseif($options['gallery_thumbnail'] == 'random') {
                    $result->previewpic = $wpdb->get_var("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "' ORDER BY RAND() LIMIT 1");
                } else {
                    // else take the first image
                    $result->previewpic = $wpdb->get_var("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "' ORDER BY sortorder ASC, pid ASC LIMIT 1");
                }
                
                $galleries[] = $result;
            }
        }
        
        if(count($galleries) > 0) {
            $title = $options['title'];
            if(isset($options['default_link']) && trim($options['default_link']) != '') {
                $title = '<a href="' . get_permalink($options['default_link']) . '">' . $options['title'] . '</a>';
            }
        
            $output = "\n";
            $output .= $before_widget . "\n";
            $output .= $before_title . $title . $after_title . "\n";
            
            foreach($galleries as $gallery) {
                $imagerow = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '" . $gallery->previewpic . "'");
                foreach($gallery as $key => $value) {
                    $imagerow->$key = $value;
                }
                
                $image = new nggImage($imagerow);
                
                if($gallery->pageid > 0) {
                    $gallery_link = get_permalink($gallery->pageid);
                } elseif(!empty($options['default_link'])) {
                    $gallery_link = get_permalink($options['default_link']);
                } else {
                    $gallery_link = get_permalink(1);
                }
                
                $output .= '<a href="' . $gallery_link . '">';
                
                if(function_exists('getphpthumburl') && trim($options['autothumb_params']) != '') {
                    $output .= '<img src="' . getphpthumburl($image->imageURL, $options['autothumb_params']) . '" title="' . $gallery->title . '" alt="' . $gallery->title . '" />';
                } else {
                    $output .= '<img src="' . $image->thumbURL . '" title="' . $gallery->title . '" alt="' . $gallery->title . '" width="' . $options['output_width'] . '" height="' . $options['output_height'] . '" />';               
                }
                
                $output .= '</a>';
            }
            
            $output .= '<br style="clear: both" />';
            $output .= "\n" . $after_widget . "\n";
            echo $output;
        }
    } 
}

function ngg_sidebar_gallery_widget_control()
{
    $options = ngg_sidebar_gallery_widget_get_options();
      
    if($_POST['ngg_sidebar_gallery_widget-submit']){
      $options['title'] = htmlspecialchars($_POST['ngg_sidebar_gallery_widget-title']);
      $options['max_galleries'] = $_POST['ngg_sidebar_gallery_widget-max_galleries'];
      $options['gallery_order'] = $_POST['ngg_sidebar_gallery_widget-gallery_order'];
      $options['gallery_thumbnail'] = $_POST['ngg_sidebar_gallery_widget-gallery_thumbnail'];
      $options['autothumb_params'] = $_POST['ngg_sidebar_gallery_widget-autothumb_params'];
      $options['output_width'] = $_POST['ngg_sidebar_gallery_widget-output_width'];
      $options['output_height'] = $_POST['ngg_sidebar_gallery_widget-output_height'];
      $options['default_link'] = $_POST['ngg_sidebar_gallery_widget-default_link'];
      update_option('ngg_sidebar_gallery_widget', $options);
    }
    
    switch($options['gallery_order']) {
        case 'random':
        case 'added_asc':
        case 'added_desc':
            break;
        default:
            $options['gallery_order'] = 'random';
            break;
    }
    
    switch($options['gallery_thumbnail']) {
        case 'preview':
        case 'first':
        case 'random':
            break;
        default:
            $options['gallery_thumbnail'] = 'preview';
            break;
    }  

    echo '<p>
        <label for="ngg_sidebar_gallery_widget-title">Widget Title</label>
        <input type="text" id="ngg_sidebar_gallery_widget-title" name="ngg_sidebar_gallery_widget-title" value="' . $options['title'] . '" />
        </p>
        <p>
              <label for="ngg_sidebar_gallery_widget-max_galleries">Maximum Galleries</label>
              <input type="text" id="ngg_sidebar_gallery_widget-max_galleries" name="ngg_sidebar_gallery_widget-max_galleries" value="' . $options['max_galleries'] . '" />
        </p>
        <p>
              <label for="ngg_sidebar_gallery_widget-gallery_order">Gallery Order</label><br />
              <select id="ngg_sidebar_gallery_widget-gallery_order" name="ngg_sidebar_gallery_widget-gallery_order">';
              
                  echo '<option value="random"';
                  echo ($options['gallery_order'] == 'random') ? ' selected="selected">' : '>';
                  echo 'Random</option>';
                  
                  echo '<option value="added_asc"';
                  echo ($options['gallery_order'] == 'added_asc') ? ' selected="selected">' : '>';
                  echo 'Date added ASC</option>';
                  
                  echo '<option value="added_desc"';
                  echo ($options['gallery_order'] == 'added_desc') ? ' selected="selected">' : '>';
                  echo 'Date added DESC</option>';
                  
              echo '</select>
        </p>
        <p>
              <label for="ngg_sidebar_gallery_widget-gallery_thumbnail">Gallery thumbnail image</label><br />
              <select id="ngg_sidebar_gallery_widget-gallery_thumbnail" name="ngg_sidebar_gallery_widget-gallery_thumbnail">';
              
                  echo '<option value="preview"';
                  echo ($options['gallery_thumbnail'] == 'preview') ? ' selected="selected">' : '>';
                  echo 'Gallery Preview (set in NGG)</option>';
                  
                  echo '<option value="first"';
                  echo ($options['gallery_thumbnail'] == 'first') ? ' selected="selected">' : '>';
                  echo 'First</option>';

                  echo '<option value="random"';
                  echo ($options['gallery_thumbnail'] == 'random') ? ' selected="selected">' : '>';
                  echo 'Random</option>';
                  
              echo '</select>
        </p>
        <p>
              <label for="ngg_sidebar_gallery_widget-autothumb_params">Autothumb Parameters (if installed)</label>
              <input type="text" id="ngg_sidebar_gallery_widget-autothumb_params" name="ngg_sidebar_gallery_widget-autothumb_params" value="' . $options['autothumb_params'] . '" />
        </p>    
        <p>
              <label for="ngg_sidebar_gallery_widget-output_width">Output width</label>
              <input type="text" id="ngg_sidebar_gallery_widget-output_width" name="ngg_sidebar_gallery_widget-output_width" value="' . $options['output_width'] . '" />
        </p>    
        <p>
              <label for="ngg_sidebar_gallery_widget-output_height">Output height</label>
              <input type="text" id="ngg_sidebar_gallery_widget-output_height" name="ngg_sidebar_gallery_widget-output_height" value="' . $options['output_height'] . '" />
        </p>
        <p>
              <label for="ngg_sidebar_gallery_widget-default_link">Default Link Id (galleries without image page)</label>
              <input type="text" id="ngg_sidebar_gallery_widget-default_link" name="ngg_sidebar_gallery_widget-default_link" value="' . $options['default_link'] . '" />
              <input type="hidden" id="ngg_sidebar_gallery_widget-submit" name="ngg_sidebar_gallery_widget-submit" value="1" />
        </p>';
}

function ngg_sidebar_gallery_widget_get_options()
{
	$options = get_option('ngg_sidebar_gallery_widget');
    
	if(!is_array($options)) {
		$options = array(
            'title' => 'Galleries',
            'max_galleries' => 6,
            'gallery_order' => 'random',
            'gallery_thumbnail' => 'first',
            'autothumb_params' => '',
            'output_width' => 100,
            'output_height' => 75,
            'default_link' => 1
        );
	}

    return $options;
}*/
