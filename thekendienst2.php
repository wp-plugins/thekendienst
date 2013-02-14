<?php
/*
Plugin Name: Thekendienst2
Plugin URI: http://wordpress.org/extend/plugins/thekendienst/
Description: Plugin zum Verwalten von Diensten
Author: Janne Jakob Fleischer
Version: 2.x.try
License: GPL
Author URI: none
Update Server: none
Min WP version: 3.0
Max WP Version: 3.5.1
*/

//globals
global $thekendienst2_db_version;
$thekendienst2_db_version = "0.4";

//hook at activation
register_activation_hook(__FILE__, 'td_createdatabase');
//echo td_createdatabase(); //workaround for not-working-hook (uncomment, if needed)

/* *************************************************** 
hooks
*************************************************** */
add_action('admin_notices', 'td2_admin_notice');
add_action('wp_head', 'td2_loadstylesheets');
add_action('admin_head', 'td2_loadstylesheets_admin');
add_action('init','td2_loadjavascripts');
add_action('admin_init','td2_loadjavascripts');
add_action('admin_menu','td2_admin_panel');


/* *************************************************** 
initiation functions
*************************************************** */
function td2_admin_panel() {
	add_options_page('Thekendienst', 'Thekendienst', 'edit_posts', 'thekendienst-optionen', 'td2_admin_panel_decider');
	}


/* *************************************************** 
switch and decide functions
*************************************************** */

function td2_admin_panel_decider() {
	if(isset($_POST['zeitfenstereintragen'])) zeitfenstereintragen("");
	elseif(isset($_POST['zeitfensteraendern'])) zeitfensteraendern("");
	elseif(isset($_POST['zeitfenster_loeschen'])) zeitfensterloeschen("");
	elseif(isset($_POST['neueveranstaltunggesetzt'])) neueveranstaltungeintragen("");
	elseif(isset($_POST['eintragen'])) eintragenName("");
	elseif(isset($_POST['austragen'])) austragenName("");
	elseif(isset($_POST['veranstaltungloeschen'])) veranstaltungloeschen("");
	elseif(isset($_POST['dauerhaft_ein_ausblenden'])) dauerhaft_ein_ausblenden("");
	echo Tabellenanfang().AufstellungermittelnAdmin().Tabellenende();
}


/* *************************************************** 
classes
*************************************************** */
/* Erstellung einer Veranstaltung in reiner Methodenlogik:
$event = new td2_event;
$event->add_basics(array(2, 'doof'));
$event->add_timeframe(array( 'date'=>'2013.02.02', 'start'=>'17:00:00', 'end'=>'18:00:00', 'count'=>3, 'comment'=>'')); */


class td2_event 
{
	public $ID;
	public $Name;
	public $timeframes;
	public function add_basics($content) {
		$this->ID=$content['event_ID'];
		$this->Name=$content['event_name'];
		return $this;
	}
	
	public function add_timeframe($content = array('id'=>-1)) {
	// if there is no id on runtime specified, the default value of -1 will cause this function to kill itself.
	
		if (!isset($this->timeframes[$content['id']])) 
		{
			$this->timeframes[$content['id']][] = new td2_timeframe;
			end($this->timeframes)->add_data($content);
		}
		else td_admin_notice(__('An error occured: An id for this timeframe hadn\'t been specified'));
		return ;
	}
	
	private function sort_timeframes() {
	//sorts out, that the timeframes appear in a timely-fashion order. first apperas first. The data (in ram) gets to get ordered and afterwards can be added to the database.
		usort($this->timeframes, 'cmp_obj');
	
	
		/* (array) $rv= $this->timeframes[0];
		foreach(array_slice($this->timeframes, 1) as $key => $timeframe) {
			(array) $comparison=current($rv);
			if(count(array_diff($timeframe, $rv))==0) continue;
			if($timeframe->get_data[1] >= $comparison[1] && $timeframe->get_data[2] >= $comparison[2] && $timeframe->get_data[3] >= $comparison[3]) 
			{
				$rv=array_merge($rv, $timeframe)
			} 
			elseif($timeframe->get_data[1] < $comparison[1] && $timeframe->get_data[2] >= $comparison[2] && $timeframe->get_data[3] >= $comparison[3]
		} */
	}
		
	public function participate($content) { //attention: $content need to include an information in which timeframe you like to dive in. Guessing: not trivial and maybe only works with a search through all the timeframes for a criteria. Or put a unique id into the html-form (quessing: Problems with more than one user at a time).
		return $this->timeframes[$content['id']]->participate($content);
	}
	
	public function get_data() {
		$rw=array($this->ID, $this->Name, $this->timeframes);
		return $rw;
	}
	
