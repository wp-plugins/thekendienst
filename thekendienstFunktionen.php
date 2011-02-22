<?php


/* *************************************************** 
�berpr�fung der zuvor via Formular eingegebenen Daten
Schalten zu welcher Funktion es gehen soll.
*************************************************** */
//print_r($_POST);

if(isset($_POST['zeitfenstereintragen'])) add_filter('the_content', 'zeitfenstereintragen');
elseif(isset($_POST['neueveranstaltunggesetzt'])) add_filter('the_content','neueveranstaltungeintragen');
elseif(isset($_POST['eintragen'])) add_filter('the_content','eintragenName');
elseif(isset($_POST['austragen'])) add_filter('the_content','austragenName');
add_filter('the_content', 'ThekendienstTabellenSchalter');

/* *************************************************** 
Hauptfunktion f�r normale Nutzer
*************************************************** */
	
function ThekendienstTabellenSchalter($content) {
	global $wpdb,$table_prefix;
	$sql='SELECT AufstellungsID, AufstellungsName FROM '.$table_prefix.'thekendienst GROUP BY AufstellungsID ORDER BY AufstellungsID';
	$tabelle=$wpdb->get_results($sql, ARRAY_A);
	foreach($tabelle as $zeile) {
		$replacestring1='[Thekendienst='.$zeile['AufstellungsID'].']';
		$replacestring2='[Thekendienst='.$zeile['AufstellungsName'].']';
		//Hier fehlt eine Funktion zum �berpr�fen ob eine Zeichenfolge in $content enthalten ist
		if(strpos($content, $replacestring1)) {
			$content=str_replace($replacestring1, Aufstellunganzeigen($zeile['AufstellungsName'], $zeile['AufstellungsID']), $content);
		}
		elseif(strpos($content, $replacestring2)) {
			$content=str_replace($replacestring2, Aufstellunganzeigen($zeile['AufstellungsName'], $zeile[AufstellungsID]),$content);
		}
	}
	return $content;
}

