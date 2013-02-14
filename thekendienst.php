<?php
/*
Plugin Name: Thekendienst
Plugin URI: http://wordpress.org/extend/plugins/thekendienst/
Description: Plugin zum Verwalten von Diensten
Author: Janne Jakob Fleischer
Version: 0.3.1
License: GPL
Author URI: none
Update Server: none
Min WP version: 2.8
Max WP Version: 3.4.1
*/


//globale Variablen
global $thekendienst_db_version;
$thekendienst_db_version = "0.4";

//hook der sich auf die Datei bezieht (ABER NICHT FUNKTIONIERT!!! )
register_activation_hook(__FILE__,'Datenbankanlegen');
//workaround für not-working-hook
echo Datenbankanlegen();

require_once("thekendienstFunktionen.php");


/* *************************************************** 
Initiierung der Hooks
*************************************************** */

add_action('wp_head', 'stylesheetsnachladen');
add_action('admin_head', 'stylesheetsnachladen');
add_action('init','Javascriptladen');
add_action('admin_init', 'Javascriptladen');
add_action('admin_menu', 'ThekendienstAdminPanel');




/* *************************************************** 
Tabelle anlegen sofern noch nicht vorhanden.
*************************************************** */

function Datenbankanlegen() {//Sollte keine Tabelle vorliegen, wird eine erzeugt
	//die('so_on');
   global $wpdb, $table_prefix;
   global $thekendienst_db_version;
   $rueckgabe=null;

   $table_name = $table_prefix."thekendienst";
   $sql = 'CREATE TABLE '.$table_name.' (
   ID mediumint(9) NOT NULL AUTO_INCREMENT KEY,
   AufstellungsID mediumint(9) NOT NULL,
   AufstellungsName varchar(100) DEFAULT "notset",
   IDZeitfenster smallint(9),
   KommentarZeitfenster varchar(45) DEFAULT "",
   Tag date,
   Startzeit time,
   Endzeit time,
   AnzahlMitarbeiter tinyint(9) DEFAULT "1",
   IDMitarbeiter smallint(9) DEFAULT NULL,
   NameMitarbeiter varchar(40),
   Ausgeblendet varchar(45) DEFAULT NULL,
   Archiv boolean DEFAULT "0");';//Tabellenstruktur wird angelegt.
   $current_db_version=get_option('thekendienst_db_version', null);
	//$rueckgabe=$wpdb->get_var("SHOW TABLES LIKE '".$table_name."'");
   if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) { //überprüft ob die Tabelle noch nicht existiert
   		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //emröglicht Zugriff auf dbDelta-Funktion
		dbDelta($sql);//erzeugt neue Tabelle in Datenbank
		add_option("thekendienst_db_version", $thekendienst_db_version);
		return $rueckgabe.mysql_error().'<br><strong>'.__('Die Datenbank wurde erfolgreich NEU angelegt', 'thekendienst_textdomain').'</strong><br>';
   }
   elseif($current_db_version=='0.1' OR $current_db_version=='0.2' OR $current_db_version=='0.3') {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //emröglicht Zugriff auf dbDelta-Funktion  	
   		dbDelta($sql);
   		update_option("thekendienst_db_version", $thekendienst_db_version);
   		return $rueckgabe.mysql_error().'<br><strong>'.__('Die Datenbank wurde erfolgreich verändert', 'thekendienst_textdomain').'</strong><br>';
   	}
   else {
   		return $rueckgabe;
   	}
}

?>