	public function get_data_flat() {
	//created to put the output into td2_put_objects_in_a_table
		$output_temp=$this->get_data;		
		$output=help_with_object($object_temp);
		return $output;
	}
		
	private function help_with_object($object) {
	//recursive flattening of objects in arrays: putting all the data in an array of arrays. Each of this arrays contains only one type of data so td2_put_objects_in_a_table() can work with the data.
		(array) $z;
		(array) $rv;
		foreach ($object as $x) 
		{
			$set=0;
			if (!is_object($x))
			{
					$z[]=$x;
			}
			else 
			{
				$rv[]=$z;
				unset($z);
				$set=1;
				$rv= array_merge($output, help_with_objects($x));
			}
			if(end($object)==$x && $set==0) 
			{
				$rv[]=$z;
				unset($z);
				$set=1;
			}
		}
		return $rv;
	}
	
}

class td2_timeframe 
{
	public $date="2013.01.01";
	public $starting_time="08:00:00";
	public $ending_time="10:00:00";
	public $count=3;
	public $comment="";
	public $participants;
	public function add_data($content) {
		$this->id=$content['id'];
		$this->date=$content['date'];
		$this->starting_time=$content['start'];
		$this->ending_time=$content['end'];
		$this->count=$content['count'];
		$this->comment=$content['comment'];
		return $this;
	}
	public function participate($content) {
		$this->participants[$content['id']][] = new td2_participant;
		end($this->participants[$content['id']])->add_data($content);
		return true;
	}
	public static function cmp_obj($a, $b)
	{
		$a1=$a->timeframes->get_data[1];
		$a2=$a->timeframes->get_data[2];
		$b1=$b->timeframes->get_data[1];
		$b2=$b->timeframes->get_data[2];
		if($a1==$b1) {
			if($a2==$b2) return 0;
			elseif($a2>$b2) return +1;
			else return -1;
		}
		elseif($a1>$b1) return +1;
		else return -1;
	}
	public function get_data() {
		$rv=array($this->id, $this->date, $this->starting_time, $this->ending_time, $this->count, $this->comment, $this->participants);
		return $rv;
	}
}

class td2_participant
{
	public $user_id="";
	public $name="";
	public function add_data($content) {
		$this->user_id=$content['user_ID'];
		$this->name=$content['user_name'];
		return $this;
	}
	public function print_out() {
		$rv=array($this->user_id, $this->user_name);
		return $rv;
	}
}