/* *************************************************** 
Anzeige der Tabelle (mit Editierkn�pfen)
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
	
function aufklappenListederZeitfenster($ID, $AufstellungsName, $editierbar=true) { //Ruft eine Liste aller Zeitfenster zur Mutter-Veranstaltung auf ($ID)
	global $wpdb, $table_prefix;
	$i=0;
	(int) $further_i=null;
	$sql='SELECT IDZeitfenster, Tag, KommentarZeitfenster, Startzeit, Endzeit, AnzahlMitarbeiter, IDMitarbeiter FROM '.$table_prefix.'thekendienst WHERE AufstellungsID="'.$ID.'" ORDER BY AufstellungsID, IDZeitfenster, Tag, Startzeit, IDMitarbeiter' ; //Abfrage der Eintr�ge zur aktuellen Veranstaltung ($ID)
	$tabelle=$wpdb->get_results($sql, ARRAY_A); //�bertragen der Ergebnisse in ein mit Spaltennamen indexiertes Array
	if($tabelle!=NULL) {//�berpr�ft ob die Veranstaltung �berhaupt existiert.
		$rueckgabe='
		<tr>
			<td></td>
			<td colspan="4">
				<!-- Anfang der Zeitfenster -->
				<table class="thekendienst_zeitfenster" id="thekendienst_zeitfenster_'.$ID.'">					
					<tbody>
						<tr class="headline">';
		$rueckgabe.='<td>ID</td><td colspan="5">Zeitfenster</td>';
		$rueckgabe.='	</tr>
						<tr class="headline">
							<td>&nbsp;</td>
							<td>Tag</td>
							<td>Startzeit</td>
							<td>Endzeit</td>
							<td style="text-align: center;">Anzahl der <br/>Mitarbeiter</td>
							<td>Kommentar</td>
						</tr>'; //Die Kopfzeile der Zeitfenster wird generiert.
		$i=$i++;
		$hoechstezeitfensterID=0;
		foreach($tabelle as $zeile) {//geht durch alle Zeitfenster
			$further_i=$i;
			$i=$zeile['IDZeitfenster'];
			if($further_i==$i) continue; //pr�ft ob die aufgerufene Zeile dem vorg�nger entspricht (dann bedarf es keines neuen Eintrages) und startet ggf. mit der n�chsten iteration von foreach.
			if(is_admin()) $adminbearbeitenjavascript='(<a href="javascript:bearbeitenformularaufmachen(\'Zeitfenster_\',\''.$ID.'\',\''.$zeile['IDZeitfenster'].'\')">bearbeiten</a>) ';
			else $adminbearbeitenjavascript='';
			$rueckgabe.='
						<tr id="Zeitfenster_'.$ID.'_'.$zeile['IDZeitfenster'].'">
							<td>'.$zeile['IDZeitfenster'].'</td>
							<td><!-- '.$zeile['Tag'].' -->'.date("l, j.n.Y", strtotime($zeile['Tag'])).'</td>
							<td>'.$zeile['Startzeit'].'</td>
							<td>'.$zeile['Endzeit'].'</td>
							<td align="center">'.$zeile['AnzahlMitarbeiter']/*.'('.$Anzahlderschoneingetragenen.')'*/.'</td>
							<td>'.$adminbearbeitenjavascript.$zeile['KommentarZeitfenster'].'</td>

						</tr>';//Gibt das aktuell aufgerufene (eindeutige!) Zeitfenster aus
			$rueckgabe.=namensliste($ID, $zeile['IDZeitfenster'], $editierbar);//ruft das dazugeh�rige Feld der eingetragenen wieder.
			if($zeile['IDZeitfenster']>=$hoechstezeitfensterID) {//ermittelt ob die aktuelle ID des aktuellen Zeitfelds h�her ist als irgendein vorher aufgerufenes. wenn ja:
				$hoechstezeitfensterID=$zeile['IDZeitfenster']; //wird der Z�hler hochgesetzt
				$letzterTag=$zeile['Tag']; //wird der letzte Tag hochgesetzt (f�r �bergabe)
				$letzteEndzeit=$zeile['Endzeit']; //wird die letzte Endzeit hochgesetzt. (f�r �bergabe)
			}
			
		}
		if (is_admin()) { //�berpr�ft ob der Betrachter angemeldet und berechtigt ist
			$rueckgabe.='
						<tr id="FormularZeitfenster_'.$ID.'">
							<td colspan="6">';
			$rueckgabe.=		neueszeitfensterformular($ID, $AufstellungsName, $hoechstezeitfensterID, $letzterTag="2011-01-01", $letzteEndzeit="00:00:00"); //baut formular f�r neue Zeitfenster auf (wird nur gezeigt wenn berechtigt) - �bergibt relevante Daten
			$rueckgabe.=	'</td>
						</tr>';//ruft eine Funktion auf, die ein Formular aufmacht, welches hidden die Variable $ID und $AufstellungsName enth�lt und um die Eintr�ge IDZEitfenster, Tag, Startzeit, endzeit erg�nzt werden kann. Der Tag und die vorhergehende Endzeit sollte in der Startzeit per default auftauchen.
		}
		$rueckgabe.='
					</tbody>
				</table>
			</td>
		</tr>';
	}
	else $rueckgabe='Fehler, die Veranstaltung in die du ein neues Zeitfenster integrieren wolltest, existiert nicht (mehr)'; //Wenn die Veranstaltung noch garnicht angelegt oder gel�scht wurde
	return $rueckgabe;
}

