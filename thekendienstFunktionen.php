<?php
//globale Variablen
$thekendienst_db_version = "0.1";

/* *************************************************** 
Initiierung der Hooks
*************************************************** */

add_action('wp_head', 'stylesheetsnachladen');
add_action('admin_head', 'stylesheetsnachladen');
add_action('init','Javascriptladen');
add_action('admin_init', 'Javascriptladen');
add_action('admin_menu', 'ThekendienstAdminPanel');

/* *************************************************** 
Überprüfung der zuvor via Formular eingegebenen Daten
Schalten zu welcher Funktion es gehen soll.
*************************************************** */

if(isset($_POST['zeitfenstereintragen'])) add_filter('the_content', 'zeitfenstereintragen');
elseif(isset($_POST['neueveranstaltunggesetzt'])) add_filter('the_content','neueveranstaltungeintragen');
elseif(isset($_POST['eintragen'])) add_filter('the_content','eintragenName');
else add_filter('the_content', 'ThekendienstTabellenSchalter');


/* *************************************************** 
Hauptfunktion für normale Nutzer
*************************************************** */
	
function ThekendienstTabellenSchalter($content) {
	global $wpdb,$table_prefix;
	$sql='SELECT AufstellungsID, AufstellungsName FROM '.$table_prefix.'thekendienst GROUP BY AufstellungsID ORDER BY AufstellungsID';
	$tabelle=$wpdb->get_results($sql, ARRAY_A);
	foreach($tabelle as $zeile) {
		$replacestring1='[Thekendienst='.$zeile[AufstellungsID].']';
		$replacestring2='[Thekendienst='.$zeile[AufstellungsName].']';
		//Hier fehlt eine Funktion zum überprüfen ob eine Zeichenfolge in $content enthalten ist
		if(strpos($content, $replacestring1)) {
			$content=str_replace($replacestring1, Aufstellunganzeigen($zeile[AufstellungsName], $zeile[AufstellungsID]), $content);
		}
		elseif(strpos($content, $replacestring2)) {
			$content=str_replace($replacestring2, Aufstellunganzeigen($zeile[AufstellungsName], $zeile[AufstellungsID]),$content);
		}
	}
	echo $content;
}

/* *************************************************** 
Anzeige der Tabelle (mit Editierknöpfen)
*************************************************** */

function Aufstellunganzeigen($veranstaltungsname,$id){
	$tabellenmitte='<tr>
			<td>'.$id.'</td>
			<td>'.$veranstaltungsname.'</td>
			<td></td>
			<td align="right">
				<a href="javascript:ein_ausklappen(\'thekendienst_zeitfenster_\',\''.$id.'\')">ein/ausblenden</a>
			</td>
		</tr>';
	return Tabellenanfang().$tabellenmitte.aufklappenListederZeitfenster($id, $veranstaltungsname).Tabellenende();
}
	
