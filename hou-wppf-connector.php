<?php
/*
Plugin Name: WP Property Feed to Houzez Theme Connector
 Plugin URI: http://www.wppropertyfeed.co.uk/
 Description: This plugin will take your property feed using WP Property Feed Plugin and map the feed to the Houzez theme properties.
 Version: 1.39
 Author: Ultimateweb Ltd
 Author URI: http://www.ultimateweb.co.uk
 Text Domain: wp-property-feed-to-houzez-theme-connector
 License: GPL2
*/

/*  Copyright 2021  Ian Scotland, Ultimateweb Ltd  (email : info@ultimateweb.co.uk)
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

error_reporting(E_ALL);
ini_set('display_errors', '1');
*/

defined('ABSPATH') or die("No script kiddies please!");
$wppf_hou_version = '1.39';

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
register_activation_hook( __FILE__, 'wppf_hou_install');
register_deactivation_hook( __FILE__, 'wppf_hou_uninstall' );

add_action('init', 'wppf_hou_init',1);
add_action('admin_init', 'wppf_hou_admin_init');
add_action('wppf_after_admin_init', 'wppf_hou_admin_init');
add_action('wppf_settings_tabs','wppf_hou_tab');
add_action('wppf_settings_sections','wppf_hou_section');
add_action('admin_notices', 'wppf_hou_notice_houzez');
add_action('wppf_after_settings_updated', 'wppf_hou_settings_updated', 101, 1);
add_action('wppf_hou_incremental','wppf_hou_populate');
add_action('wppf_register_scripts', 'wppf_hou_register_scripts');

function wppf_hou_install() {
    wp_clear_scheduled_hook('wppf_hou_incremental');
    wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_hou_incremental');
}

function wppf_hou_uninstall() {
    delete_option("wppf_theme");
}

function wppf_hou_init() {
    //are the theme and plugin active
    if (strtolower(get_template()) == 'houzez' && is_plugin_active('wp-property-feed/wppf_properties.php')) {
        add_action('admin_menu', 'wppf_hou_admin_menu');
        add_filter('wppf_filter_slug', function($title) { return "wppf-property";});
        update_option("wppf_theme", "houzez");
        if (!wp_get_schedule('wppf_hou_incremental')) wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_hou_incremental');  //re-instate schedule in case it drops away!
    } else {
        delete_option("wppf_theme");
    }
    add_shortcode("hou_propertyfile_valuation", "hou_propertyfile_valuation");
    add_shortcode("hou_propertyfile_viewing", "hou_propertyfile_viewing");
    add_filter('template_include', 'hou_template_include');
}

function wppf_hou_notice_houzez() {
    if (strtolower(get_template()) != 'houzez' || !is_plugin_active('wp-property-feed/wppf_properties.php')) {
        echo "<div class='notice notice-error'><p><img align='left' src='".plugins_url('wppf.png', __FILE__)."' alt='WP Property Feed' style='margin: -10px 10px -1px -10px' /> <strong>WPPF Houzez</strong><br>The Houzez WPPF Property Theme plugin is currently inactive as it requires both the Houzez theme to be the current theme and also for the WP Property Feed plugin to be active.</p><div style='clear:both'></div></div>";
    }
}

function wppf_hou_register_scripts() {
    wp_dequeue_script('google_maps');
}

function wppf_hou_admin_menu() {
    //check for theme and plugin and show notice it they do not exist and are not active
    if (get_template() == 'houzez' && is_plugin_active('wp-property-feed/wppf_properties.php')) remove_menu_page('edit.php?post_type=wppf_property');
}

function wppf_hou_tab() {
    echo "<a href='#houzez' class='nav-tab'>Houzez</a>";
}

function wppf_hou_section() {
    echo "<div class='wppf_tab_section' id='houzez' style='display:none'>";
    do_settings_sections('wppf_hou_feed');
    $wppf_hou_propertycount = get_option("wppf_hou_propertycount");
    if (!empty($wppf_hou_propertycount)) {
        echo 'There are currently ' . $wppf_hou_propertycount.' feed properties listed in the theme.<br /><br />';
        echo '<strong>Last 10 Updates:</strong><br /><table border="0">';
        if ($logs = get_option('wppf_hou_log')) {
            foreach($logs as $log) {?>
                <tr>
                    <td><?php echo $log['logdate']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;updated: <?php echo $log['updated']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;deleted: <?php echo $log['deleted']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;total: <?php echo $log['totalproperties']; ?></td>
                </tr>
            <?php
            }
        }
        echo '</table><br /><br />';
    }
    echo '</div>';
}