function namensliste($IDAufstellung, $IDZeitfenster, $editierbar=false) {//gibt die Liste der eingew�hlten zum Mutter-Zeitfenster wieder
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
								<a href="javascript:ein_ausklappen(\'thekendienst_namen_\',\''.$IDAufstellung.'_'.$IDZeitfenster.'\')">
									(verbergen)
								</a>
							</td>'; //Erzeugt einen durch Javascript realisierten Einklapp-Knopf
		$rueckgabe.='	<td colspan="4">
								<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'" style="display: '.$anzeigederpersonen.'">
									<tbody>
										<tr class="headline">
											<td class="thekendienst_ID">ID</td>
											<td colspan="3" class="thekendienst_namen_ueberschrift" align="left">Name</td>
											<td>
											</td>
										</tr>'; //Gibt fir �berschrift der Personenliste 
		foreach($tabelle as $zeile) {//Geht jede Zeile des Zeitfensters der Veranstaltung durch
			$ID_Unique=$IDAufstellung.'_'.$IDZeitfenster;
			if($zeile['IDMitarbeiter']==0 || $zeile['IDMitarbeiter']==null) {//Pr�ft ob ein Mitarbeiter eingetragen ist. Wenn nicht:
				$rueckgabe.='			<tr>
											<td></td>
											<td colspan="2" align="left">-noch Platz-</td>
											<td></td>
											<td><a href="javascript:ein_ausklappen(\'Eintragfeld_\',\''.$ID_Unique.'\',true)" id="Eintragfeld_'.$ID_Unique.'_ausloeser">eintragen</a></td>
											<td></td>
										</tr>
										<tr id="Eintragfeld_'.$ID_Unique.'" style="display:none">
											<div>
												<form action="'.$location.'" method="post" name="eintragen'.$ID_Unique.'">
													<td></td>
													<td align="left">
														
														<input type="hidden" name="AufstellungsID" value="'.$IDAufstellung.'"/>
														<input type="hidden" name="IDZeitfenster" value="'.$IDZeitfenster.'"/>';
//Beginn des Aufklappmen�s aller Benutzer
				$rueckgabe.='							
														<select name="NameMitarbeiter" size="1" onchange="if(this.value==\'-Andere-\') {ein_ausklappen(\'NameMitarbeiterManuell_\',\''.$ID_Unique.'\', \'false\')} else {einklappen(\'NameMitarbeiterManuell_\',\''.$ID_Unique.'\', false)}">';
				$sql_user='SELECT ID, user_login, display_name FROM '.$wpdb->users;
				(array) $tabelle=$wpdb->get_results($sql_user, ARRAY_A);
				foreach($tabelle as $zeile) {
					if($zeile['user_login']==$current_user->user_login) $rueckgabe.='
															<option selected>'.$zeile['user_login'].'</option>';
					else $rueckgabe.='
															<option>'.$zeile['user_login'].'</option>';
				}
				$rueckgabe.='								<option>-Andere-</option>';
				$rueckgabe.='							</select>
														<input type="hidden" name="IDMitarbeiter" value=""/>
														<input type="text" size="20" maxlength="40" name= "NameMitarbeiterManuell" id="NameMitarbeiterManuell_'.$ID_Unique.'" value="" style="display:none"/>
													</td>
													<td></td>';					
//Ende des Aufklappmen�s aller Benutzer

//Absende-Knopf f�r Eintragvorg�nge
				$rueckgabe.='					
													<td><input name="eintragen" value="eintragen" type="submit"/></td>
												</tr>
											</form>
										</div>';
				break;//sorgt daf�r, dass nur einmal "-noch Platz-" da steht.
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
													<a href="javascript:ein_ausklappen(\'Austragsfeld_\',\''.$ID_Unique.'_'.$zeile['IDMitarbeiter'].'\',true)" id="eintragenknopf_'.$ID_Unique.'">austragen</a>
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
							<td><!--Namensfeld f�r weitere Person--></td>
							<td><!--Knopf f�r weitere Personen--></td>
						</tr>"; //Namensfeld per Dafault mit Angemeldetem Benutzer gef�llt. �berpr�fung: Wenn das Namensfeld mit dem angemeldeten �bereinstimmt Funktion aufrufen die den angemeldeten eintr�gt. Ist dem nicht so, name mit bekannten Nutzern �berpr�fen, falls vorhanden, nachfragen und f�r die ID eintragen. Falls nicht: ID f�r neue Person erzeugen(tricky!) und eintragen.
	}
	else $rueckgabe="Fehler, das Zeitfenster und/oder die Veranstaltung existiert nicht(mehr) = Irgendwer hat an der Datenbank was kaputt gemacht";
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
	if(isset($_POST['zeitfensteraendern'])) zeitfensteraendern("");
	elseif(isset($_POST['neueveranstaltunggesetzt'])) neueveranstaltungeintragen("");
	elseif(isset($_POST['eintragen'])) eintragenName("");
	elseif(isset($_POST['veranstaltungloeschen'])) veranstaltungloeschen_final("");
	echo Tabellenanfang().AufstellungermittelnAdmin().Tabellenende();
}

function AufstellungermittelnAdmin() {//
	global $wpdb;
	global $table_prefix;
	(string) $rueckgabe="";
	$tabelle=$wpdb->get_results('SELECT AufstellungsID, AufstellungsName FROM '.$table_prefix.'thekendienst GROUP BY AufstellungsID ORDER BY AufstellungsID', ARRAY_A); //Holt alle vorhanden Eintr�ge au der Datenbank
	if(is_array($tabelle)) {
		$letzterEintrag=end($tabelle);
		/*if($letzterEintrag["AufstellungsID"]!=count($tabelle)) {//�berpr�ft (rudiment�r) die Validit�t der Tabelle. Beide Arrays gleiche Anzahl von Elementen? Wenn nicht wird nachfolgende Fehlermeldung ausgegeben...
			echo '<tr><td colspan="2">Fehler: Die Tabelle ist korrupt -> Support fragen (Aufstellungermitteln)</td></tr>'; 
		}
		else { //Tabelle ist (rudiement�r) in Ordnung
		*/
			foreach($tabelle as $zeile) {
				//Geht das Veranstaltungsnamen-Array durch (ruft die �bersicht der Veranstaltungen auf)
				$rueckgabe.='
				<tr>
					<td>'.$zeile["AufstellungsID"].'</td>
					<td>'.$zeile["AufstellungsName"].'</td>
					<td align="center" id="bearbeitenzurueck">'.veranstaltungloeschen($content="", $zeile["AufstellungsID"]).'</td>
					<td align="right">
						<a href="javascript:ein_ausklappen(\'thekendienst_zeitfenster_\',\''.$zeile["AufstellungsID"].'\')">ein/ausblenden</a>
					</td>
				</tr>'; //Ausgabe der Veranstaltungsliste
				$rueckgabe.= aufklappenListederZeitfenster($zeile["AufstellungsID"],  $zeile["AufstellungsName"]); //ruft die zur Veranstaltung geh�renden Zeitfenster auf.
				}
		/*	}*/
	}
	return $rueckgabe.NeueVeranstaltungFormular($zeile["AufstellungsID"]); //Die rekursiv vorher aufgerufenen Elemente werden mit einem Formular zum eintragen weiterer Veranstaltungen(letzte VeranstaltungsID wird �bergeben) zur�ckgegeben.
}


/* *************************************************** 
Formulare zur Dateneingabe.
*************************************************** */

function NeueVeranstaltungFormular($letzteAufstellungsID) {//Baut ein Formular auf um neue Veranstaltungen zu erzeugen
	$id=$letzteAufstellungsID+1; //berechnet die neue ID
	$rueckgabe='
	<tr>
		<td></td>
		<td colspan="5">
			<form action="" method="post">';//FOrmular er�ffnet, referenziert auf dieses Script.
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
	</tr>';//erm�glicht Veranstaltungsnamen bis 45 Zeilen. �bergibt im Formular zus�tzlich noch daten.
	return $rueckgabe;
}

function neueszeitfensterformular($IDAufstellung, $IDAufstellungsName, $IDLetztesZeitfenster, $defaulttag, $defaultstartzeit, $defaultendzeit="09:30:00") { //Erzeugt ein Formular um neue Zeitfenster anzulegen.
	if(is_admin()) $defaultendzeit=$defaultstartzeit;
	$IDZeitfenster=$IDLetztesZeitfenster+1;//Berechnet die neue ID des Zeitfensters
	if($defaultstartzeit=="") $defaultstartzeit='00:00'; //falls keine defaultzeit �bergeben wurde
	if($defaulttag=="") $defaulttag='2010-01-01'; //falls kein defaulttag �bergeben wurde
	$rueckgabe='
								<form action="" method="post">
									<table class="thekendienst_namen" id="thekendienst_namen_'.$IDAufstellung.'_'.$IDZeitfenster.'">
										<tr><td colspan="5" align="left" id="ueberschriftformular"><strong>Neues Zeitfenster erzeugen</strong></td></tr>
										<tr>
											<td>Tag</td>

											<td>Startzeit</td>
											<td>Endzeit</td>
											<td>Anzahl der<br/>Mithelfer</td>
											<td>Kommentar</td>
										</tr>
										<tr>
											<td><input type="text" size="10" maxlength="10" name="Tag" value="'.$defaulttag.'"/></td>
											<td><input type="text" size="10" maxlength="8" name="Startzeit" value="'.$defaultstartzeit.'" /></td>
											<td><input type="text" size="10" maxlength="8" name="Endzeit"  value="'.$defaultendzeit.'"/></td>
											<td><input type="text" size="10" maxlength="2" name="AnzahlMitarbeiter" value="2"/></td>
											<td><input type="text" size="10" maxlength="44" name="KommentarZeitfenster" value=""/></td>
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
								</form>'; //Komplettes Formular zum eintragen. Defaultwerte werde anhand des vorherigen Zeitfensters �bergeben. 
	return $rueckgabe;
}

/* *************************************************** 
Funktionen zur Dateneingabe.
*************************************************** */

function neueveranstaltungeintragen($content="") {//Veranstaltung in Datenbank eintragen (aus formular) -  aufruf durch hook!
	global $wpdb, $table_prefix;
	$zeile=$_POST;
	if($zeile["AufstellungsName"]!="") {//Pr�ft ob Formular leer war
		$sql_abfrage='
			SELECT AufstellungsID, AufstellungsName
			FROM '.$table_prefix.'thekendienst 
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'"
				)'; //�berpr�fung ob ein Zeitfenster mit der gleichen ID (und anderem) existiert.
		$sql='INSERT INTO '.$table_prefix.'thekendienst (AufstellungsID, AufstellungsName) VALUES ("'.$zeile["AufstellungsID"].'","'.$zeile["AufstellungsName"].'")'; 
		//echo $sql;
		//echo $sql_abfrage;
		$Anzahl=$wpdb->query($sql_abfrage);
		if($Anzahl<=1) {//Nur einer oder kein Eintrag da? dann:
		  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (erg�nzt)
		  	//echo mysql_error();
		} 
		elseif($Anzahl>1) {
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Die Datenbank ist defekt -> Bitte Administrator/Support umgehend informieren', $content); //...Script wird abgebrochen
			return NULL;
		}
		else {//Bereits ein Eintrag vorhanden...
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Es ist zu vermuten, dass das Formular doppelt abgeschickt wurde. Script wird unterbrochen.', $content); //...Script wird abgebrochen
			return NULL; //gibt nichts zur�ck (aber egal, function ist void
		}
		unset($_POST);//Formulardaten werden gel�scht sofern n�tig
		return $content;
	}
	else echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Fehler, Es wurde kein Name vergeben (neueveranstaltungeintragen)', $content); //Gibt Fehler zur�ck, wenn das Formular leer abgeschickt wurde
}

