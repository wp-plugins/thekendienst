<?php
/*
Plugin Name: Thekendienst
Plugin URI: none
Description: Plugin zum Verwalten von Diensten
Author: Janne Jakob Fleischer
Version: 0.0.1
License: GPL
Author URI: none
Update Server: none
Min WP version: 2.9.1
Max WP Version: 2.9.1
*/
require_once("thekendienstFunktionen.php");

add_action( 'activate_'.plugin_basename(__FILE__),   'Datenbankanlegen' );
//add_action( 'deactivate_'.plugin_basename(__FILE__), 'mb_plugin_wird_deaktiviert' );

?>