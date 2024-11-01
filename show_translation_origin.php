<?php
/**
 * Plugin Name: Show Translation Origin
 * Plugin URI: http://www.triplebit.com/show-translation-origin/show-translation-origin.zip
 * Description: Displays original text above the localized.
 * Version: 1.9
 * Author: Izac Lesher
 * Author URI: http://itziklesher.triplebit.com
 * Text Domain: show-translation-origin
 * Domain Path: /languages/
 * License: GPL2
 *
 * Copyright 2015  Izack Lesher  (email : msher3@gmail.com)
 */


function my_special_function() {
    if (!current_user_can( 'administrator' ) ){

        wp_dequeue_script( 'rdhil_hover_text_js');
    }
}
add_action( 'wp_print_styles', 'my_special_function');

add_action( 'wp_ajax_nopriv_rdhil_get_translation_origin', 'rdhil_get_translation_origin' );
add_action( 'wp_ajax_rdhil_get_translation_origin', 'rdhil_get_translation_origin' );

function rdhil_get_translation_origin()
{

    // sanitize input text
    $website_text_sanitized = sanitize_text_field($_POST["website_text"]);

    // validate input text
    //validate at least one letter    
    if (strlen($website_text_sanitized) < 1) {
    echo "No translation";
    die();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'rdhil_localization_table';
    $org_english = $wpdb->get_var($wpdb->prepare("SELECT text_english FROM $table_name WHERE text_localized = %s", $website_text_sanitized  ));

    // escaping data
    $org_english = sanitize_text_field($org_english);
  
    //verify that ouput contains at least one English character 
    $pattern = '/[A-Za-z]/';
     preg_match($pattern, $org_english, $matches);
    if (!$matches)
    {
      echo "No translation";
      die();     
    }

    echo $org_english;
    die();
}


// Load all css and js files

if ( is_admin() ) 
{
 function rdhil_scripts_admin() {
   wp_enqueue_script('jquery');
   //! wp_enqueue_script( 'rdhil_ajax_calls_js',plugins_url( '/js/ajax-calls.js' , __FILE__ ));
   wp_enqueue_script( 'rdhil_hover_text_js',plugins_url( '/js/hover_text.js' , __FILE__ ));
   wp_enqueue_style( 'rdhil_tooltip',plugins_url( '/css/tooltip.css' , __FILE__ ));

   // passing varible from php to JS   
   wp_localize_script('rdhil_hover_text_js', 'objectFromPhp', array(
            'websiteUrl' =>  admin_url( 'admin-ajax.php' )));
}
add_action( 'admin_enqueue_scripts', 'rdhil_scripts_admin' );
}
else  // if ( is_admin() )
{
function rdhil_scripts_non_admin() {
   wp_enqueue_script('jquery');
   wp_enqueue_script( 'rdhil_hover_text_js',plugins_url( '/js/hover_text.js' , __FILE__ ));
   wp_enqueue_style( 'rdhil_tooltip',plugins_url( '/css/tooltip.css' , __FILE__ ));

  
    // passing varible from php to js
    wp_localize_script('rdhil_hover_text_js', 'objectFromPhp', array(
        'websiteUrl' =>  admin_url( 'admin-ajax.php' )));

}
add_action( 'wp_enqueue_scripts', 'rdhil_scripts_non_admin' );  
} // if ( is_admin() )


// add to Option table settings using arrays(like plugin version)

function rdhilpho_set_default_options_array() {
    if ( get_option( 'rdhilpho_options' ) === false ) {
        $new_options['version'] = "1.0.0";
        add_option( 'rdhilpho_options', $new_options );
    } else {
        $existing_options = get_option( 'rdhilpho_options' );
        if ( $existing_options['version'] < 1.8 ) {
            $existing_options['version'] = "1.8";
            update_option( 'rdhilpho_options', $existing_options );
        }
    }
}

// Create table rdhil_localization_table 

function rdhilbt_create_table( $prefix )
{
// Prepare SQL query to create database table

    $creation_query = 'CREATE TABLE ' . $prefix . 'rdhil_localization_table  (
`text_id` int(20) NOT NULL AUTO_INCREMENT,
`text_localized` varchar(255),
`text_english` text,
`style_saved_date` datetime DEFAULT NULL,
PRIMARY KEY (text_id),
INDEX (text_localized)
)CHARSET=utf8';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($creation_query);
}
// runs when activating this plugin
register_activation_hook( __FILE__, 'rdhilbt_activation' );