function zeitfenstereintragen($content) {//erstellt neue Zeitfenster
	//error_log("zeitfenstereintragen erreicht", 3, "/php_temp.log");
	$zeile=$_POST;
	global $wpdb, $table_prefix;
	if(
		check_time($zeile['Startzeit']) &&
		check_time($zeile['Endzeit']) &&
		check_date($zeile['Tag'],"Ymd","-") &&
		$zeile['AnzahlMitarbeiter']>0
	) {	//�berpr�ft (mittels Hilfsfunktionen unten) ob die eingegebenen Daten im richtigen Formar vorliegen. Wenn ja:
		$sql_abfrage='
			SELECT *
			FROM '.$table_prefix.'thekendienst 
			WHERE (
				AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
				AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
				IDZeitfenster="'.$zeile["IDZeitfenster"].'"
				)'; //�berpr�fung ob ein Zeitfenster mit der gleichen ID (und anderem) existiert.
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
				AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"'; //tr�gt neue Zeile in Datenbank ein. Ohne Namen
		$Anzahl=$wpdb->query($sql_abfrage);//�berpr�ft doppelpost
		if($Anzahl<=1) {//Nur einer oder kein Eintrag da? dann:
		  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (erg�nzt)
			for($count = mysql_affected_rows(); $count < $zeile['AnzahlMitarbeiter']; $count++){ //Schleife die so oft wie Helfer abz�glich bereits existierender Eintr�ge wiederholt
				$wpdb->query($sql2); //Tr�gt neue Zeilen in Datenbank ein (s.o.)
			}
		} 
		else {//Bereits mehr als ein Eintrag vorhanden...
			echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']','Es ist zu vermuten, dass das Formular doppelt abgeschickt wurde. Script wird unterbrochen.', $content); //...Script wird abgebrochen
			return NULL; //gibt nichts zur�ck (aber egal, function ist void
		}
		unset($_POST);//Formulardaten werden gel�scht sofern n�tig
		//echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']', ThekendienstTabellenSchalter($content), $content); //ruft die erg�nzte Tabelle wieder auf (Script startet von neu)
		return $content;
	}
	else {//Die Daten sind im falschen Format
		echo 'Fehler: Die eingegebene Daten entsprechen nicht dem notwendigen Format (zeitfenstereintragen)';
	};
}

