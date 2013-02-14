<?php


/* *************************************************** 
Überprüfung der zuvor via Formular eingegebenen Daten
Schalten zu welcher Funktion es gehen soll.
*************************************************** */
//print_r($_POST);

if(isset($_POST['eintragen'])) add_filter('the_content','eintragenName');
elseif(isset($_POST['austragen'])) add_filter('the_content','austragenName');
add_filter('the_content', 'ThekendienstTabellenSchalter');

/* *************************************************** 
Hauptfunktion für normale Nutzer
*************************************************** */
	
function ThekendienstTabellenSchalter($content) {
	global $wpdb,$table_prefix;
	$sql='SELECT AufstellungsID, AufstellungsName FROM '.$table_prefix.'thekendienst GROUP BY AufstellungsID ORDER BY AufstellungsID';
	$tabelle=$wpdb->get_results($sql, ARRAY_A);
	if(isset($tabelle[0])) {
		foreach($tabelle as $zeile) {
			$replacestring1='[Thekendienst='.$zeile['AufstellungsID'].']';
			$replacestring2='[Thekendienst='.$zeile['AufstellungsName'].']';
			//Hier fehlt eine Funktion zum überprüfen ob eine Zeichenfolge in $content enthalten ist
			if(strpos($content, $replacestring1)) {
				$content=str_replace($replacestring1, Aufstellunganzeigen($zeile['AufstellungsName'], $zeile['AufstellungsID']), $content);
			}
			elseif(strpos($content, $replacestring2)) {
				$content=str_replace($replacestring2, Aufstellunganzeigen($zeile['AufstellungsName'], $zeile['AufstellungsID']),$content);
			}
		}
	}
	return $content;
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
				<a href="javascript:ein_ausklappen(\'thekendienst_zeitfenster_\',\''.$id.'\')">'.__('ein/ausblenden').'</a>
			</td>
		</tr>';
	return Tabellenanfang().$tabellenmitte.aufklappenListederZeitfenster($id, $veranstaltungsname, false).Tabellenende();
}
	