function aufklappenListederZeitfenster($ID, $AufstellungsName) { //Ruft eine Liste aller Zeitfenster zur Mutter-Veranstaltung auf ($ID)
	global $wpdb, $table_prefix;
	$sql='SELECT IDZeitfenster, Tag, Startzeit, Endzeit, AnzahlMitarbeiter, IDMitarbeiter FROM '.$table_prefix.'thekendienst WHERE AufstellungsID="'.$ID.'" ORDER BY AufstellungsID, IDZeitfenster, Tag, Startzeit, IDMitarbeiter' ; //Abfrage der Einträge zur aktuellen Veranstaltung ($ID)
	$tabelle=$wpdb->get_results($sql, ARRAY_A); //übertragen der Ergebnisse in ein mit Spaltennamen indexiertes Array
	if($tabelle!=NULL) {//überprüft ob die Veranstaltung überhaupt existiert.
		$rueckgabe='
		<tr>
			<td></td>
			<td colspan="3">';
		$rueckgabe.='
				<!-- Anfang der Zeitfenster -->
				<table class="thekendienst_zeitfenster" id="thekendienst_zeitfenster_'.$ID.'">					
					<tbody>
						<tr class="headline">
							<td>ID</td><td colspan="4">Zeitfenster</td>
						</tr>';
		$rueckgabe.='	<tr class="headline">
							<td>&nbsp;</td>
							<td>Tag</td>
							<td>Startzeit</td>
							<td>Endzeit</td>
							<td style="text-align: center;">Anzahl der <br/>Mitarbeiter</td>
						</tr>'; //Die Kopfzeile der Zeitfenster wird generiert.
		$i=$i++;
		$hoechstezeitfensterID=0;
		foreach($tabelle as $zeile) {//geht durch alle Zeitfenster
			$further_i=$i;
			$i=$zeile[IDZeitfenster];
			if($further_i==$i) continue; //prüft ob die aufgerufene Zeile dem vorgänger entspricht (dann bedarf es keines neuen Eintrages) und startet ggf. mit der nächsten iteration von foreach.
			$rueckgabe.='
						<tr>
							<td>'.$zeile[IDZeitfenster].'</td>
							<td>'.date("l, j.n.Y", strtotime($zeile[Tag])).'</td>
							<td>'.$zeile[Startzeit].'</td>
							<td>'.$zeile[Endzeit].'</td>
							<td align="center">'.$zeile[AnzahlMitarbeiter]/*.'('.$Anzahlderschoneingetragenen.')'*/.'</td>
						</tr>';//Gibt das aktuell aufgerufene (eindeutige!) Zeitfenster aus
			$rueckgabe.=namensliste($ID, $zeile[IDZeitfenster]);//ruft das dazugehörige Feld der eingetragenen wieder.
			if($zeile[IDZeitfenster]>=$hoechstezeitfensterID) {//ermittelt ob die aktuelle ID des aktuellen Zeitfelds höher ist als irgendein vorher aufgerufenes. wenn ja:
				$hoechstezeitfensterID=$zeile[IDZeitfenster]; //wird der Zähler hochgesetzt
				$letzterTag=$zeile[Tag]; //wird der letzte Tag hochgesetzt (für übergabe)
				$letzteEndzeit=$zeile[Endzeit]; //wird die letzte Endzeit hochgesetzt. (für übergabe)
			}
			
		}
		if (is_admin()) { //überprüft ob der Betrachter angemeldet und berechtigt ist
			$rueckgabe.='
						<tr>
							<td colspan="5">';
			$rueckgabe.=		neueszeitfensterformular($ID, $AufstellungsName, $hoechstezeitfensterID, $letzterTag, $letzteEndzeit); //baut formular für neue Zeitfenster auf (wird nur gezeigt wenn berechtigt) - übergibt relevante Daten
			$rueckgabe.=	'</td>
						</tr>';//ruft eine Funktion auf, die ein Formular aufmacht, welches hidden die Variable $ID und $AufstellungsName enthält und um die Einträge IDZEitfenster, Tag, Startzeit, endzeit ergänzt werden kann. Der Tag und die vorhergehende Endzeit sollte in der Startzeit per default auftauchen.
		}
		$rueckgabe.='
					</tbody>
				</table>
			</td>
		</tr>';
	}
	else $rueckgabe='Fehler, die Veranstaltung in die du ein neues Zeitfenster integrieren wolltest, existiert nicht (mehr)'; //Wenn die Veranstaltung noch garnicht angelegt oder gelöscht wurde
	return $rueckgabe;
}

