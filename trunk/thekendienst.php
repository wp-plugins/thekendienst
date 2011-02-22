<?php
/*
Plugin Name: Thekendienst
Plugin URI: none
Description: Plugin zum Verwalten von Diensten
Author: Janne Jakob Fleischer
Version: 0.0.2
License: GPL
Author URI: none
Update Server: none
Min WP version: 2.9.1
Max WP Version: 2.9.2
*/
require_once("thekendienstFunktionen.php");


/* *************************************************** 
Initiierung der Hooks
*************************************************** */

add_action('wp_head', 'stylesheetsnachladen');
add_action('admin_head', 'stylesheetsnachladen');
add_action('init','Javascriptladen');
add_action('admin_init', 'Javascriptladen');
add_action('admin_menu', 'ThekendienstAdminPanel');
add_action( 'activate_'.plugin_basename(__FILE__),   'Datenbankanlegen' );
//add_action( 'deactivate_'.plugin_basename(__FILE__), 'mb_plugin_wird_deaktiviert' );

?>