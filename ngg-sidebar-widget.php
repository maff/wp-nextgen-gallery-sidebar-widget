<?php
/*
Plugin Name: NextGEN Gallery Sidebar Widget
Plugin URI: http://ailoo.net
Description: A widget to show random galleries with preview image.
Author: Mathias Geat
Version: 0.2.1
Author URI: http://ailoo.net/
*/

add_action('plugins_loaded', 'init_ngg_sidebar_gallery_widget');

function init_ngg_sidebar_gallery_widget()
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
                if((int)$result->previewpic <= 0) {
                    $result->previewpic = $wpdb->get_var("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '" . $result->gid . "' ORDER BY RAND() LIMIT 1");
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
            
            $output .= '<br class="clear" />';
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
		$options['autothumb_params'] = $_POST['ngg_sidebar_gallery_widget-autothumb_params'];
		$options['output_width'] = $_POST['ngg_sidebar_gallery_widget-output_width'];
		$options['output_height'] = $_POST['ngg_sidebar_gallery_widget-output_height'];
		$options['default_link'] = $_POST['ngg_sidebar_gallery_widget-default_link'];
		update_option('ngg_sidebar_gallery_widget', $options);
	}
    
    switch($options['gallery_order']) {
        case 'random':
            break;
        case 'added_asc':
            break;
        case 'added_desc':
            break;
        default:
            $options['gallery_order'] = 'random';
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
            'autothumb_params' => '',
            'output_width' => 100,
            'output_height' => 75,
            'default_link' => 1
        );
	}

    return $options;
}