function namensliste($IDAufstellung, $IDZeitfenster) {//gibt die Liste der eingewählten zum Mutter-Zeitfenster wieder
	global $wpdb, $table_prefix, $current_user;
	$sql='SELECT AufstellungsID, IDZeitfenster, IDMitarbeiter, NameMitarbeiter FROM '.$table_prefix.thekendienst.' WHERE (AufstellungsID='.$IDAufstellung.' AND IDZeitfenster='.$IDZeitfenster.') ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter DESC, NameMitarbeiter DESC';
	$tabelle=$wpdb->get_results($sql, ARRAY_A);
	if($tabelle!=NULL) {//Veranstaltung existend? Schon jemand eingetragen?
		//Personenliste innerhalb der Zeitfenster anzeigen
		if(current_user_can('manage_options')) $anzeigederpersonen='block'; else $anzeigederpersonen='none';
		$rueckgabe='
						<tr>
							<td>
								<a href="javascript:ein_ausklappen(\'thekendienst_namen_\',\''.$IDAufstellung.'_'.$IDZeitfenster.'\')">
									&harr;
								</a>
							</td>'; //Erzeugt einen durch Javascript realisierten Einklapp-Knopf
		$rueckgabe.='		<td colspan="4">
								<form action="'.$location.'" method="post">
								<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'" style="display: '.$anzeigederpersonen.'">
									<tbody>
										<tr class="headline">
											<td class="thekendienst_ID">ID</td>
											<td colspan="2" class="thekendienst_namen_ueberschrift" align="left">Name</td>
										</tr>'; //Gibt fir Überschrift der Personenliste aus
		//
		foreach($tabelle as $zeile) {//Geht jede Zeile des Zeitfensters der Veranstaltung durch
			if($zeile[IDMitarbeiter]==0 || $zeile[IDMitarbeiter]==null) {//Prüft ob ein Mitarbeiter eingetragen ist. Wenn nicht:
				$ID_Unique=$IDAufstellung.'_'.$IDZeitfenster;
				$rueckgabe.='			<tr>
											<td></td>
											<td align="left">-noch Platz-</td>
											<td align="right"><a href="javascript:ein_ausklappen(\'Eintragfeld_\',\''.$ID_Unique.'\')" id="eintragenknopf_'.$ID_Unique.'">eintragen</a></td>
										</tr>
										<tr id="Eintragfeld_'.$ID_Unique.'" style="display:none">
											<td></td>
											<td align="left">
												<input type="hidden" name="AufstellungsID" value="'.$IDAufstellung.'"/>
												<input type="hidden" name="IDZeitfenster" value="'.$IDZeitfenster.'"/>
												<input type="hidden" name="IDMitarbeiter" value="'.$current_user->ID.'"/>
												<input type="text" size="20" maxlength="40" name="NameMitarbeiter" value="'.$current_user->display_name.'"/></td>
											<td><input name="eintragen" value="eintragen" type="submit"/></td>
										</tr>';
				break;
			}
			else {
			
			if($zeile[IDMitarbeiter]=="999") $idM="x"; else $idM=$zeile[IDMitarbeiter];
			$rueckgabe.='			<tr>
											<td>'.$idM.'</td>
											<td colspan="2" align="left">'.$zeile[NameMitarbeiter].'</td>
										</tr>
										';	//Wenn eingetragen, ausgegeben
			}
		}
		$rueckgabe.='				</tbody>
								</table>
								</form>
							</td>
						</tr>';

		$rueckgabe.="
						<tr>
							<td></td>
							<td><!--Namensfeld für weitere Person--></td>
							<td><!--Knopf für weitere Personen--></td>
						</tr>"; //Namensfeld per Dafault mit Angemeldetem Benutzer gefüllt. Überprüfung: Wenn das Namensfeld mit dem angemeldeten übereinstimmt Funktion aufrufen die den angemeldeten einträgt. Ist dem nicht so, name mit bekannten Nutzern überprüfen, falls vorhanden, nachfragen und für die ID eintragen. Falls nicht: ID für neue Person erzeugen(tricky!) und eintragen.
	}
	else $rueckgabe="Fehler, das Zeitfenster und/oder die Veranstaltung existiert nicht(mehr)";
	return $rueckgabe;
}

/* *************************************************** 
Anzeige der Admintabelle (Alle Veranstaltungen)
*************************************************** */