function rdhilbt_activation() {
 rdhilpho_set_default_options_array(); 
  
    // Get access to global database access class
    global $wpdb;
    // Actually create table on main blog 
    rdhilbt_create_table( $wpdb->get_blog_prefix() );

//-----------------------------------------------------
// get relevant plugins list
//----------------------------------------------------------
    $apl=get_option('active_plugins');
    // array holding plugin modified names(used to filter needed plugins from the whole .po files list)
    $activated_plugins_modified_names = array(); //e.g. wp-update-notifier
    foreach ($apl as $plugin) {
        $last_diagonal_position = strrpos($plugin, '/', -3);
        $plugin_modified_name = substr($plugin, $last_diagonal_position +1 ,strlen($plugin) - $last_diagonal_position -1 - 4);
        $plugin_modified_name = strtolower($plugin_modified_name);
        if(isset($plugin_modified_name)){
            array_push($activated_plugins_modified_names,$plugin_modified_name );
        }
    }

    // get active thmeme and add to the plugin list

    $theme_modified_name = get_template_directory();
    $last_diagonal_position = strrpos($theme_modified_name, '/');
    $theme_modified_name = substr($theme_modified_name , $last_diagonal_position + 1, strlen($theme_modified_name) -  $last_diagonal_position - 1);
    $theme_modified_name = strtolower($theme_modified_name);
    //add to the plugin relevant list
    if(isset($theme_modified_name)) {
        array_push($activated_plugins_modified_names, $theme_modified_name);
    }

    // Get loacl langaush e.g. "he_IL"

    $local_languash = get_locale();

    if ( !defined( 'WP_CONTENT_DIR' ) )
        define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

    // Get all the relevant "po" files from the Wordpress root folder

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(WP_CONTENT_DIR));

    $finals_po_files = array(); //including path

    foreach ($rii as $file) {
        if ($file->isDir()){// drop folders
            continue;
        }
        // take into account only ".po" files and only local languash files
        if ((strtolower(substr($file, -3)) == ".po") &&
            (strtolower(strpos($file , $local_languash) > 0)))
        {
            // check if this .po file is also active:
            // check if its included in the active plugin list

            if ($activated_plugins_modified_names > 0)
            {
                foreach($activated_plugins_modified_names as $plugin_string)
                {
                    //$pathname_current = $file->getPathname();
                    // if $plugin_string is contained in plugin pathname
                    $pathname_test = $file->getPathname();
                    if ( strpos ($file->getPathname(), $plugin_string ) > 0 )
                    {
                        $finals_po_files[] = $file->getPathname();
                        insert_po_file_into_table($file->getPathname());
                    }
                }
            }// if ($activated_plugins_modified_names > 0)

        }
    }//foreach ($rii as $file) {

//-----------------------------------------------------

}// function rdhilbt_activation() {

function insert_po_file_into_table($full_path_name_file){

    // open po file for read
    $po_file_handle = fopen($full_path_name_file, "r");
    if ( $po_file_handle <= 0 )
    {
        exit();
    }
     if ( $po_file_size = count( file($full_path_name_file)) <= 0){
         exit();
     }

    global $wpdb;
  
  

    $table_name = $wpdb->prefix . 'rdhil_localization_table';
  
    while(!feof($po_file_handle))
    {
        $line = fgets($po_file_handle);
        if (strpos($line,'msgid') !== false)
        {
            $first_postroph_position = strpos($line, '"');
            $second_postroph_position = strpos($line, '"', $first_postroph_position +1 );
            $english_text = substr($line, $first_postroph_position + 1,$second_postroph_position - $first_postroph_position -1 );
            // if line is empty - skip it
            if ($english_text == "")
              continue;
            //fetch the localize value
            $line = fgets($po_file_handle);
            if (strpos($line,'msgstr') !== false)
            {
                $first_postroph_position = strpos($line, '"');
                $second_postroph_position = strpos($line, '"', $first_postroph_position +1 );
                $localize_text = substr($line, $first_postroph_position + 1,$second_postroph_position - $first_postroph_position -1 );
                // if line is empty - skip it
                if ($localize_text == "")
                    continue;
                // insert a row into DB
                $wpdb->query($wpdb->prepare("INSERT INTO $table_name (text_id, text_localized, text_english, style_saved_date)
                VALUES (%d, %s, %s, %s)", null , $localize_text, $english_text ,current_time( 'mysql' )));
            }
            else
                continue;
         ///   $test = $english_text;
        }
    } //  while(!feof($po_file_handle))

} // function insert_po_file_into_table($full_path_name_file){

  // drop the table in unregister the plugin

   register_deactivation_hook( __FILE__, 'rdhil_deactivate' );
   function rdhil_deactivate () {
     global $wpdb;
     rdhilbt_drop_table( $wpdb->prefix );
   }

   function rdhilbt_drop_table( $prefix ) {
    global $wpdb;
       $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . $prefix .
           'rdhil_localization_table', null ) );

  }