function wppf_hou_admin_init() {
    add_settings_section('wppf_main', 'Houzez', 'wppf_hou_section_text', 'wppf_hou_feed');
    add_settings_field('wppf_fh_summary', 'Add Property Summary to Description', 'wppf_hou_setting_summary', 'wppf_hou_feed', 'wppf_main');
    add_settings_field('wppf_fh_refresh', 'Update Houzez properties now', 'wppf_hou_setting_refresh', 'wppf_hou_feed', 'wppf_main');
}

function wppf_hou_section_text() {
    echo '<p>Congratulations you and now set up up and the property feed plugin will automatically feed properties into the Houzez theme every 15 minutes.</p>';
}

function wppf_hou_setting_summary() {
    $options = get_option('wppf_options');
    if (empty($options['houzez_summary'])) $options['houzez_summary']="";
    if ($options['houzez_summary']==1)
        echo "<input type='checkbox' name='wppf_options[houzez_summary]' value='1' checked='checked' />";
    else
        echo "<input type='checkbox' name='wppf_options[houzez_summary]' value='1' />";
}

function wppf_hou_setting_refresh() {
    echo "<input type='checkbox' name='wppf_options[houzez_refresh]' value='1' /> tick to refresh the property data on save (please be patient it can take a while!)";
}

function wppf_hou_settings_updated() {
    $options = get_option('wppf_options');
    if (isset($options['houzez_refresh'])) {
        wppf_hou_populate();
    }
}

function hou_template_include($template) {
    if (is_archive() && is_post_type_archive('property')) {
        //add redirect for profileID email links
        $profileID = (isset($_GET['profileID'])) ? $_GET['profileID']:null;
        if(!empty($profileID) && is_numeric($profileID)) {
            $args = array('post_type' => 'property', 'numberposts' => -1, 'meta_query' => array(array('key' => 'wppf_id', 'value' => (string)$profileID, 'compare' => 'LIKE')));
            $mposts = get_posts($args);
            if (!empty($mposts)) {
                $propertylink = get_permalink($mposts[0]);
                wp_redirect( $propertylink, 301 );
                exit;
            }
        }
    }
    return $template;
}


function wppf_hou_purge() {
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
    foreach ($rhposts as $post) {
        wppf_purge_post($post->ID);
    }
}
add_action("wppf_purge","wppf_hou_purge");