class td2_create_new_event_form {
	public $session_id;
	public $id;
	public function td2_create_new_event_form() {
		//get an ID from Database and add a session_id.
		$this->session_id = session_id();
	}
	public function form() {
		$rv="";
		$rv.='
			<form>
			<table border="1">
			<tr>
				<td colspan="7">'.__("Add a new Event by putting your wishes into this form:").'</td>
			</tr>
			<tr>
				<td>'.$this->id.'</td>
				<td colspan="6">
					<input name="td2_title_of_event" type="text" size="100%" maxlength="255">
					<input type="hidden" name="id" value='.$this->id.'>
					<input type="hidden" name="session_id" value='.$this->session_id.'>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Datum</td>
				<td>Anfangszeit</td>
				<td>Endzeit</td>
				<td>Anzahl der Mitarbeiter</td>
				<td colspan="2">Besonderheiten/Kommentare</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input name="date_day" type="text" size="2" maxlength="2">
					<input name="date_month" type="text" size="2" maxlength="2">
					<input name="date_year" type="text" size="2" maxlength="2"><br/>
					<select name="date_day" size="1" maxlength="2">'.$this->option_to_x(31).'</select>
					<select name="date_month" size="1" maxlength="2">'.$this->option_to_x(12).'</select>
					<select name="date_year" size="1" maxlength="4">'.$this->option_to_x(2025, 2012).'</select>
				</td>
				<td>
					<input name="start_hour" type="text" size="2" maxlength="2">
					<input name="start_minute" type="text" size="2" maxlength="2"><br/>
					<select name="start_hour" size="1" maxlength="2">'.$this->option_to_x(24).'</select>
					<select name="start_minute" size="1" maxlength="2">'.$this->option_to_x(60).'</select>
				</td>
				<td>
					<input name="end_hour" type="text" size="2" maxlength="2">
					<input name="end_minute" type="text" size="2" maxlength="2"><br/>
					<select name="end_hour" size="1" maxlength="2">'.$this->option_to_x(24).'</select>
					<select name="end_minute" size="1" maxlength="2">'.$this->option_to_x(60).'</select>
				</td>
				<td>
					<input name="count" type="text" size="1" maxlength="1"><br/>
					<select name="count" type="text" size="1" maxlength="2">'.$this->option_to_x(8).'</select>
				</td>
				<td colspan="2">
					<input name="comment" type="text" size="60" maxlength="255">
				</td>
			</tr>
			<th colspan="7"/>
			<tr>
				<td>&nbsp;</td>
				<td colspan="4">
					<input type="radio" name="td2_options_on_creation" value="once" checked>
					Einmaliges Ereignis
				</td>
				<td rowspan="3" colspan="2">
					Wieviele Zeitfenster?<br/>
					<input type="text" value="1" size="3" maxlength="2" readonly>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="4"><input type="radio" name="td2_options_on_creation" value="multiply" disabled="disabled">Wiederholen (obere Angaben um ein vielfaches dessen verlängern)</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="4"><input type="radio" name="td2_options_on_creation" value="split" disabled="disabled"> Gleichmäßig verteilen (obere Angaben ist Gesamtzeitraum und wird gleichmäßig unterteilt</td>
			</tr>
			<tr>
				<td colspan="7">
					 
				</td>
			</tr>
			<tr>
				<td colspan="5">Nachträgliche Anpassungen können im nächsten Schritt erfolgen</td>
				<td colspan="2">
					<input type="submit" value="'.__("Create event").'/><br/>
					<input type="reset" value="'.__("Abbrechen/Formular leeren").'/>
				</td>
			</tr>
			</table>
			</form>';
		return $rv;
	}
	private function option_to_x($x, $a=1) {
		$rv = "";
		for($a; $a <=x;$a++) {
			$rv.='<option>'.$a.'</option>
			';
		}
		return $rv;
	}

}


/* *************************************************** 
basic functions
*************************************************** */

function td2_put_objects_in_a_table($input) {
	$rv="";
	$rv.="
	<table>
		<tr>";
	if(is_object($input)) {
		foreach ($input as $x) {
			switch ($x) {
				case 'td2_event':
					$rv.='
						<td class="td2_firstrow">'.$x[0].'</td>
						<td class="td2_eventline" colspan="6">'.$x[1].'</td>
						';
					break;
				case 'td2_timeframe':
					$rv.='
						<td class="td2_firstrow">&nbsp;<td>
						<td>&nbsp;<td>
						<td class="td2_date">'.$x[0].'</td>
						<td class="td2_start_time">'.$x[1].'</td>
						<td class="td2_end_time">'.$x[2].'</td>
						<td class="td2_count">'.$x[3].'</td>
						<td class="td2_comment">'.$x[4].'</td>
						';
					break;
				case 'td2_participant':
					$rv.='
						<td class="td2_firstrow">&nbsp;</td>
						<td colspan="2">&nbsp;<td>
						<td>'.$x[0].'</td>
						<td colspan="2">'.$x[1].'</td>
						<td>$nbsp;</td>
						';
					break;
			}
			
		}
	}
	else '<td>__("An error occured with the interpretation of the data. Are you sure you added an event with this ID?")</td>';
	$rv.="
		</tr>
	</table>";
}

function td_createdatabase() { 
//creating and changing database, if needed

	global $wpdb, $table_prefix;
	global $thekendienst2_db_version;
	$rueckgabe=null;
	$table_name = $table_prefix."thekendienst2";
	$sql = '
		CREATE TABLE '.$table_name.' (
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
	$current_db_version=get_option('thekendienst2_db_version', null);
	if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) { //does the table exist?
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //grants access to dbDelta
		dbDelta($sql); //creates new tables in database
		add_option("thekendienst2_db_version", $thekendienst2_db_version);
		td_admin_notice('<div class="updated">'.__("The additional table for thekendienst2 has been created: The suffix of the name is *thekendienst2", "thekendienst2_textdomain"). '</div>');
		return $rueckgabe.mysql_error();
	}
	elseif($current_db_version=='0.1' OR $current_db_version=='0.2' OR $current_db_version=='0.3') {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //grants access to dbDelta
		dbDelta($sql);
		update_option("thekendienst2_db_version", $thekendienst2_db_version);
		td_admin_notice('<div class="updated">'.__("The table for thekendienst2 has been changed: The suffix of the table is *thekendienst2", "thekendienst2_textdomain"). '</div>');
		return $rueckgabe.mysql_error();
		}
	else {
			return $rueckgabe;
		}
}

function td_admin_notice($string) { 
//message to admin in backend

	echo $string;
}

function td2_loadstylesheets(){ 
//loads the stylesheets

	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL."/thekendienst/thekendienststyles.css' type='text/css' media='all' />";
}

function td2_loadstylesheets_admin(){ 
//loads the stylesheets for the backend

	td2_loadstylesheets;
}

function td2_loadjavascripts() {
//loads all the javascripts

	wp_enqueue_script('thekendienstscript', WP_PLUGIN_URL.'/thekendienst/thekendienstscript.js');
}

?>