function ThekendienstAdminPanel() {
	add_options_page('Thekendienst', 'Thekendienst', 'edit_posts', $location, 'ThekendienstAdminFunktion');
	}

function ThekendienstAdminFunktion() {
	if(isset($_POST['zeitfenstereintragen'])) zeitfenstereintragen("");
	elseif(isset($_POST['neueveranstaltunggesetzt'])) neueveranstaltungeintragen("");
	elseif(isset($_POST['eintragen'])) eintragenName("");
	echo Tabellenanfang().AufstellungermittelnAdmin().Tabellenende();
}

function AufstellungermittelnAdmin() {
	global $wpdb;
	global $table_prefix;
	$tabelle=$wpdb->get_results('SELECT AufstellungsID, AufstellungsName FROM '.$table_prefix.'thekendienst GROUP BY AufstellungsID ORDER BY AufstellungsID', ARRAY_A); //Holt alle vorhanden Einträge au der Datenbank
	$letzterEintrag=end($tabelle);
	if($letzterEintrag["AufstellungsID"]!=count($tabelle)) {//überprüft (rudimentiert) die vialidität der Tabelle. Beide arrays  gleiche Anzahl von Elementen? Wenn nicht wird nachfolgende Fehlermeldung ausgegeben...
		echo '<tr><td colspan="2">Fehler: Die Tabelle ist korrupt -> Support fragen (Aufstellungermitteln)</td></tr>'; 
	}
	else { //Tabelle ist (rudiementär) in Ordnung
		foreach($tabelle as $zeile) {
			//Geht das Veranstaltungsnamen-Array durch
			$rueckgabe.='
			<tr>
				<td>'.$zeile["AufstellungsID"].'</td>
				<td>'.$zeile["AufstellungsName"].'</td>
				<td></td>
				<td align="right">
					<a href="javascript:ein_ausklappen(\'thekendienst_zeitfenster_\',\''.$zeile["AufstellungsID"].'\')">ein/ausblenden</a>
				</td>
			</tr>'; //Ausgabe der Veranstaltungsliste
			$rueckgabe.= aufklappenListederZeitfenster($zeile["AufstellungsID"],  $zeile["AufstellungsName"]); //ruft die zur Veranstaltung gehörenden Zeitfenster auf.
			}
		}
	return $rueckgabe.NeueVeranstaltungFormular($zeile["AufstellungsID"]); //Die rekursiv vorher aufgerufenen Elemente werden mit einem Formular zum eintragen weiterer Veranstaltungen(letzte VeranstaltungsID wird übergeben) zurückgegeben.
}


/* *************************************************** 
Formulare zur Dateneingabe.
*************************************************** */

function NeueVeranstaltungFormular($letzteAufstellungsID) {//Baut ein Formular auf um neue Veranstaltungen zu erzeugen
	$id=$letzteAufstellungsID+1; //berechnet die neue ID
	$rueckgabe='
	<tr>
		<td colspan="4">
			<form action="'./*$_SERVER['PHP_SELF']*/$location.'" method="post">';//FOrmular eröffnet, referenziert auf dieses Script.
	$rueckgabe.='
				<table class="thekendienst_main" id="thekendienst_main_NeueVeranstaltung">';
	$rueckgabe.='
					<tr class="headline">
						<td colspan="4">Neue Veranstaltung eintragen</td>
					</tr>';
	$rueckgabe.='
					<tr>
						<td>
							<input type="hidden" name="AufstellungsID" value="'.$id.'"/>
						</td>
						<td colspan="2">
							<input type="text" name="AufstellungsName" size="45" maxlength="50"/>
						</td>
						<td align="right">
							<input type="submit" name="neueveranstaltunggesetzt" value="erstellen"/>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>';//ermöglicht Veranstaltungsnamen bis 45 Zeilen. Übergibt im Formular zusätzlich noch daten.
	return $rueckgabe;
}