function zeitfensteraendern($content) {
	//error_log("zeitfensteraendern erreicht", 3, "/php_temp.log");
	$zeile=$_POST;
	//echo 'arabesque';
	//print_r($_POST);
	global $wpdb, $table_prefix;
		if(
			check_time($zeile['Startzeit']) &&
			check_time($zeile['Endzeit']) &&
			check_date($zeile['Tag'],"Ymd","-") &&
			$zeile['AnzahlMitarbeiter']>0
		) {	//�berpr�ft (mittels Hilfsfunktionen unten) ob die eingegebenen Daten im richtigen Formar vorliegen. Wenn ja:
			/*$sql_abfrage='
				SELECT *
				FROM '.$table_prefix.'thekendienst 
				WHERE (
					AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
					AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
					IDZeitfenster="'.$zeile["IDZeitfenster"].'"
					)'; */ //�berpr�fung ob ein Zeitfenster mit der gleichen ID (und anderem) existiert.
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
				ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter
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
					AnzahlMitarbeiter="'.$zeile["AnzahlMitarbeiter"].'"'; //tr�gt neue Zeile in Datenbank ein. Ohne Namen
			$sql3='
				DELETE FROM '.$table_prefix.'thekendienst 
				WHERE (AufstellungsID="'.$zeile["AufstellungsID"].'" AND 
					AufstellungsName="'.$zeile["AufstellungsName"].'" AND 
					IDZeitfenster="'.$zeile["IDZeitfenster"].'")
				ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter DESC
				LIMIT 1';
			  	$wpdb->query($sql);//Wenn einer vorhanden wird dieser aktualisiert (erg�nzt)
			  	//print_r($wpdb);
				for($count = mysql_affected_rows(); $count < $zeile['AnzahlMitarbeiter']; $count++){ //Schleife die so oft wie Helfer abz�glich bereits existierender Eintr�ge wiederholt
					$wpdb->query($sql2); //Tr�gt neue Zeilen in Datenbank ein (s.o.)
				}
				for($count = mysql_affected_rows(); $count > $zeile['AnzahlMitarbeiter']; $count--){ //Schleife die so oft wiederholt wird wie noch zu viele Eintr�ge f�r das Zeitfenster vorgehalten werden
					$wpdb->query($sql3); //L�scht �berfl�ssige Zeilen aus der Datenbank (s.o.)
				}
			unset($_POST);//Formulardaten werden gel�scht sofern n�tig
			//echo str_replace('[Thekendienst='.$zeile["AufstellungsID"].']', ThekendienstTabellenSchalter($content), $content); //ruft die erg�nzte Tabelle wieder auf (Script startet von neu)
			return $content;
		}
		else {//Die Daten sind im falschen Format
			echo 'Fehler: Die eingegebene Daten entsprechen nicht dem notwendigen Format (zeitfensteraendern)';
		};
}

