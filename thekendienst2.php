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
$thekendienst2_db_version='2.0';

//hook at activation
//register_activation_hook(__FILE__, 'td_createdatabase');
echo td_createdatabase(); //workaround for not-working-hook. Uncomment, if needed (necessary in development-systems with hard or symbolic links)

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
	add_options_page('Thekendienst', 'Thekendienst', 'edit_posts', 'thekendienst2-options', 'td2_admin_panel_decider');
	}


/* *************************************************** 
switch and decide functions
*************************************************** */
if(isset($_POST['td2_participate_submit'])) add_filter('the_content','td2_participator');
//elseif(isset($_POST['td2_participate_submit'])) add_filter('the_content','');
add_filter('the_content', 'td2_get_events_printed');


function td2_admin_panel_decider() {
	if(isset($_POST['td2_eventcreation'])) add_filter('the_content','td2_event_creator');
	$new_event= new td2_create_new_event_form();
	echo $new_event->print_data();
	print_r($_POST);
	echo '<div class="thekendienst2">'.td2_get_events_printed(null).'</div>';
	
	
	/*
	if(isset($_POST['zeitfenstereintragen'])) zeitfenstereintragen("");
	elseif(isset($_POST['zeitfensteraendern'])) zeitfensteraendern("");
	elseif(isset($_POST['zeitfenster_loeschen'])) zeitfensterloeschen("");
	elseif(isset($_POST['neueveranstaltunggesetzt'])) neueveranstaltungeintragen("");
	elseif(isset($_POST['eintragen'])) eintragenName("");
	elseif(isset($_POST['austragen'])) austragenName("");
	elseif(isset($_POST['veranstaltungloeschen'])) veranstaltungloeschen("");
	elseif(isset($_POST['dauerhaft_ein_ausblenden'])) dauerhaft_ein_ausblenden("");
	echo Tabellenanfang().AufstellungermittelnAdmin().Tabellenende();*/
}
/* *************************************************** 
help functions for deciders
*************************************************** */
function td2_event_creator($content) {
	$event = new td2_event;
	extract($_POST);
	$event->add_basics(array('ID'=>$_POST('id'),'Name'=>$_POST('Name')));
	//$event->calculate_timeframes($_POST);
	//$event->save_to_db();
	//print_r($event);
	$content=td2_get_events_printed($content, $event, true);
	return $content;
}

function td2_participator($content) {
	$events=td2_get_events($_POST['ID']);
	print_r($events);
	foreach ($events as $event) {
		if($event->id_timeframe==$_POST['ID-timeframe']) $content=$event->participate($content);
	}
	return $content;
}