function neueszeitfensterformular($IDAufstellung, $IDAufstellungsName, $IDLetztesZeitfenster, $defaulttag, $defaultstartzeit) { //Erzeugt ein Formular um neue Zeitfenster anzulegen.
	$IDZeitfenster=$IDLetztesZeitfenster+1;//Berechnet die neue ID des Zeitfensters
	if($defaultstartzeit=="") $defaultstartzeit='00:00'; //falls keine defaultzeit übergeben wurde
	if($defaulttag=="") $defaulttag='2010-01-01'; //falls kein defaulttag übergeben wurde
	$rueckgabe='
								<form action="'.$location.'" method="post">
									<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'">
										<tr><td colspan="4" align="left"><strong>Neues Zeitfenster erzeugen</strong></td></tr>
										<tr>
											<td>Tag</td>
											<td>Startzeit</td>
											<td>Endzeit</td>
											<td>Anzahl der<br/>Mithelfer</td>
										</tr>
										<tr>
											<td><input type="text" size="10" maxlength="10" name="Tag" value="'.$defaulttag.'"/></td>
											<td><input type="text" size="10" maxlength="8" name="Startzeit" value="'.$defaultstartzeit.'" /></td>
											<td><input type="text" size="10" maxlength="8" name="Endzeit"  value="'.$defaultstartzeit.'"/></td>
											<td><input type="text" size="10" maxlength="2" name="AnzahlMitarbeiter" value="2"/></td>
										</tr>
										<tr>
											<td>
												<input type="hidden" name="AufstellungsID" value="'.$IDAufstellung.'"/>
												<input type="hidden" name="AufstellungsName" value="'.$IDAufstellungsName.'"/>
												<input type="hidden" name="IDZeitfenster" value="'.$IDZeitfenster.'"/>
											</td>
											<td colspan="4" align="right">
												<input type="submit" value="eintragen" name="zeitfenstereintragen"/>
											</td>
										</tr>
									</table>
								</form>'; //Komplettes Formular zum eintragen. Defaultwerte werde anhand des vorherigen Zeitfensters übergeben. 
	return $rueckgabe;
}



/* *************************************************** 
Funktionen zur Dateneingabe.
*************************************************** */

function neueveranstaltungeintragen($content) {//Veranstaltung in Datenbank eintragen (aus formular) -  aufruf durch hook!
	global $wpdb, $table_prefix;
	$zeile=$_POST;
	if($zeile["AufstellungsName"]!="") {//Prüft ob Formular leer war
		$sql_abfrage='
			SELECT AufstellungsID, AufstellungsName
			FROM '.$table_prefix.'thekendienst 
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'"
				)'; //Überprüfung ob ein Zeitfenster mit der gleichen ID (und anderem) existiert.
		$sql='INSERT INTO '.$table_prefix.'thekendienst (AufstellungsID, AufstellungsName) VALUES ("'.$zeile["AufstellungsID"].'","'.$zeile["AufstellungsName"].'")'; 
		echo $sql;
		echo $sql_abfrage;
		$Anzahl=$wpdb->query($sql_abfrage);
		if($Anzahl<=1) {//Nur einer oder kein Eintrag da? dann:
		  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (ergänzt)
		  	echo mysql_error();
		} 
		elseif($Anzahl>1) {
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Die Datenbank ist defekt -> Bitte Administrator/Support umgehend informieren', $content); //...Script wird abgebrochen
			return NULL;
		}
		else {//Bereits ein Eintrag vorhanden...
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Es ist zu vermuten, dass das Formular doppelt abgeschickt wurde. Script wird unterbrochen.', $content); //...Script wird abgebrochen
			return NULL; //gibt nichts zurück (aber egal, function ist void
		}
		unset($_POST);//Formulardaten werden gelöscht sofern nötig
	}
	else echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Fehler, Es wurde kein Name vergeben (neueveranstaltungeintragen)', $content); //Gibt Fehler zurück, wenn das Formular leer abgeschickt wurde
}

