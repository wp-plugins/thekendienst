<?php
/*
Plugin Name: Thekendienst
Plugin URI: http://thekendienstplugin.derdateienhafen.de/
Description: Plugin zum Verwalten von Diensten
Author: Janne Jakob Fleischer
Version: 0.1alpha
License: GPL
Author URI: none
Update Server: none
Min WP version: 3.0.5
Max WP Version: 3.0.5
*/


//globale Variablen
global $thekendienst_db_version;
$thekendienst_db_version = "0.2";

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
	//$rueckgabe=$wpdb->get_var("SHOW TABLES LIKE '".$table_name."'");
   if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) { //überprüft ob die Tabelle noch nicht existiert
   		//$rueckgabe.=get_option('thekendienst_db_version', null);
		$sql = 'CREATE TABLE '.$table_name.' (
		ID mediumint(9) NOT NULL AUTO_INCREMENT KEY,
		AufstellungsID mediumint(9) NOT NULL,
		AufstellungsName varchar(45) DEFAULT "notset",
		IDZeitfenster smallint(9),
		KommentarZeitfenster varchar(45) DEFAULT "",
		Tag date,
		Startzeit time,
		Endzeit time,
		AnzahlMitarbeiter tinyint(9) DEFAULT "1",
		IDMitarbeiter smallint(9) DEFAULT NULL,
		NameMitarbeiter varchar(40),
		Ausgeblendet boolean DEFAULT "0");';//Tabellenstruktur wird angelegt.

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //emröglicht Zugriff auf dbDelta-Funktion
		dbDelta($sql);//erzeugt neue Tabelle in Datenbank
		add_option("thekendienst_db_version", $thekendienst_db_version);
		return $rueckgabe.mysql_error().'<br><strong>Die Datenbank wurde erfolgreich angelegt</strong><br>';
   }
   elseif(get_option('thekendienst_db_version', null)=='0.1') {
   		//$rueckgabe.= get_option('thekendienst_db_version', null);
   		$sql = 'ALTER TABLE '.$table_name.' ADD COLUMN KommentarZeitfenster varchar(45) ';
   		dbDelta($sql);
   		update_option("thekendienst_db_version", $thekendienst_db_version);
   		return $rueckgabe.mysql_error().'<br><strong>Die Datenbank wurde erfolgreich verändert</strong><br>';
   }
   else {
   		//$rueckgabe.= get_option('thekendienst_db_version', null);
   		return $rueckgabe;
   	}
}

?>