function td2_findobject ($ID, $ID_timeframe, $user_id) {
	
	return $ro;
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
	public $mark;
	public function add_basics($content) {
		$this->ID=$content['ID'];
		$this->Name=$content['Name'];
		//$this->mark=$content['mark'];
		return $this;
	}
	
	public function __construct($ID=null) {
		$this->ID=$ID;
	}
	
	public function add_data($content) {
		return $this->add_basics($content);
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
	
	public function add_data_db($content) {
		//put an array from db in, get an object (with data in it) out.
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
		return $this->timeframes[$_POST['id_timeframe']]->participate($content);
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
	
	public function print_data() {
		$rv='
			<tr class="event, mark_'.var_export($this->mark).'">
				<td class="td2_firstrow, mark_'.var_export($this->mark).'">'.$this->ID.'</td>
				<td class="td2_eventline" colspan="6">'.$this->Name.'</td>
			</tr>
			';
		return $rv;
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
					//problem: If there is no timeframe (as an object) in $object all the data goes into one enormous (and wrong!) array.
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
	public $id_timeframe;
	public $date="2013.01.01";
	public $starting_time="08:00:00";
	public $ending_time="10:00:00";
	public $count=3;
	public $comment="";
	public $participants;
	public $mark;
	public $participation_form;
	
	public function __construct($id_t=null) {
		$this->id_timeframe=$id_t;
		$form=new td2_create_new_participation_form($this->id_timeframe);
		$this->participation_form=$form->form();
		return ;
	}
	
	public function add_data($content) {
		$this->id_timeframe=$content['ID_timeframe'];
		$this->date=$content['date'];
		$this->starting_time=$content['starting_time'];
		$this->ending_time=$content['ending_time'];
		$this->count=$content['count'];
		$this->comment=$content['comment'];
		if ($content['participants'] != '') {
			foreach (unserialize($content['participants']) as $participant_data) {
				$participant= new td2_participant($this->id_timeframe);
				$participant->add_data($participant_data);
				$this->participants[]=$participant;
			}
		}
		return $this;
	}
	
	public function show_participation_form() {
		return $this->participation_form;
	}
	
	public function participate($content) {
		global $wpdb;
		$sql_user='SELECT ID, user_login, display_name FROM '.$wpdb->users;
		$users=$wpdb->get_results($sql_user, ARRAY_A);
		foreach ($users as $user) { 
			if($user->login_name == $_POST['td2_participant_opts']) 
				$user_data=array('user_id'=>$user->ID , 'user_name'=>$user->login_name);
		}
		$participant = new td2_participant($_POST['id_timeframe']);
		$participant->add_data($user_data);
		$participant->save_data();
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
	
	public function print_data() {
		$rv='
			<tr class="timeframe, mark_'.$this->mark.'">
				<td class="td2_firstrow"></td>
				<td class="td2_date">'.$this->date.'</td>
				<td class="td2_start_time">'.$this->starting_time.'</td>
				<td class="td2_end_time">'.$this->ending_time.'</td>
				<td class="td2_count">'.$this->count.'</td>
				<td colspan="2" class="td2_comment">'.$this->comment.'</td>
			</tr>';
		return $rv;
	}
}

class td2_participant
{
	public $user_id="";
	public $name="";
	public $mother_timeframe;
	
	public function __construct($id_timeframe=null) {
		$this->mother_timeframe =$id_timeframe;
	}
	
	public function add_data($content) {
		$this->user_id=$content['user_ID'];
		$this->name=$content['user_name'];
		return $this;
	}
	public function get_data() {
		$rv=array($this->user_id, $this->user_name);
		return $rv;
	}
	public function print_data() {
		$rv='';
		$rv.='
			<tr class="participant, mark_'.var_export($x->mark).'">
				<td class="td2_firstrow">&nbsp;</td>
				<td colspan="2">&nbsp;<td>
				<td>'.$this->user_id.'</td>
				<td colspan="2">'.$this->user_name.'</td>
				<td>$nbsp;</td>
			</tr>';
		return $rv;
	}
	public function save_data() {
		global $wpdb, $table_prefix;
		$table = $table_prefix.'thekendienst2_timeframe';
		$sql_get='SELECT id_timeframe, participants from '.$table.' WHERE id_timeframe='.$this->mother_timeframe.' ORDER BY id_timeframe';
		$timeframe_data=$wpdb->get_results($sql_get, ARRAY_A);
		if(mysql_affected_rows()>1) return 'something went wrong: there are doublettes in the database';
		$timeframe_data[0];
		$participants=unserialize($timeframe_data[0]['participants']);
		$breakout==false;
		foreach($participants as $p) {
			if($breakout==true || $p->user_id==$this->user_id) {
				return 'allready subscribed';
			}
		}
		$participants[]=$this;
		$sql_put='UPDATE '.$table1.' SET participants='.serialize($participants).'WHERE id_timeframe='.$this->mother_timeframe.'';
		$wpdb->query($sql_put);
		return 'you‘ve been subscribed';
	}
}

class td2_create_new_participation_form {
	
	public $id;
	public $id_timeframe;
	public $session_id;
	public $users_in_option;
	
	public function list_users_option() {
		global $wpdb;
		$current_user=wp_get_current_user();
		$sql_user='SELECT ID, user_login, display_name FROM '.$wpdb->users;
		$users=$wpdb->get_results($sql_user, ARRAY_A);
		$rv1='';
		$rv2[]='';
		foreach ($users as $user) {
			if($user["user_login"]==$current_user->user_login) {
				$rv1.="<option selected>".$user['user_login']."</option>";
			}
			else $rv1.='<option>'.$user["user_login"].'</option>
			';
			//print_r($user);
			$rv2[]=array($user['ID']=>$user['user_login']);
		}
		$this->users_in_option[1]=$rv1;
		$this->users_in_option[2]=$rv2;
	}
	
	public function __construct($id_timeframe=null,$id=null) {
		$this->session_id = session_id();
		$this->id=$id;
		$this->id_timeframe=$id_timeframe;
	}
	
	public function form() {
		$current_user=wp_get_current_user();
		if(!isset($this->users_in_option[1])) $this->list_users_option();
		$rv='';
		$rv.='
			<tr>
				<form action="" method="post" name="td2_participationform">
					<td>&nbsp</td>
					<td>&nbsp</td>
					<td colspan="3">
						<select name="td2_participant_opts">
						'.$this->users_in_option[1].'
						</select>
					</td>
					<td colspan="2">
						<input type="submit" name="td2_participate_submit"  value="submit">
						<input type="hidden" value="'.$this->id_timeframe.'" name="id_timeframe"/>
						<input type="hidden" value="'.$this->id.'" name="ID"/>
					</td>
				</form>
			</tr>			
			<br/> ';
		return $rv;
	}
	
	public function print_data() {
		return $this->form();
	}
}

class td2_create_new_event_form {
	public $session_id;
	public $id;
	
	public function __construct() {
		$objects=td2_get_events();
		$last=end($objects);
		//print_r($last);
		$this->id=$last['ID']+1;
		$this->session_id=session_id();
	}
	
	public function form() {
		/* $objects=td2_get_events();
		$last=end($objects);
		$last_id=$last['ID']; */
		$rv="";
		$rv.='
			<form action="" method="post" name="td2_eventcreation">
			<table border="1">
			<tr>
				<td colspan="7">'.__("Add a new Event by putting your wishes into this form:").'</td>
			</tr>
			<tr>
				<td>'.$this->id.'</td>
				<td colspan="6">
					<input name="td2_title_of_event" type="text" size="120" maxlength="255">
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
					<input type="submit" name="td2_eventcreation" value="'.__("Create event").'"/><br/>
					<input type="reset" value="'.__("Cancel/Clear form").'"/>
				</td>
			</tr>
			</table>
			</form>
			<br/>';
		return $rv;
	}
	
	public function print_data() {
		return $this->form();
	}
	
	private function option_to_x($x, $a=1) {
		$rv = "";
		$mid=floor(($x-$a+1)/2);
		for($a; $a<=$x;$a++) {
			if($a==$mid) {
				$rv.='<option selected>'.$a.'</option>';
				continue;	
			}
			$rv.='<option>'.$a.'</option>
			';
		}
		return $rv;
	}

}


/* *************************************************** 
basic functions
*************************************************** */
function td2_get_events($event=null, $mark = false, $timeframe=null) {
	//if (!isset($event)) $event = new td2_event;
	global $wpdb, $table_prefix;
	$table1 = $table_prefix.'thekendienst2_event';
	$table2 = $table_prefix.'thekendienst2_timeframe';
	if (is_object($event) && !is_null($event)) $event_data=$event->get_data();
	elseif (is_numeric($event) && !is_null($event)) {
		$event_data['ID']=$event;
	}
	if (isset($event_data['ID']) && $mark=false) {
		$sql1='SELECT * FROM '.$table1.' LEFT JOIN '.$table2.' ON '.$table1.'.ID = '.$table2.'.ID WHERE '.$table2.'.ID='.$event_data['ID'].' GROUP BY '.$table2.'.ID ORDER BY '.$table2.'.ID, date, starting_time';
		$one=true;
	}
	elseif (isset($event_data['ID']) && $mark=true) {
		$sql1='SELECT * FROM '.$table1.' LEFT JOIN '.$table2.' ON '.$table1.'.ID = '.$table2.'.ID GROUP BY '.$table2.'.ID ORDER BY '.$table2.'.ID, date, starting_time';
		$one=false;
	}
	else {
		$sql1='SELECT * FROM '.$table1.' LEFT JOIN '.$table2.' ON '.$table1.'.ID = '.$table2.'.ID GROUP BY '.$table2.'.ID ORDER BY '.$table2.'.ID, date, starting_time';
		$one=false;
	}
	$events_data=$wpdb->get_results($sql1, ARRAY_A);
	if(isset($events_data[0])) {
		foreach($events_data as $event_data) { //db to array of objects
			//events
			$current_event = new td2_event;
			if($mark && $event_data['ID']==$event_data['ID']) 
			{
				$current_event->add_data(array_merge($event_data, array('mark'=>true)));
			}
			else 
			{
				$current_event->add_data($event_data);
			}
			$objects_in_order[]=$current_event;
			//list all timeframes of current event
			$sql2='SELECT * FROM '.$table1.' LEFT JOIN '.$table2.' ON '.$table1.'.ID = '.$table2.'.ID   WHERE '.$table2.'.ID="'.$event_data['ID'].'" ORDER BY '.$table2.'.ID, '.$table2.'.date, '.$table2.'.starting_time, '.$table2.'.comment';
			$timeframeS_data=$wpdb->get_results($sql2, ARRAY_A);
			foreach($timeframeS_data as $timeframe_data) 
			{
				$timeframe=new td2_timeframe;
				$objects_in_order[]=$timeframe->add_data($timeframe_data);
				if (isset($timeframe->participants)) {
					foreach($timeframe->participants as $participant) $objects_in_order[]=$participant;
				}
				$objects_in_order[]=new td2_create_new_participation_form($timeframe->id_timeframe, $current_event->ID);
			}
		}
		return array($objects_in_order, $one);
	}
	else {
		return __('There is no data in the database');
	}
	
}

function td2_get_events_printed($content, $event=null, $mark = false) {
	$objects_in_order_pre=td2_get_events($event=null, $mark = false);
	$objects_in_order=$objects_in_order_pre[0];
	$one=$objects_in_order_pre[1];
	$html_table = td2_put_objects_in_a_table($objects_in_order);
	if($content==null) {
		return $html_table;
	}
	elseif($one) {
		$replacestring1='[Thekendienst='.$event_data['ID'].']';
		$replacestring2='[Thekendienst='.$event_data['Name'].']';
		//Hier fehlt eine Funktion zum überprüfen ob eine Zeichenfolge in $content enthalten ist
		if(strpos($content, $replacestring1)) {
			$content=str_replace($replacestring1, $htmltable, $content);
		}
		elseif(strpos($content, $replacestring2)) {
			$content=str_replace($replacestring2, $htmltable, $content);
		}
		return $content;
	}
}

function td2_put_objects_in_a_table($input) {
	$rv="";
	$rv.="
	<table>";
	foreach ($input as $x) {
		$rv.=$x->print_data();
		/*switch (get_class($x)) {
			case 'td2_event':
				$rv.=$x->print_data();
				break;
			case 'td2_timeframe':
				$rv.=$x->print_data();
				break;
			case 'td2_participant' :
				$rv.=$x->print_data();
			break;
			default : ;
		}
		*/
	}
	/*else '
		<tr>
			<td>'.__("An error occured with the interpretation of the data. Are you sure you have any data in the database? -> call support!").'</td>
		</tr>'; */
	$rv.="
	</table>";
	return $rv;
}

function td_createdatabase() { 
//creating and changing database, if needed

	global $wpdb, $table_prefix;
	global $thekendienst2_db_version;
	$rueckgabe=null;
	$table_name1 = $table_prefix."thekendienst2_event";
	$table_name2 = $table_prefix."thekendienst2_timeframe";
	$table_name_old = $table_prefix."thekendienst";
	$sql1 = '
		CREATE TABLE '.$table_name1.' (
			ID mediumint(9) NOT NULL AUTO_INCREMENT KEY,
			Name varchar(127) NOT NULL,
			timeframes varchar(255) DEFAULT "");';//Tabellenstruktur wird angelegt.
	$sql2 = '
		CREATE TABLE '.$table_name2.' (
			ID mediumint(9) NOT NULL,
			ID_timeframe mediumint (9) NOT NULL AUTO_INCREMENT KEY,
			date varchar(9) NOT NULL,
			starting_time time,
			ending_time time,
			count smallint(9),
			comment varchar(255),
			participants text,
			hidden varchar(255) DEFAULT NULL,
			archive boolean DEFAULT "0" );';
	$current_db2_version=get_option('thekendienst2_db_version', null);
	$current_db_version = get_option('thekendienst_db_version', null);
	if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name1."'") != $table_name1 && $wpdb->get_var("SHOW TABLES LIKE '".$table_name2."'") != $table_name2) { //are the tables existent?
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //grants access to dbDelta
		dbDelta($sql1);
		dbDelta($sql2); //creates new tables in database
		add_option("thekendienst2_db_version", $thekendienst2_db_version);
		td_admin_notice('<div class="updated">'.__("The additional table for thekendienst2 has been created: The suffix of the name is *thekendienst2", "thekendienst2_textdomain"). '</div>');
		return $rueckgabe.mysql_error();
	}
	elseif($current_db2_version==null && $wpdb->get_var('SHOW TABLES LIKE "'.$table_name_old.'"') != $table_name_old && ($current_db_version=='0.1' OR $current_db_version=='0.2' OR $current_db_version=='0.3')) {
	//function needed to get data from old db's from Version 0.x to version 2.x
		$sql3 = '
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
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //grants access to dbDelta
		dbDelta($sql3);
		update_option("thekendienst_db_version", '0.4');
		td_admin_notice('<div class="updated">'.__("The table for thekendienst2 has been changed to an intermediate format (version 0.4) - the conversion-function to 2.0 is still missing (but you can start from scratch, if you like)", "thekendienst2_textdomain"). '</div>');
		//the function to put 0.4 db into 2.x db is missing!!!!
		
		// end of missing function !!!!
		return $rueckgabe.mysql_error();
		}
	else {
			return $rueckgabe;
		}
}

function td2_admin_notice($string) { 
//message to admin in backend
	return $string;
}

function td2_loadstylesheets(){ 
//loads the stylesheets

	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL."/thekendienst2/thekendienststyles.css' type='text/css' media='all' />";
}

function td2_loadstylesheets_admin(){ 
//loads the stylesheets for the backend

	td2_loadstylesheets();
}

function td2_loadjavascripts() {
//loads all the javascripts

	wp_enqueue_script('thekendienstscript', WP_PLUGIN_URL.'/thekendienst/thekendienstscript.js');
}

?>