function zeitfenstereintragen($content) {//erstellt neue Zeitfenster
$zeile=$_POST;
global $wpdb, $table_prefix;
	if(
		check_time($zeile['Startzeit']) &&
		check_time($zeile['Endzeit']) &&
		check_date($zeile['Tag'],"Ymd","-") &&
		$zeile['AnzahlMitarbeiter']>0
	) {	//überprüft (mittels Hilfsfunktionen unten) ob die eingegebenen Daten im richtigen Formar vorliegen. Wenn ja:
		$sql_abfrage='
			SELECT *
			FROM '.$table_prefix.'thekendienst 
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
				IDZeitfenster="'.$zeile["IDZeitfenster"].'"
				)'; //Überprüfung ob ein Zeitfenster mit der gleichen ID (und anderem) existiert.
		$sql='
			UPDATE '.$table_prefix.'thekendienst 
			SET IDZeitfenster="'.$zeile["IDZeitfenster"].'", 
				Tag="'.$zeile["Tag"].'", 
				Startzeit="'.$zeile["Startzeit"].'", 
				Endzeit="'.$zeile["Endzeit"].'", 
				AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"
			WHERE (AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
				IDZeitfenster IS NULL)
			ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter
			LIMIT 1'; //Aktualisiert vorhandene Zeilen der Datenbank
		$sql2='
			INSERT INTO '.$table_prefix.'thekendienst 
			SET AufstellungsID="'.$zeile["AufstellungsID"].'",
				AufstellungsName="'.$zeile["AufstellungsName"].'",
				IDZeitfenster="'.$zeile["IDZeitfenster"].'", 
				Tag="'.$zeile["Tag"].'", 
				Startzeit="'.$zeile["Startzeit"].'", 
				Endzeit="'.$zeile["Endzeit"].'", 
				AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"'; //trägt neue Zeile in Datenbank ein. Ohne Namen
		$Anzahl=$wpdb->query($sql_abfrage);//überprüft doppelpost
		if($Anzahl<=1) {//Nur einer oder kein Eintrag da? dann:
		  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (ergänzt)
			for($count = mysql_affected_rows(); $count < $zeile['AnzahlMitarbeiter']; $count++){ //Schleife die so oft wie Helfer abzüglich bereits existierender Einträge wiederholt
				$wpdb->query($sql2); //Trägt neue Zeilen in Datenbank ein (s.o.)
			}
		} 
		else {//Bereits mehr als ein Eintrag vorhanden...
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Es ist zu vermuten, dass das Formular doppelt abgeschickt wurde. Script wird unterbrochen.', $content); //...Script wird abgebrochen
			return NULL; //gibt nichts zurück (aber egal, function ist void
		}
		unset($_POST);//Formulardaten werden gelöscht sofern nötig
		echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']', ThekendienstTabellenSchalter($content), $content); //ruft die ergänzte Tabelle wieder auf (Script startet von neu)
		
	}
	else {//Die Daten sind im falschen Format
		echo 'Fehler: Die eingegebene Daten entsprechen nicht dem notwendigen Format (zeitfenstereintragen)';
	};
}

function str_replace_thekendienst($ersetzung) {
	
}

function eintragenName($content) {
	global $wpdb, $table_prefix, $current_user;
	$zeile=$_POST;
	if($zeile["NameMitarbeiter"]!=$current_user->display_name) $zeile["IDMitarbeiter"]="999";
	if($zeile["IDMitarbeiter"]!=null && $zeile["NameMitarbeiter"]!=null ) {
		$sql='
			UPDATE '.$table_prefix.'thekendienst 
			SET IDMitarbeiter="'.$zeile["IDMitarbeiter"].'", 
				NameMitarbeiter="'.$zeile["NameMitarbeiter"].'"
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				IDZeitfenster="'.$zeile["IDZeitfenster"].'" AND
				IDMitarbeiter IS NULL)
			ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter
			LIMIT 1'; //Aktualisiert vorhandene Zeilen der Datenbank
		$output=$wpdb->query($sql);
		echo mysql_error();
		if(mysql_affected_rows()==0) {
			return str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Fehler: Entweder ist im Zeitfenster kein Platz mehr frei, oder das Zeitfenster existiert nicht, oder die Veranstaltung gibt es nicht. Es wurde kein Name vergeben (eintragenName)', $content);
		}
		unset($_POST);
		return ThekendienstTabellenSchalter($content);
	}
	else {
		return str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Fehler: Entweder wurde kein Name eingegeben oder für den Namen konnte keine ID ermittelt werden. (eintragenName)', $content);
	}
	
}