function veranstaltungloeschen($content,$id) {//Veranstaltung in Datenbank eintragen (aus formular) -  aufruf durch hook!
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

function veranstaltungloeschen_final($content) {
	//error_log("verantaltungloeschen_final erreicht/n", 3, "/php_temp.log");
	global $wpdb, $table_prefix;
	$sql='
		DELETE FROM '.$table_prefix.'thekendienst 
		WHERE AufstellungsID="'.$_POST["veranstaltungsid"].'"
		';
	$wpdb->query($sql);
	return $content.mysql_error().'blub';
	//echo 'Gel&ouml;schte Reihen'.mysql_affected_rows();
}

function eintragenName($content) {
	global $wpdb, $table_prefix, $current_user;
	$zeile=$_POST;
	$sql='SELECT ID FROM '.$wpdb->users.' WHERE user_login=\''.$zeile["NameMitarbeiter"].'\'';
	//echo $sql;
	$idfromDB=$wpdb->get_row($sql, ARRAY_A);
	//print_r($idfromDB);
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
			ORDER BY AufstellungsID, IDZeitfenster, IDMitarbeiter
			LIMIT 1'; //Aktualisiert vorhandene Zeilen der Datenbank
		$output=$wpdb->query($sql);
		//print_r($output);
		//echo $sql;
		echo mysql_error();
		if(mysql_affected_rows()==0) {
		echo 'Fehler: Entweder ist im Zeitfenster kein Platz mehr frei, oder das Zeitfenster existiert nicht, oder die Veranstaltung gibt es nicht. Es wurde kein Name vergeben (eintragenName)';
		}
		//return ThekendienstTabellenSchalter($content);
	}
	else {
		echo 'Fehler: Entweder wurde kein Name eingegeben oder f�r den Namen konnte keine ID ermittelt werden. (eintragenName)';
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
	//print_r($output);
	echo mysql_error();
	if(mysql_affected_rows()==0) {
		echo 'Fehler: Der Benutzer, die Veranstaltung oder das Zeitfenster konnte nicht gefunden werden. Evtl. wurde es zwischenzeitlich gel�scht. (Funktion: austragenName() )';
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
			<td colspan="3">Veranstaltung</td>
		</tr>';
}

function Tabellenende() {
	return '</tbody></table>';
}

function check_date($date,$format,$sep){ //�berpr�ft einen Datums-String auf das richtige Format   
    $pos1    = strpos($format, 'd');
    $pos2    = strpos($format, 'm');
    $pos3    = strpos($format, 'Y'); 
    $check    = explode($sep,$date);
    return checkdate($check[$pos2],$check[$pos1],$check[$pos3]);
}

function check_time($time) {//�berpr�ft einen Time-String auf Korrektheit)
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

function stylesheetsnachladen(){ //L�dt die Stylesheets
	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL."/thekendienst/thekendienststyles.css' type='text/css' media='all' />";
}

function Javascriptladen() {//L�dt die JavaScript-Funktionen
	//wp_enqueue_script('jquery');
	wp_enqueue_script('thekendienstscript', WP_PLUGIN_URL.'/thekendienst/thekendienstscript.js');
}




?>