function aufklappenListederZeitfenster($ID, $AufstellungsName, $backend=true, $editierbar=true) { //Ruft eine Liste aller Zeitfenster zur Mutter-Veranstaltung auf ($ID)
	global $wpdb, $table_prefix, $user_ID;
	$letzteEndzeit="00:00:00";
	$i=0;
	(int) $further_i=null;
	$sql='SELECT IDZeitfenster, Tag, KommentarZeitfenster, Startzeit, Endzeit, AnzahlMitarbeiter, IDMitarbeiter, Ausgeblendet FROM '.$table_prefix.'thekendienst WHERE (AufstellungsID="'.$ID.'" AND Archiv!="1") ORDER BY Tag, Startzeit, AufstellungsID, IDZeitfenster, IDMitarbeiter ASC' ; //Abfrage der Einträge zur aktuellen Veranstaltung ($ID)
	$sql2='SELECT IDZeitfenster, Tag, KommentarZeitfenster, Startzeit, Endzeit, AnzahlMitarbeiter, IDMitarbeiter, Ausgeblendet FROM '.$table_prefix.'thekendienst WHERE (AufstellungsID="'.$ID.'") ORDER BY Tag, Startzeit, AufstellungsID, IDZeitfenster, IDMitarbeiter ASC' ; //Abfrage der Einträge zur aktuellen Veranstaltung ($ID)
	$tabelle=$wpdb->get_results($sql, ARRAY_A); //übertragen der Ergebnisse in ein mit Spaltennamen indexiertes Array
	$tabelle2=$wpdb->get_results($sql2, ARRAY_A); //übertragen der Ergebnisse in ein mit Spaltennamen indexiertes Array
	if(current_user_hat_es_ausgeblendet($user_ID, $tabelle[0]['Ausgeblendet']) AND $backend) {
		$display_var='none';
	}
	else $display_var='';
	if($tabelle!=null) {//überprüft ob die Veranstaltung überhaupt existiert.
		$rueckgabe='
		<tr>
			<td></td>
			<td colspan="4">
				<!-- Anfang der Zeitfenster -->
				<table class="thekendienst_zeitfenster" id="thekendienst_zeitfenster_'.$ID.'" style="display: '.$display_var.'">					
					<tbody>
						<tr class="headline">';
		$rueckgabe.='<td rowspan="2">'.__('Termin<br>down-<br>loaden').'</td><td colspan="5">'.__('Zeitfenster').'</td>';
		$rueckgabe.='	</tr>
						<tr class="headline">
							<td>'.__('Tag', 'thekendienst_textdomain').'</td>
							<td>'.__('Startzeit', 'thekendienst_textdomain').'</td>
							<td>'.__('Endzeit', 'thekendienst_textdomain').'</td>
							<td style="text-align: center;">'.__('Anzahl der').'<br/>'.__('Mitarbeiter', 'thekendienst_textdomain').'</td>
							<td>'.__('Kommentar', 'thekendienst_textdomain').'</td>
						</tr>'; //Die Kopfzeile der Zeitfenster wird generiert.
		$i=$i++;
		$hoechstezeitfensterID=0;
		foreach($tabelle as $zeile) {//geht durch alle Zeitfenster
			$further_i=$i;
			$i=$zeile['IDZeitfenster'];
			if($further_i==$i) continue; //prüft ob die aufgerufene Zeile dem vorgänger entspricht (dann bedarf es keines neuen Eintrages) und startet ggf. mit der nächsten iteration von foreach.
			if(is_admin()) $adminbearbeitenjavascript='<div id="loeschen_zeitfenster_'.$ID.'_'.$zeile['IDZeitfenster'].'_ausloeser">(<a href="javascript:bearbeitenformularaufmachen(\'Zeitfenster_\',\''.$ID.'\',\''.$zeile['IDZeitfenster'].'\')">bearbeiten</a>)<br/>(<a href="javascript:ein_ausklappen(\'loeschen_zeitfenster_\',\''.$ID.'_'.$zeile['IDZeitfenster'].'\',\'1\')">loeschen</a>) </div>';
			else $adminbearbeitenjavascript='';
			$rueckgabe.='
				<tr id="Zeitfenster_'.$ID.'_'.$zeile['IDZeitfenster'].'">
					<td align="center"><a href="'.WP_PLUGIN_URL.'/thekendienst/thekendienst_ics.php?title='.urlencode($AufstellungsName).'&blogname='.get_bloginfo('name', 'raw').'&day='.strtotime($zeile['Tag']).'&start='.$zeile['Startzeit'].'&end='.$zeile['Endzeit'].'&id='.$ID.'?comment='.$zeile['KommentarZeitfenster'].'&url='.get_permalink().'">ics</a></td>
					<td><!-- '.$zeile['Tag'].' -->'.date("l, j.n.Y", strtotime($zeile['Tag'])).'</td>
					<td>'.$zeile['Startzeit'].'</td>
					<td>'.$zeile['Endzeit'].'</td>
					<td align="center">'.$zeile['AnzahlMitarbeiter']/*.'('.$Anzahlderschoneingetragenen.')'*/.'</td>
					<td>'.$adminbearbeitenjavascript.$zeile['KommentarZeitfenster'].'<div id="loeschen_zeitfenster_'.$ID.'_'.$zeile['IDZeitfenster'].'" style="display: none"><form action="" method="post" name="eintragen"><input type="submit" value="wirklich l&ouml;schen" name="zeitfenster_loeschen" ><input type="hidden" name="ID_Veranstaltung" value="'.$ID.'"><input type="hidden" name="ID_Zeitfenster" value="'.$zeile['IDZeitfenster'].'"><input type="hidden" name="AnzahlMitarbeiter" value="'.$zeile['AnzahlMitarbeiter'].'"></form></div></td>

				</tr>';//Gibt das aktuell aufgerufene (eindeutige!) Zeitfenster aus
			$rueckgabe.=namensliste($ID, $zeile['IDZeitfenster'], $editierbar);//ruft das dazugehörige Feld der eingetragenen wieder.
			if($zeile['IDZeitfenster']>=$hoechstezeitfensterID) {//ermittelt ob die aktuelle ID des aktuellen Zeitfelds höher ist als irgendein vorher aufgerufenes. wenn ja:
				$hoechstezeitfensterID=$zeile['IDZeitfenster']; //wird der Zähler hochgesetzt
				$letzterTag=$zeile['Tag']; //wird der letzte Tag hochgesetzt (für übergabe)
				$letzteEndzeit=$zeile['Endzeit']; //wird die letzte Endzeit hochgesetzt. (für übergabe)
			}
		}
		if (is_admin()) { //überprüft ob der Betrachter angemeldet und berechtigt ist
			$rueckgabe.='
						<tr id="FormularZeitfenster_'.$ID.'">
							<td colspan="6">';
			$rueckgabe.=		neueszeitfensterformular_array(
					array(
						IDAufstellung=>$ID, 
						IDAufstellungsName=>$AufstellungsName, 
						hoechstezeitfensterID=>$IDLetztesZeitfenster, 
						defaultTag=>date(Y-m-d), 
						defaultstartzeit=>$letzteEndzeit, 
						defaultendzeit=>"09:30:00")
					);
			//($ID, $AufstellungsName, $hoechstezeitfensterID, $letzterTag="2011-01-01", $letzteEndzeit); //baut formular für neue Zeitfenster auf (wird nur gezeigt wenn berechtigt) - übergibt relevante Daten
			$rueckgabe.=	'</td>
						</tr>';//ruft eine Funktion auf, die ein Formular aufmacht, welches hidden die Variable $ID und $AufstellungsName enthält und um die Einträge IDZEitfenster, Tag, Startzeit, endzeit ergänzt werden kann. Der Tag und die vorhergehende Endzeit sollte in der Startzeit per default auftauchen.
		}
		$rueckgabe.='</tr>
					</tbody>
				</table>
			</td>
		</tr>';
	}
	elseif($tabelle2!=null) {//überprüft ob die Veranstaltung überhaupt existiert.
		$rueckgabe='
		<tr>
		<td></td>
		<td colspan="4">
			<!-- '.__('Anfang der Zeitfenster').' -->
			<table class="thekendienst_zeitfenster" id="thekendienst_zeitfenster_'.$ID.'">		
				<tbody>
				<tr><td>Diese Veranstaltung wurde gel&ouml;scht</td></tr>
				</tbody>
			</table>
		</td>
		</tr>
		';
	}
	else $rueckgabe='Fehler, die Veranstaltung in die du ein neues Zeitfenster integrieren wolltest, existiert nicht (mehr)'; //Wenn die Veranstaltung noch garnicht angelegt oder gelöscht wurde
	return $rueckgabe;
}