/* *************************************************** 
Hilfsfunktionen
*************************************************** */

function Tabellenanfang() {
	return '
	<table class="thekendienst_main" id="thekendienst_main"><tbody>
		<tr class="headline">
			<td>ID</td>
			<td colspan="3">Veranstaltung</td>
		</tr>';
}

function Tabellenende() {
	return '</tbody></table>';
}

function check_date($date,$format,$sep){ //überprüft einen Datums-String auf das richtige Format   
    $pos1    = strpos($format, 'd');
    $pos2    = strpos($format, 'm');
    $pos3    = strpos($format, 'Y'); 
    $check    = explode($sep,$date);
    return checkdate($check[$pos2],$check[$pos1],$check[$pos3]);
}

function check_time($time) {//Überprüft einen Time-String auf Korrektheit)
	$parts = explode(':',$time);
	if (
		(count($parts) !== 2 && count($parts) !== 3) || 
		!is_numeric($parts[0]) || 
		!is_numeric($parts[1]) ||
		(!empty($parts[2]) && !is_numeric(($parts[2]))) || 
		$parts[0]    <  0 ||
		$parts[0]    > 24 ||
		$parts[1]    <  0 ||
		$parts[1]    > 60 ||
		$parts[2]    <  0 ||
		$parts[2]    > 60
		) return FALSE;
		return TRUE;
}

function stylesheetsnachladen(){ //Lädt die Stylesheets
	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL."/thekendienst/thekendienststyles.css' type='text/css' media='all' />";
}

function Javascriptladen() {//Lädt die JavaScript-Funktionen
	//wp_enqueue_script('jquery');
	wp_enqueue_script('thekendienstscript', WP_PLUGIN_URL.'/thekendienst/thekendienstscript.js');
}


/* *************************************************** 
Tabelle anlegen sofern noch nicht vorhanden.
*************************************************** */

function Datenbankanlegen() {//Sollte keine Tabelle vorliegen, wird eine erzeugt
   global $wpdb, $table_prefix;
   global $thekendienst_db_version;
   $table_name = $table_prefix."thekendienst";
   if($wpdb->get_var("SHOW TABLES LIKE ".$table_name) != $table_name) { //überprüft ob die Tabelle noch nicht existiert
		$sql = 'CREATE TABLE '.$table_name.' (
		ID mediumint(9) NOT NULL AUTO_INCREMENT KEY,
		AufstellungsID mediumint(9) NOT NULL,
		AufstellungsName varchar(45) DEFAULT "notset",
		IDZeitfenster smallint(9),
		Tag date,
		Startzeit time,
		Endzeit time,
		AnzahlMitarbeiter tinyint(9) DEFAULT "1",
		IDMitarbeiter smallint(9) DEFAULT NULL,
		NameMitarbeiter varchar(40),
		Ausgeblendet boolean DEFAULT "0")';//Tabellenstruktur wird angelegt.

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); //emröglicht Zugriff auf dbDelta-Funktion
		dbDelta($sql);//erzeugt neue Tabelle in Datenbank
		add_option("jal_db_version", $jal_db_version);
		return mysql_error().'<br><strong>Die Datenbank wurde erfolgreich angelegt</strong><br>';
   }
   else return;
}

?>