function wppf_hou_populate() {
    set_time_limit(0);
    //ini_set('max_execution_time', 300);
    $options = get_option('wppf_options');
    wppf_log("Propery","Houzez","","Started","Updated");

    //fix for older version of the wppf plugin
    $tax_prefix=(floatval(explode(".",get_option('wppf_version'))[1]) > 50) ? "wppf_": "";

    //clear the image log
    update_option("wppf_hou_imagelog", null);

    $mposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_query' => array('relation' => 'AND', array('key' => 'wppf', 'value' => '1', 'compare' => '='),array('key' => 'remove', 'value' => true, 'compare' => '='))));
    if (count($mposts)==0) {
        //mark all properties to delete (we will un-mark any that are in the feed) - only do this if we have successfully connected to the API, hence it is here
        $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
        foreach ($rhposts as $post) {
            update_post_meta($post->ID,'remove',true);
        }
    }

    //Update agents
    $terms = get_terms(array('taxonomy' => $tax_prefix.'branch'));
    foreach ($terms as $branch) {
        $postid = 0;
        $rhposts = get_posts(array('post_type' => 'houzez_agent', 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
        if (empty($rhposts)) {
            $agent = array('ID' => $postid,
                'post_title' => $branch->name,
                'post_status' => 'publish',
                'post_type' => 'houzez_agent');
            $postid = wp_insert_post($agent);
            $phone = get_term_meta($branch->ID, 'phone');
            $email = get_term_meta($branch->ID, 'email');
            add_post_meta($postid,'fave_agent_company',$branch->name,true);
            if (!empty($phone)) add_post_meta($postid,'fave_agent_office_num',$phone,true);
            if (!empty($email)) add_post_meta($postid,'fave_agent_email',$email,true);
            add_post_meta($postid,'wppf_slug',$branch->slug,true);
        }
    }

    //Main update loop
    $updated = 0;
    $wposts = get_posts(array('post_type' => 'wppf_property', 'numberposts' => -1));
    foreach ($wposts as $property) {
        $postid = 0; $isNew = false; $changed=true; $nothumb = true;
        $wppfid = get_post_meta($property->ID,'wppf_id',true);
        $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf_id', 'meta_value' => $wppfid));
        if (!empty($rhposts)) {
            $postid = $rhposts[0]->ID;
            if (get_post_meta($postid,'remove',true)===false) {  //if already marked to keep then ignore - allows feed to resume
                $changed = false;
            } else {
                $plast = get_post_meta($property->ID,'updated',true);  //date the feed property was last updated
                $tlast = get_post_meta($postid,'updated',true); //date the houzez property was last updated
                if (!empty($plast) && !empty($tlast)) {
                    if (strtotime($plast) < strtotime($tlast)) {
                        $changed = false;
                        update_post_meta($postid,'remove',false);
                    }
                }
            }
        }
        if ($postid == 0) $isNew = true;

        if ($changed || $isNew) {
            $postcontent = "";
            if ($options['houzez_summary']=="1") $postcontent .= $property->post_excerpt;
            $postcontent .= "<p>".$property->post_content."</p>";
            $paragraphs = wppf_get_paragraphs($property->ID);
            if (!empty($paragraphs)) {
                foreach($paragraphs as $paragraph) {
                    if ($paragraph['name']!='') $postcontent .= "<h4>".$paragraph['name']."</h4>";
                    $postcontent.="<p>";
                    if ($paragraph['dimensions']!="") $postcontent .= "<em>".$paragraph['dimensions']."</em><br />";
                    $postcontent.=$paragraph['description']."</p>";
                }
            }
            $counciltaxband = get_post_meta($property->ID,'counciltaxband',true);
            if (!empty($counciltaxband)) $postcontent.="<div class='ctaxband'><span>Council Tax Band:</span> ".$counciltaxband."</div>";

            //set up key datafields
            $prop = array('ID' => $postid,
                'post_title' => $property->post_title,
                'post_excerpt' => $property->post_excerpt,
                'post_content' => $postcontent,
                'post_status' => 'publish',
                'post_type' => 'property',
                'post_date' => get_post_meta($property->ID,'uploaded',true),
                'post_modified' => get_post_meta($property->ID,'updated',true));
            $postid = wp_insert_post($prop);
            update_post_meta($postid,'wppf','1');
            update_post_meta($postid,'wppf_id',$wppfid);
            update_post_meta($postid,'updated',get_post_meta($property->ID,'updated',true));
            update_post_meta($postid,'remove',false);
            if (get_post_meta($property->ID,'price_display',true)=='0' || get_post_meta($property->ID,'price_qualifier',true)=='POA') {
                update_post_meta($postid,'fave_property_price','');
                update_post_meta($postid,'fave_property_price_postfix','');
            } else {
                update_post_meta($postid,'fave_property_price_prefix',get_post_meta($property->ID,'price_qualifier',true));
                update_post_meta($postid,'fave_property_price',get_post_meta($property->ID,'price',true));
                update_post_meta($postid,'fave_property_price_postfix',get_post_meta($property->ID,'price_postfix',true));
            }
            $propertyarea_sqft = get_post_meta($property->ID,'propertyarea_sqft',true);
            if (!empty($propertyarea_sqft)) {
                update_post_meta($postid,'fave_property_size',$propertyarea_sqft);
                update_post_meta($postid,'fave_property_size_prefix','sqft');
            }
            update_post_meta($postid,'fave_property_bedrooms',get_post_meta($property->ID,'bedrooms',true));
            update_post_meta($postid,'fave_property_bathrooms',get_post_meta($property->ID,'bathrooms',true));
            update_post_meta($postid,'fave_property_garage',get_post_meta($property->ID,'parking',true));
            update_post_meta($postid,'fave_property_id',get_post_meta($property->ID,'agentref',true));

            update_post_meta($postid,'fave_property_map','1');
            update_post_meta($postid,'fave_property_map_address',get_post_meta($property->ID,'address_all',true));
            update_post_meta($postid,'fave_property_location',get_post_meta($property->ID,'latitude',true).",".get_post_meta($property->ID,'longitude',true));
            update_post_meta($postid,'fave_property_country','GB');
            update_post_meta($postid,'fave_property_address',get_post_meta($property->ID,'address_all',true));
            update_post_meta($postid,'fave_property_zip',get_post_meta($property->ID,'address_postcode',true));

            update_post_meta($postid,'fave_epc_current_rating',get_post_meta($property->ID,'eer_current',true));
            update_post_meta($postid,'fave_epc_potential_rating',get_post_meta($property->ID,'eer_potential',true));
            
            update_post_meta($postid,'fave_featured',get_post_meta($property->ID,'featured',true));
            update_post_meta($postid,'fave_agent_display_option','agent_info');
            delete_post_meta($postid,'fave_agents'); //clear current agents
            $branches = wp_get_post_terms($property->ID, $tax_prefix."branch");
            foreach ($branches as $branch) {
                $tbs = get_posts(array('post_type' => 'houzez_agent', 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
                foreach($tbs as $tb) {
                    update_post_meta($postid,'fave_agents',$tb->ID);
                }
            }

            delete_post_meta($postid,'fave_property_images'); //clear property images
            delete_post_meta($postid,'fave_attachments'); //clear property images

            $files = get_post_meta($property->ID,'files');
            $outfiles = array();
            $floorplans = array();

            if (isset($files[0])) {
                $ifiles = $files[0];
                $sfiles = array(); $tfiles = array();
                foreach ($ifiles as $key => $row)
                {
                    $tfiles[$key] = (int)$row['filetype'];
                    $sfiles[$key] = (int)$row['sortorder'];
                }
                array_multisort($tfiles, SORT_ASC, $sfiles, SORT_ASC, $ifiles);
                $videotour = "";
                foreach($ifiles as $file) {
                    switch((int)$file['filetype']) {
                        case 0: //picture
                        case 1: //picture map
                            if (!wppf_hou_check_attachment($file['attachmentid'],$postid,$file['sortorder'])) {
                                if ($aid = wppf_hou_upload_file($file['url'],$postid,$file['sortorder'])) {
                                    add_post_meta($postid,'fave_property_images',$aid);
                                    $file['attachmentid'] = $aid;
                                }
                            }
                            add_post_meta($postid,'fave_property_images',$file['attachmentid']);
                            break;
                        case 2: //floorplans
                            $floorplans[] = array("fave_plan_title"=> (empty($file['name']) ? "Floorplan" : $file['name']), "fave_plan_image"=> $file['url']);
                            break;
                        case 7: //Documents
                        case 9: //EPCs
                            $title = ((int)$file['filetype']==7) ? "Brochure" : "EPC";
                            if (!wppf_hou_check_attachment($file['attachmentid'],$postid,$file['sortorder'])) {
                                if ($aid = wppf_hou_upload_file($file['url'],$postid,$file['sortorder'],$title)) {
                                    $file['attachmentid'] = $aid;
                                }
                            }
                            add_post_meta($postid,'fave_attachments',$file['attachmentid']);
                            break;
                    }
                    $outfiles[] = $file;
                }
                update_post_meta($property->ID,'files',$outfiles);
                if (!empty($floorplans)) {
                    update_post_meta($postid,'floor_plans', $floorplans);
                    update_post_meta($postid,'fave_floor_plans_enable', 'enable');
                }
            }

            $hasvideo = false; $hasvirtual = false;
            $videotour = wppf_get_video_tour($property->ID);
            if (!strpos($videotour,'you')) {
                $videotour = '<iframe src="'.$videotour.'" style="width: 100%; height: 75vh" frameborder="0" allow="fullscreen" allow="xr-spatial-tracking"></iframe>';
                update_post_meta($postid,'fave_virtual_tour', $videotour);
                $hasvirtual = true;
            } else {
                update_post_meta($postid,'fave_video_url', str_replace("embed/", "watch?v=", $videotour));
                $hasvideo = true;
            }

            $videotour2 = wppf_get_video_tour($property->ID,2);
            if (!empty($videotour2)) {
                if (!strpos($videotour2,'you') && !$hasvirtual) {
                    $videotour = '<iframe src="'.$videotour2.'" style="width: 100%; height: 75vh" frameborder="0" allow="fullscreen" allow="xr-spatial-tracking"></iframe>';
                    update_post_meta($postid,'fave_virtual_tour', $videotour2);
                } else {
                    if (!$hasvideo) update_post_meta($postid,'fave_video_url', str_replace("embed/", "watch?v=", $videotour2));
                }
            }
            
 
            //get property sales areas (For Sale, To Let, etc) and add to property status
            wp_delete_object_term_relationships($postid, "property_status");
            $areas = wp_get_post_terms($property->ID, $tax_prefix."property_area");
            foreach ($areas as $area) {
                wp_set_object_terms($postid,$area->name,"property_status",true);
            }

            //get property Status (For Sale, Sold, SSTC, To Let, etc) and add to labels
            //wp_delete_object_term_relationships($postid, "property_label");
            wp_set_object_terms($postid,get_post_meta($property->ID,'web_status',true),"property_label");

            //get property type (detached, semi, etc) and add to property types
            wp_delete_object_term_relationships($postid, "property_type");
            $ptypes = wp_get_post_terms($property->ID, $tax_prefix."property_type");
            foreach ($ptypes as $ptype) {
                wp_set_object_terms($postid,$ptype->name,"property_type",true);
            }

            //get City/locality locations
            //wp_delete_object_term_relationships($postid, "property_city");
            wp_set_object_terms($postid,get_post_meta($property->ID,'address_town',true),"property_city");
            //wp_delete_object_term_relationships($postid, "property_state");
            wp_set_object_terms($postid,get_post_meta($property->ID,'address_county',true),"property_state");

            //turn bullets into property features
            wp_delete_object_term_relationships($postid, "property_feature");
            $bullets = get_post_meta($property->ID,'bullets',true);
            if (!empty($bullets)) {
                $dom = new DOMDocument;
                try {
                    $dom->loadHTML($bullets);
                    $lis = $dom->getElementsByTagName('li');
                    foreach ($lis as $li) {
                        wp_set_object_terms($postid,$li->textContent,"property_feature",true);
                    }
                }
                catch (Exception $e) {
                    //just carry on
                }
            }
            $updated++;
        }
        //do thumbnail bit here
        if (has_post_thumbnail($property->ID)) {
            $sourcethumbid = get_post_thumbnail_id($property->ID);
            if (is_numeric($sourcethumbid)) {
                set_post_thumbnail($postid, $sourcethumbid);
                wp_update_post(array('ID' => $sourcethumbid, 'post_parent' => $postid));
            }
        }
        wppf_log("Propery","Houzez","",get_the_title($postid),"Updated");
        do_action('wppf_houzez_process', $postid, $property);
    }

    //Now remove any properties that were not in the feed
    $deleted = 0;
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_query' => array('relation' => 'AND', array('key' => 'wppf', 'value' => '1', 'compare' => '='),array('key' => 'remove', 'value' => true, 'compare' => '='))));
    foreach ($rhposts as $post) {
        wppf_purge_post($post->ID);
        $deleted++;
    }


    $property_count = 0;
    //update total count
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key'=>'wppf', 'meta_value'=>'1'));
    if (is_array($rhposts)) {
        $property_count = count($rhposts);
        update_option("wppf_hou_propertycount", $property_count);
    }

    //update logs keeping just the last 10
    $date = new DateTime();
    $log = get_option("wppf_hou_log");
    if (is_array($log)) {
        array_unshift($log,array('logdate' => $date->format('d/m/Y H:i:s'),'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
        $log = array_slice($log,0,10);
    } else {
        $log = array(array('logdate' => $date->format('d/m/Y H:i:s'), 'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
    }
    update_option("wppf_hou_log", $log);
}

function wppf_hou_check_attachment($attachment_id, $post_id, $sortorder) {
    if (!empty($attachment_id)) {
        $image = wp_get_attachment_image_src($attachment_id);
        wp_update_post( array('ID' => $attachment_id, 'post_parent' => $post_id, 'menu_order' => $sortorder));
        if ($image) return true;
    }
    return false;
}

function wppf_hou_upload_file($url, $postid, $sortorder,$title="") {
    $mimetype = "image/jpeg";
    $parts = pathinfo($url);
    switch ($parts['extension']) {
        case "gif":
            $mimetype = "image/gif";
            break;
        case "png":
            $mimetype = "image/png";
            break;
    }
    if (empty($title)) $title =  preg_replace( '/\.[^.]+$/', '', basename($url));
    $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $mimetype,
            'post_title'     => $title,
            'post_content'   => 'cdn',
            'post_status'    => 'inherit',
            'post_parent'    => $postid,
            'menu_order'     => $sortorder
        );
    $attach_id = wp_insert_attachment($attachment, basename($url), $postid);
    //fake the attachment data so it will display in the gallery
    $imagemeta = array("aperture" => "0", "credit" => "", "camera" => "", "caption" => "", "created_timestamp" => "0", "copyright" => "", "focal_length" => "0", "iso" => "0", "shutter_speed" => "0", "title" => "", "orientation" => "1", "keywords" => array());
    $meta = array("width" => "300", "height" => "200", "file" => preg_replace( '/\.[^.]+$/', '', basename($url)), "image_meta" => $imagemeta);
    wp_update_attachment_metadata($attach_id, $meta);
    return $attach_id;
}

if (!function_exists("get_image_sizes")) {
    function get_image_sizes() {
	    global $_wp_additional_image_sizes;
	    $sizes = array();
	    foreach ( get_intermediate_image_sizes() as $_size ) {
		    if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
			    $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			    $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			    $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		    } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			    $sizes[ $_size ] = array(
				    'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				    'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			    );
		    }
	    }
	    return $sizes;
    }
}

if (!function_exists("wppf_image_downsize")) {
    function wppf_image_downsize($downsize, $id, $size) {
        //if this is a cdn image then server that
        $attachment = get_post($id);
        if ($attachment->post_content == 'cdn') {
            $sizes = get_image_sizes();
            if (is_array($size)) $size='thumbnail';
            if (isset($sizes[$size])) {
                return array($attachment->guid, $sizes[$size]['width'], $sizes[$size]['height'], $sizes[$size]['crop']);
            } else {
                return array($attachment->guid, '100', '75', true);
            }
        }
        return false;
    }
    add_filter('image_downsize', 'wppf_image_downsize', 10, 3);
}

if (!function_exists("wppf_attachment_cdn_url")) {
    function wppf_attachment_cdn_url($url, $id) {
        $attachment = get_post($id);
        if ($attachment->post_content == 'cdn') {
		    $url = $attachment->guid;
	    }
	    return $url;
    }
    add_filter('attachment_link', 'wppf_attachment_cdn_url', 10, 2 );
    add_filter('wp_get_attachment_url', 'wppf_attachment_cdn_url', 10, 2);
    add_filter('wp_get_attachment_thumb_url', 'wppf_attachment_cdn_url', 10, 2);
}

function wppf_hou_published($the_date, $overide, $post) {
    if ($post->post_type == 'property') {
        $wppf_slug = get_post_meta($post->ID,'wppf_slug',true);
        if (!empty($wppf_slug)) {
            $updated = get_post_meta($post->ID,'updated',true);
            $the_date = mysql2date(get_option( 'date_format' ), $updated);
        }
    }
    return $the_date;
}
add_filter('get_the_date', 'wppf_hou_published', 10, 3);

function hou_propertyfile_valuation($atts) {
    $wppf_atts = shortcode_atts(array(
		'class' => '',
        'text' => 'Request a Valuation'
	), $atts);
    $options = get_option('wppf_options');
    if (!empty($options['jupix_subdomain']) && !empty($options['jupix_apikey_valuation'])) {
        $classes = "pf-request-appraisal-button". (($wppf_atts['class'] == "") ? "" : " ".$wppf_atts['class']);
        return "<a href='#' class='".$classes."'>".$wppf_atts['text']."</a>";
    }
    return false;
}

function hou_propertyfile_viewing($atts) {
    $wppf_atts = shortcode_atts(array(
		'class' => '',
        'text' => 'Request a Viewing',
        'propertyid' => ''
	), $atts);
    $post_id = get_the_ID();
    if (empty($wppf_atts['propertyid'])) {
        $propertyid = str_replace("V","",str_replace("J","",get_post_meta($post_id,'wppf_id', true)));
    } else {
        $propertyid=$wppf_atts['propertyid'];
    }
    $options = get_option('wppf_options');
    if (!empty($options['jupix_subdomain']) && !empty($options['jupix_apikey'])) {
        $classes = "pf-request-viewing-button". (($wppf_atts['class'] == "") ? "" : " ".$wppf_atts['class']);
        return "<a href='#' class='".$classes."' data-property-id='".$propertyid."'>".$wppf_atts['text']."</a>";
    }
    return false;
}