function namensliste($IDAufstellung, $IDZeitfenster, $editierbar=false) {//gibt die Liste der eingewählten zum Mutter-Zeitfenster wieder
	global $wpdb, $table_prefix, $current_user;
	$location=null;
	$sql='SELECT IDMitarbeiter, NameMitarbeiter FROM '.$table_prefix.'thekendienst'.' WHERE (AufstellungsID='.$IDAufstellung.' AND IDZeitfenster='.$IDZeitfenster.') ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter DESC, NameMitarbeiter DESC';
	$tabelle=$wpdb->get_results($sql, ARRAY_A);
	if($tabelle!=NULL) {//Veranstaltung existend? Schon jemand eingetragen?
		//Personenliste innerhalb der Zeitfenster anzeigen
		if(current_user_can('manage_options')) $anzeigederpersonen='block'; else $anzeigederpersonen='block';
		$rueckgabe='
						<tr>
							<td></td>';
		$rueckgabe.=' <td>
								<a href="javascript:ein_ausklappen(\'thekendienst_namen_\',\''.$IDAufstellung.'_'.$IDZeitfenster.'\')">'.__('(verbergen)').'
								</a>
							</td>'; //Erzeugt einen durch Javascript realisierten Einklapp-Knopf
		$rueckgabe.='	<td colspan="4">
								<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'" style="display: '.$anzeigederpersonen.'">
									<tbody>
										<tr class="headline">
											<td class="thekendienst_ID">ID</td>
											<td colspan="3" class="thekendienst_namen_ueberschrift" align="left">'.__('Name').'</td>
											<td>
											</td>
										</tr>'; //Gibt fir Überschrift der Personenliste 
		foreach($tabelle as $zeile) {//Geht jede Zeile des Zeitfensters der Veranstaltung durch
			$ID_Unique=$IDAufstellung.'_'.$IDZeitfenster;
			if($zeile['IDMitarbeiter']==0 || $zeile['IDMitarbeiter']==null) {//Prüft ob ein Mitarbeiter eingetragen ist. Wenn nicht:
				$rueckgabe.='			<tr>
											<td></td>
											<td colspan="2" align="left">'.__('-noch Platz-').'</td>
											<td></td>
											<td><a href="javascript:ein_ausklappen(\'Eintragfeld_\',\''.$ID_Unique.'\',true)" id="Eintragfeld_'.$ID_Unique.'_ausloeser">'.__('eintragen').'</a></td>
											<td></td>
										</tr>
										<tr id="Eintragfeld_'.$ID_Unique.'" style="display:none">
											<div>
												<form action="'.$location.'" method="post" name="eintragen'.$ID_Unique.'">
													<td></td>
													<td align="left">
														
														<input type="hidden" name="AufstellungsID" value="'.$IDAufstellung.'"/>
														<input type="hidden" name="IDZeitfenster" value="'.$IDZeitfenster.'"/>';
//Beginn des Aufklappmenüs aller Benutzer
				$rueckgabe.='							<select name="NameMitarbeiter" size="1" onchange="if(this.value==\'-Andere-\') {ein_ausklappen(\'NameMitarbeiterManuell_\',\''.$ID_Unique.'\', \'false\')} else {einklappen(\'NameMitarbeiterManuell_\',\''.$ID_Unique.'\', false)}">';
				$sql_user='SELECT ID, user_login, display_name FROM '.$wpdb->users;
				(array) $tabelle=$wpdb->get_results($sql_user, ARRAY_A);
				foreach($tabelle as $zeile) {
					if($zeile['user_login']==$current_user->user_login) $rueckgabe.='
															<option selected>'.$zeile['user_login'].'</option>';
					else $rueckgabe.='
															<option>'.$zeile['user_login'].'</option>';
				}
				$rueckgabe.='								<option>'.__('-Andere-').'</option>';
				$rueckgabe.='							</select>
														<input type="hidden" name="IDMitarbeiter" value=""/>
														<input type="text" size="20" maxlength="40" name= "NameMitarbeiterManuell" id="NameMitarbeiterManuell_'.$ID_Unique.'" value="" style="display:none"/>
													</td>
													<td></td>';					
//Ende des Aufklappmenüs aller Benutzer

//Absende-Knopf für Eintragvorgänge
				$rueckgabe.='					
													<td><input name="eintragen" value="eintragen" type="submit"/></td>
												</tr>
											</form>
										</div>';
				break;//sorgt dafür, dass nur einmal "-noch Platz-" da steht.
			}
			else {
				$sql='';
				if($zeile['IDMitarbeiter']=="999") $idM="x"; else $idM=$zeile['IDMitarbeiter'];
				$rueckgabe.='			<tr>
											<td>'.$idM.'</td>
											<td colspan="3" align="left">'.$zeile['NameMitarbeiter'].'</td>
											<td>
												<form action="" method="post" name="austragen'.$ID_Unique.'_'.$zeile['IDMitarbeiter'].'">
												<div id="Austragsfeld_'.$ID_Unique.'_'.$zeile['IDMitarbeiter'].'_ausloeser" style="display:">
													<a href="javascript:ein_ausklappen(\'Austragsfeld_\',\''.$ID_Unique.'_'.$zeile['IDMitarbeiter'].'\',true)" id="eintragenknopf_'.$ID_Unique.'">'.__('austragen').'</a>
												</div>
												<div id="Austragsfeld_'.$ID_Unique.'_'.$zeile['IDMitarbeiter'].'" style="display:none">
													<input type="hidden" name="AufstellungsIDAustragen" value="'.$IDAufstellung.'"/>
													<input type="hidden" name="IDZeitfensterAustragen" value="'.$IDZeitfenster.'"/>
													<input type="hidden" name="IDMitarbeiterAustragen" value="'.$zeile['IDMitarbeiter'].'"/>
													<input type="hidden" name="NameMitarbeiterAustragen" value="'.$zeile['NameMitarbeiter'].'"/>
													<input type="submit" name="austragen" value="austragen">
												</div>
												</form>
											</td>
										</tr>
										<tr id="Austragsfeld_'.$ID_Unique.'" style="display:none">
											<td colspan="3" align="right">
																							<td>
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
							<td><!--".__('Namesfeld fŸr weitere Personen')."--></td>
							<td><!--".__('Knopf fŸr weitere Personen')."--></td>
						</tr>"; //Namensfeld per Dafault mit Angemeldetem Benutzer gefüllt. Überprüfung: Wenn das Namensfeld mit dem angemeldeten übereinstimmt Funktion aufrufen die den angemeldeten einträgt. Ist dem nicht so, name mit bekannten Nutzern überprüfen, falls vorhanden, nachfragen und für die ID eintragen. Falls nicht: ID für neue Person erzeugen(tricky!) und eintragen.
	}
	else $rueckgabe=__('Fehler, das Zeitfenster und/oder die Veranstaltung existiert nicht(mehr) = Irgendwer hat an der Datenbank was kaputt gemacht');
	return $rueckgabe;
}

/* *************************************************** 
Anzeige der Admintabelle (Alle Veranstaltungen)
*************************************************** */

function ThekendienstAdminPanel() {
	add_options_page('Thekendienst', 'Thekendienst', 'edit_posts', 'thekendienst-optionen', 'ThekendienstAdminFunktion');
	}

function ThekendienstAdminFunktion() {//schalter der je nach Variableninhalt entscheidet was gemacht werden soll
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

function AufstellungermittelnAdmin() {//
	global $wpdb, $user_ID;
	global $table_prefix;
	(string) $rueckgabe="";
	$tabelle=$wpdb->get_results('SELECT AufstellungsID, AufstellungsName, Ausgeblendet FROM '.$table_prefix.'thekendienst WHERE Archiv!="1" GROUP BY AufstellungsID ORDER BY AufstellungsID DESC', ARRAY_A); //Holt alle vorhanden Einträge aus der Datenbank
	if(is_array($tabelle)) {
		$letzterEintrag=end($tabelle);
		foreach($tabelle as $zeile) {
			//Geht das Veranstaltungsnamen-Array durch (ruft die Übersicht der Veranstaltungen auf)
			//echo current_user_hat_es_ausgeblendet($user_ID, $zeile['Ausgeblendet']);
			if(current_user_hat_es_ausgeblendet($user_ID, $zeile['Ausgeblendet'])===true) {
				$aus_einblend_string=__('dauerhaft einblenden', 'thekendienst_textdomain');
			}
			else $aus_einblend_string=__('dauerhaft ausblenden', 'thekendienst_textdomain');
			$rueckgabe.='
			<tr>
				<td>'.$zeile["AufstellungsID"].'</td>
				<td>'.$zeile["AufstellungsName"].'</td>
				<td align="center" id="bearbeitenzurueck">'.veranstaltungloeschen_formular($content="", $zeile["AufstellungsID"]).'</td>
				<td align="right"><a href="javascript:veranstaltung_ein_ausklappen(\'dauerhaft_ein_ausblenden_form\',\''.$zeile["AufstellungsID"].'\',\'1\')" id="dauerhaft_ein_ausblenden_form'.$zeile["AufstellungsID"].'_ausloeser">'.$aus_einblend_string.'</a>
					<form action="" name="dauerhaft_ein_ausblenden_form" method="post" id="dauerhaft_ein_ausblenden_form'.$zeile["AufstellungsID"].'">
						<input type="hidden" value="ein_ausblenden'.$zeile["AufstellungsID"].'" name="dauerhaft_ein_ausblenden">
						<input type="hidden" value="'.$user_ID.'" name="aktuellerbenutzer">
						<input type="submit" name="dauerhaft_ein_ausblenden_button" value="'.__('dauerhaft ein/ausblenden').'" style="display: none;">
					</form>
				</td>
				<td align="right">
					<a href="javascript:ein_ausklappen(\'thekendienst_zeitfenster_\',\''.$zeile["AufstellungsID"].'\')">'.__('ein/ausblenden').'</a>
				</td>
			</tr>'; //Ausgabe der Veranstaltungsliste
			$rueckgabe.= aufklappenListederZeitfenster($zeile["AufstellungsID"],  $zeile["AufstellungsName"]); //ruft die zur Veranstaltung gehörenden Zeitfenster auf.
		}
	}
	if(isset($zeile)) {
		return $rueckgabe.NeueVeranstaltungFormular($zeile["AufstellungsID"]); //Die rekursiv vorher aufgerufenen Elemente werden mit einem Formular zum eintragen weiterer Veranstaltungen(letzte VeranstaltungsID wird übergeben) zurückgegeben.
	}
	else return $rueckgabe.NeueVeranstaltungFormular(0);
}


/* *************************************************** 
Formulare zur Dateneingabe.
*************************************************** */

function NeueVeranstaltungFormular($letzteAufstellungsID) {//Baut ein Formular auf um neue Veranstaltungen zu erzeugen
	global $wpdb, $table_prefix;
	$hoechste_id=$wpdb->get_var('
		SELECT DISTINCT AufstellungsID 
		FROM '.$table_prefix.'thekendienst 
		ORDER BY AufstellungsID 
		DESC 
		LIMIT 1
		');
	$id=$hoechste_id+1; //berechnet die neue ID
	$rueckgabe='
	<tr>
		<td></td>
		<td colspan="5">
			<form action="" method="post">';//FOrmular eröffnet, referenziert auf dieses Script.
	$rueckgabe.='
				<table class="thekendienst_main" id="thekendienst_main_NeueVeranstaltung">';
	$rueckgabe.='
					<tr class="headline">
						<td colspan="4">'.__('Neue Veranstaltung eintragen').'</td>
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
							<input type="submit" name="neueveranstaltunggesetzt" value="'.__('erstellen').'"/>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>';//ermöglicht Veranstaltungsnamen bis 45 Zeilen. Übergibt im Formular zusätzlich noch daten.
	return $rueckgabe;
}

function neueszeitfensterformular_array($values) {
	$IDAufstellung = $values['IDAufstellung'];
	$IDAufstellungsName = $values['IDAufstellungsName'];
	$IDLetztesZeitfenster = $values['IDLetztesZeitfenster'];
	$defaulttag = $values['defaulttag'];
	$defaultstartzeit = $values['defaultstartzeit'];
	$defaultendzeit = $values['defaultendzeit'];
	if($defaultstartzeit=="") $defaultstartzeit='00:01:00'; //falls keine defaultzeit übergeben wurde
	if($defaulttag=="") $defaulttag='2010-01-01'; //falls kein defaulttag übergeben wurde
	if($defaultendzeit == "") $defaultendzeit="09:30:00";
	print_r($values);
	return neueszeitfensterformular($IDAufstellung, $IDAufstellungsName, $IDLetztesZeitfenster, $defaulttag, $defaultstartzeit, $defaultendzeit);
}

function neueszeitfensterformular($IDAufstellung, $IDAufstellungsName, $IDLetztesZeitfenster, $defaulttag, $defaultstartzeit, $defaultendzeit) { //Erzeugt ein Formular um neue Zeitfenster anzulegen.
	if(is_admin()) $defaultendzeit=$defaultstartzeit;
	$IDZeitfenster=$IDLetztesZeitfenster+1;//Berechnet die neue ID des Zeitfensters
	
	$rueckgabe='
								<form action="" method="post">
									<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'">
										<tr><td colspan="5" align="left" id="ueberschriftformular"><strong>'.__('Neues zeitfenster erzeugen').'</strong></td></tr>
										<tr><td colspan="5"><label>yyy</label> zzz</td></tr>
										<tr>
											<td>'.__('Tag').'</td>

											<td>'.__('Startzeit').'</td>
											<td>'.__('Endzeit').'</td>
											<td>'.__('Anzahl der ').'<br/>'.__('Mitarbeiter').'</td>
											<td>'.__('Kommentar').'</td>
										</tr>
										<tr>
											<td><input type="text" size="13" maxlength="10" name="Tag" value="'.$defaulttag.'"/></td>
											<td><input type="text" size="8" maxlength="8" name="Startzeit" value="'.$defaultstartzeit.'" /></td>
											<td><input type="text" size="8" maxlength="8" name="Endzeit"  value="'.$defaultendzeit.'"/></td>
											<td><input type="text" size="7" maxlength="2" name="AnzahlMitarbeiter" value="2"/></td>
											<td><input type="text" size="13" maxlength="44" name="KommentarZeitfenster" value=""/></td>
										</tr>
										<tr>
											<td>
												<input type="hidden" name="AufstellungsID" value="'.$IDAufstellung.'"/>
												<input type="hidden" name="AufstellungsName" value="'.$IDAufstellungsName.'"/>
												<input type="hidden" name="IDZeitfenster" value="'.$IDZeitfenster.'"/>
											</td>
											<td colspan="4" align="right">
												<input type="submit" value="'.__('eintragen').'" name="zeitfenstereintragen"/>
											</td>
										</tr>
									</table>
								</form>'; //Komplettes Formular zum eintragen. Defaultwerte werde anhand des vorherigen Zeitfensters übergeben. 
	return $rueckgabe;
}

/* *************************************************** 
Funktionen zur Dateneingabe.
*************************************************** */

function neueveranstaltungeintragen($content="") {//Veranstaltung in Datenbank eintragen (aus formular) -  aufruf durch hook!
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
		$sql='
			INSERT INTO '.$table_prefix.'thekendienst (AufstellungsID, AufstellungsName) 
			VALUES ("'.$zeile["AufstellungsID"].'","'.$zeile["AufstellungsName"].'")'; 
		$sql2='
			UPDATE '.$table_prefix.'thekendienst 
			SET Archiv="0",
			AufstellungsID="'.$zeile["AufstellungsID"].'",
			AufstellungsName="'.$zeile["AufstellungsName"].'",
			AnzahlMitarbeiter=null
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'"
				)
			';
		//echo $sql;
		//echo $sql_abfrage;
		$Anzahl=$wpdb->query($sql_abfrage);
		if($Anzahl=1) {//Nur einer oder kein Eintrag da? dann:
		  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (ergänzt)
		  	//echo mysql_error();
		}
		elseif($Anzahl=0) {
			
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
		return $content;
	}
	else echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Fehler, Es wurde kein Name vergeben (neueveranstaltungeintragen)', $content); //Gibt Fehler zurück, wenn das Formular leer abgeschickt wurde
}

function zeitfenstereintragen($content) {//erstellt neue Zeitfenster
	//error_log("zeitfenstereintragen erreicht", 3, "/php_temp.log");
	$zeile=$_POST;
	print_r($_POST);
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
				KommentarZeitfenster="'.$zeile["KommentarZeitfenster"].'",
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
				KommentarZeitfenster="'.$zeile["KommentarZeitfenster"].'",
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
		//echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']', ThekendienstTabellenSchalter($content), $content); //ruft die ergänzte Tabelle wieder auf (Script startet von neu)
		return $content;
	}
	else {//Die Daten sind im falschen Format
		echo 'Fehler: Die eingegebene Daten entsprechen nicht dem notwendigen Format (zeitfenstereintragen)';
	};
}

function zeitfensteraendern($content) {
	//error_log("zeitfensteraendern erreicht", 3, "/php_temp.log");
	$zeile=$_POST;
	global $wpdb, $table_prefix;
		if(
			check_time($zeile['Startzeit']) &&
			check_time($zeile['Endzeit']) &&
			check_date($zeile['Tag'],"Ymd","-") &&
			$zeile['AnzahlMitarbeiter']>0
		) {	//überprüft (mittels Hilfsfunktionen unten) ob die eingegebenen Daten im richtigen Formar vorliegen. Wenn ja:
			$sql='
				UPDATE '.$table_prefix.'thekendienst 
				SET IDZeitfenster="'.$zeile["IDZeitfenster"].'", 
					KommentarZeitfenster="'.$zeile["KommentarZeitfenster"].'",
					Tag="'.$zeile["Tag"].'", 
					Startzeit="'.$zeile["Startzeit"].'", 
					Endzeit="'.$zeile["Endzeit"].'", 
					AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"
				WHERE (AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
					AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
					IDZeitfenster="'.$zeile["IDZeitfenster"].'")
				ORDER BY ID ASC
				'; //Aktualisiert vorhandene Zeilen der Datenbank
			$sql2='
				INSERT INTO '.$table_prefix.'thekendienst 
				SET AufstellungsID="'.$zeile["AufstellungsID"].'",
					AufstellungsName="'.$zeile["AufstellungsName"].'",
					IDZeitfenster="'.$zeile["IDZeitfenster"].'", 
					KommentarZeitfenster="'.$zeile["KommentarZeitfenster"].'",
					Tag="'.$zeile["Tag"].'", 
					Startzeit="'.$zeile["Startzeit"].'", 
					Endzeit="'.$zeile["Endzeit"].'", 
					AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"'; //trägt neue Zeile in Datenbank ein. Ohne Namen
			$sql3='
				DELETE FROM '.$table_prefix.'thekendienst 
				WHERE (AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
					AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
					IDZeitfenster="'.$zeile["IDZeitfenster"].'")
				ORDER BY ID DESC
				LIMIT 1';
			  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (ergänzt)
				for($count = mysql_affected_rows(); $count < $zeile['AnzahlMitarbeiter']; $count++){ //Schleife die so oft wie Helfer abzüglich bereits existierender Einträge wiederholt
					$wpdb->query($sql2); //Trägt neue Zeilen in Datenbank ein (s.o.)
				}
				for($count = mysql_affected_rows(); $count > $zeile['AnzahlMitarbeiter']; $count--){ //Schleife die so oft wiederholt wird wie noch zu viele Einträge für das Zeitfenster vorgehalten werden
					$wpdb->query($sql3); //Löscht überflüssige Zeilen aus der Datenbank (s.o.)
				}
			unset($_POST);//Formulardaten werden gelöscht sofern nötig
			return $content;
		}
		else {//Die Daten sind im falschen Format
			echo 'Fehler: Die eingegebene Daten entsprechen nicht dem notwendigen Format (zeitfensteraendern)';
		};
}

function zeitfensterloeschen($content) {
	global $wpdb, $table_prefix;
	$i = $_POST["AnzahlMitarbeiter"]-1;
	$sql='
		DELETE FROM '.$table_prefix.'thekendienst 
		WHERE (AufstellungsID="'.$_POST["ID_Veranstaltung"].'" AND IDZeitfenster="'.$_POST["ID_Zeitfenster"].'")
		LIMIT '.$i;
	$sql_archiv='UPDATE '.$table_prefix.'thekendienst SET Archiv="1", AnzahlMitarbeiter="0" WHERE (AufstellungsID="'.$_POST["ID_Veranstaltung"].'" AND IDZeitfenster="'.$_POST["ID_Zeitfenster"].'")';
	$wpdb->query($sql);
	$wpdb->query($sql_archiv);
}

function veranstaltungloeschen_formular($content,$id) {//Veranstaltung in Datenbank eintragen (aus formular) -  aufruf durch hook!
	$rueckgabe = '
		<form action="" method="post" name="loeschen'.$id.'">
		<div id="Loeschen_'.$id.'_ausloeser" style="display:">
			<a href="javascript:ein_ausklappen(\'Loeschen_\',\''.$id.'\',true)" id="loeschenknopf_'.$id.'">l&ouml;schen</a>
		</div>
		<div id="Loeschen_'.$id.'" style="display:none">
			<input type="hidden" name="veranstaltungsid" value="'.$id.'">
			<input type="submit" name="veranstaltungloeschen" value="wirklich l&ouml;schen">
		</div>
		</form>';
	return $rueckgabe;
	/*
	
	*/
}

function dauerhaft_ein_ausblenden() {
	global $wpdb, $table_prefix;
	$id=str_replace('ein_ausblenden','', $_POST['dauerhaft_ein_ausblenden']);
	$sql_abfrage='
		SELECT Ausgeblendet 
		FROM '.$table_prefix.'thekendienst
		WHERE AufstellungsID='.$id.'
		';	
	$liste=$wpdb->get_results($sql_abfrage, ARRAY_A);
	$liste=array_unique($liste);
	foreach($liste as $liste_einzeln) {
		//print_r($liste_einzeln);
		if(count($liste_einzeln)=="1" && !current_user_hat_es_ausgeblendet($_POST['aktuellerbenutzer'], $liste_einzeln['Ausgeblendet'])) {
			$neuer_inhalt=$_POST['aktuellerbenutzer'].';'.$liste_einzeln['Ausgeblendet'];
			$sql='
				UPDATE '.$table_prefix.'thekendienst
				SET Ausgeblendet="'.$neuer_inhalt.'"
				WHERE AufstellungsID="'.str_replace('ein_ausblenden','',$_POST['dauerhaft_ein_ausblenden']).'"';
			$wpdb->query($sql);
		}
		elseif(count($liste_einzeln)=="1" && current_user_hat_es_ausgeblendet($_POST['aktuellerbenutzer'], $liste_einzeln['Ausgeblendet'])) {
			$neuer_inhalt=explode(";",$liste_einzeln['Ausgeblendet']);
			foreach($neuer_inhalt as $key => $user) {
				if($user==$_POST['aktuellerbenutzer']) {
					unset($neuer_inhalt[$key]);
				}
			}
			$neuer_inhalt=implode(';', $neuer_inhalt);
			$sql='
				UPDATE '.$table_prefix.'thekendienst
				SET Ausgeblendet="'.$neuer_inhalt.'"
				WHERE AufstellungsID="'.str_replace('ein_ausblenden','',$_POST['dauerhaft_ein_ausblenden']).'"';
			$wpdb->query($sql);
		}
		else echo "Datenbank kaputt -> support fragen! (function: dauerhaft_ein_ausblenden())";
		echo mysql_error();
	}
}

function current_user_hat_es_ausgeblendet($user_id, $Ausgeblendet_String) {
	$Ausgeblendet_Strings=explode(";",$Ausgeblendet_String);
	foreach($Ausgeblendet_Strings as $ausblend_id) {
		if($ausblend_id==$user_id) return true;
	}
	return false;
}


function veranstaltungloeschen($content) {
	//error_log("verantaltungloeschen_final erreicht/n", 3, "/php_temp.log");
	global $wpdb, $table_prefix;
	$sql_anzahleintraege='
		SELECT * FROM '.$table_prefix.'thekendienst 
		WHERE AufstellungsID="'.$_POST["veranstaltungsid"].'"
		';
	$anzahleintraege=$wpdb->query($sql_anzahleintraege);
	if($anzahleintraege>0) {
		$i=$anzahleintraege-1;
		$sql='
			DELETE FROM '.$table_prefix.'thekendienst 
			WHERE AufstellungsID="'.$_POST["veranstaltungsid"].'"
			LIMIT '.$i;
		$wpdb->query($sql);
		$sql2='
			UPDATE '.$table_prefix.'thekendienst 
			SET Archiv="1", AnzahlMitarbeiter="0"
			WHERE AufstellungsID="'.$_POST["veranstaltungsid"].'"';
		$wpdb->query($sql2);
	}
	else echo "an error occured";
	return $content.mysql_error().'blub';
}

function eintragenName($content) {
	global $wpdb, $table_prefix, $current_user;
	$zeile=$_POST;
	$sql='SELECT ID FROM '.$wpdb->users.' WHERE user_login=\''.$zeile["NameMitarbeiter"].'\'';
	//echo $sql;
	$idfromDB=$wpdb->get_row($sql, ARRAY_A);
	if($zeile["NameMitarbeiter"]!=null ) {
		if($zeile["NameMitarbeiter"]=='-Andere-') {
			$zeile["NameMitarbeiter"]=$zeile["NameMitarbeiterManuell"];
			$zeile["IDMitarbeiter"]="999";
		}
		if($idfromDB['ID']==null) $zeile["IDMitarbeiter"]="999";
		else $zeile['IDMitarbeiter']=$idfromDB['ID'];
		$sql='
			UPDATE '.$table_prefix.'thekendienst 
			SET IDMitarbeiter="'.$zeile["IDMitarbeiter"].'", 
				NameMitarbeiter="'.$zeile["NameMitarbeiter"].'"
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				IDZeitfenster="'.$zeile["IDZeitfenster"].'" AND
				IDMitarbeiter IS NULL)
			ORDER BY ID, AufstellungsID, IDZeitfenster, IDMitarbeiter ASC
			LIMIT 1'; //Aktualisiert vorhandene Zeilen der Datenbank
		$output=$wpdb->query($sql);
		echo mysql_error();
		if(mysql_affected_rows()==0) {
		echo 'Fehler: Entweder ist im Zeitfenster kein Platz mehr frei, oder das Zeitfenster existiert nicht, oder die Veranstaltung gibt es nicht. Es wurde kein Name vergeben (eintragenName)';
		}
		//return ThekendienstTabellenSchalter($content);
	}
	else {
		echo 'Fehler: Entweder wurde kein Name eingegeben oder für den Namen konnte keine ID ermittelt werden. (eintragenName)';
	}
	unset($_POST);
	//add_filter('the_content', 'ThekendienstTabellenSchalter');
	return $content;
}

function austragenName($content) {
	global $wpdb, $table_prefix, $current_user;
	$zeile=$_POST;
	$sql='
		UPDATE '.$table_prefix.'thekendienst 
		SET IDMitarbeiter=NULL, 
			NameMitarbeiter=NULL
		WHERE (
			AufstellungsID="'.$zeile["AufstellungsIDAustragen"].'" AND 
			IDZeitfenster="'.$zeile["IDZeitfensterAustragen"].'" AND
			NameMitarbeiter="'.$zeile["NameMitarbeiterAustragen"].'" AND
			IDMitarbeiter="'.$zeile["IDMitarbeiterAustragen"].'")
		ORDER BY AufstellungsID, IDZeitfenster, NameMitarbeiter
		LIMIT 1'; //Aktualisiert vorhandene Zeilen der Datenbank
	//echo $sql.'<br>';
	$output=$wpdb->query($sql);
	echo mysql_error();
	if(mysql_affected_rows()==0) {
		echo 'Fehler: Der Benutzer, die Veranstaltung oder das Zeitfenster konnte nicht gefunden werden. Evtl. wurde es zwischenzeitlich gelöscht. (Funktion: austragenName() )';
	}
	unset($_POST);
	return $content;
	//return ThekendienstTabellenSchalter($content);	
}


/* *************************************************** 
Hilfsfunktionen
*************************************************** */

function Tabellenanfang() {
	return '
	<table class="thekendienst_main" id="thekendienst_main"><tbody>
		<tr class="headline">
			<td>ID</td>
			<td colspan="4">Veranstaltung</td>
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




?>