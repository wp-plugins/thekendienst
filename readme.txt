=== Plugin Name ===
Contributors: bas_der_gruene
Donate link: 
Tags: Thekendienst, shift schedule, work plan, work schedule, Schichtdienst, Schichtplan
Requires at least: 2.8
Tested up to: 3.4.1
Stable tag: 0.3.1

This Plugin helps to create and organize shift schedules. You can create events by time frames and fill those with persons on duty.
== Description ==

(english)
This Plugin helps to create and organize shift schedules. Within this plugin you can create events, seperated by time frames and fill those time frames with a predefined number of persons on duty. (english isn't easy for me, sorry)

This plugin should only be used by skilled users because its a work in progress-plugin. And sometimes progress is even stalled for some time.

This plugin isn't ready for international use. This Plugin is the first php code I wrote by myself. At the time all code is commented in german and also all functions and variables are named that way. I intend to translate all of it sometime and enable gettext capabilities but it takes time, which I don't have right now. So you have to understand german to understand the documentary of this plugin below or just figure out how this plugin works by yourself. Sorry for this. I wrote all this german stuff down below in half the time It took my to produce this crippled paragraph in english.

Basic stuff: Configure Thekendienst on the backend of Wordpress as an admin: Preferences/Properties? -> Thekendienst. Show the schedule of an Event by using  &#91;Thekendienst=1&#93; in the content-section of a post or page. All Stuff the Thekendienst-Plugin is doing is only be done in a seperated table called wp_thekendienst (or equal). So remove this Table when you dont want to use Thekendienst anymore. Additionally there is an option-entry set in wp_options (or equal) you could delete if you like to get rid of all this plugin entirely: Search for  option_name=thekendienst_db_version in your sql database.

This Plugin requires enabled JavaScript. And I think it do not work on Internet Explorer, but I don't have Windows so I can't try.

(German)
Dieses Plugin dient der einfachen Einteilung und Veröffentlichung von “Thekendiensten” bei verschiedenen Veranstaltungen. Unterschiedliche Veranstaltungen werden in Zeiträume unterteilt und können mit Mitarbeitern gefüllt werden.

Ich bin kein Programmierer und dieses plugin ist das erste php-Script das ich produktiv einsetze und damit auch veröffentliche. Profis werden über den Code lachen.

Die Erstellung und Bearbeitung der Veranstaltungen erfolgt z. Zt. nur durch die Administratoren. Das Eintragen der Mitarbeiter erfolgt jedoch durch jeden (angemeldeten) Benutzer des Blogs. 

Daraus ergibt sich schon: Sicherheitsbedenken haben auf die Entwicklung dieses Scripts keinerlei Einfluss gehabt. Ich verwende es auch ausschließlich in einem "Members only"-Blog in dem jeder angemeldete (Anmeldung nur durch Admin) schreiben, lesen und (in Grenzen) administrieren kann. Externe Kommentare (und damit weitere Accounts über die der Editoren hinaus) sind nicht vorgesehen. Würden sie das, wäre das Aufklappmenü beim Eintragen in die dienste ungleich länger (vgl. <a href="http://www.derdateienhafen.de/thekendienstplugin">DEMO</a>). Ich kann mir aber vorstellen dass dieses Plugin auch in einem öffentlichen Blog Anwendung finden kann, wenn man es lediglich auf einer passwortgeschützten Seite (oder Beitrag) einsetzt. Ich habe das nicht ausprobiert.

Ich garantiere kein bisschen support. Jegliche Verwendung erfolgt auf eigene Gefahr. Wer sich daran wagt sollte es erst in eimem Testsystem ausprobieren und dazu in der lage sein, php/mysql zu debuggen. Wer des deutschen mächtig ist, kann durch die klare Benennung der Funktionen und Variablen recht leicht in den Code finden. Wirklich schwirig ist das alles nicht, höchstens chaotisch.

JavaScript wird benötigt, und im Internet Explorer ist das Plugin vermutlich nicht zu administrieren. Ich habe hier kein Windows, kann das also nicht testen.

Zu den eigentlichen Funktionen:

Das Plugin stellt folgende Funktionen zur Verfügung:
Im backend unter Einstellungen -> Thekendienst werden die Veranstaltungen und Zeitfenster angelegt.
<strong>Veranstaltungen<strong> beinhalten einen Titel und eine automatisch vergebene ID. Veranstaltungen können gelöscht und ausgeblendet (temporär und dauerhaft) werden.
<strong>Zeitfenster<strong> beinhalten die Informationen des Tages, Start- und Endzeit, die Anzahl der Personen die sich eintragen können und ein Kommentarfeld. zeitfenster können gelöscht und bearbeitet werden.
In jedem Zeitfenster können sich soviele <strong>Mitarbeiter<strong> eintragen wie das vorher definiert wurde. Beim Druck auf den Knopf "eintragen" zeigt sich ein Aufklappmenü mit allen Accounts des Worpress-Systems, außerdem der Eintrag "-Andere-" über den externe ebenfalls eingetragen werden können. Austragen funktioniert ebenso einfach. Die Liste der Mitarbeiter kann auch temporär ausgeblendet werden, ist aber in der Regel niemals nötig)

Durch die Zeichenfolge &#91;Thekendienst=1&#93; im content wird die Veranstaltung mit der ID 1 aufgerufen. Gibt es eine Veranstaltung mit dem Titel "Welteroberung" wird diese mit &#91;Thekendienst=Welteroberung&#93; angezeigt.


== Installation ==

(german)
Die Installation erfolgt wie üblich bei allen Plugins. Eine eigene Tabelle in der Datenbank sollte bei der aktivierung des plugins automatisch angelegt werden.
d.h.: Die zip-Datei herunterladen und entpacken. Den hoffentlich entstehenden Ordner thekendienst mit allen Inhalten in den Ordner wp-content/plugins laden und im Wordpress-Backend unter Plugins aktivieren.

Deinstalliert werden kann das ganze in dem der Ordner thekendienst gelöscht wird. Außerdem die Tabelle wp_thekendienst (oder eigenes prefix). Um alle spuren restlos zu beseitigen sollte in der tabelle wp_options (oder eigenes prefix) der Eintrag thekendienst_db_version gelöscht werden.

== Frequently Asked Questions ==

(english)
= Where can I get support and ask questions? =

Normally I can not guarantee any support, but you can try anyway: Post a comment on the <a href="http://www.derdateienhafen.de/thekendienstplugin">plugin homepage</a> and I see what I can do.

(german)
= Wo erhalte ich support und kann fragen stellen? =

In der Regel kann ich keinen support leisten, einen Versuch kann aber jeder interessierte dennoch wagen: Einfach unter <a href="http://www.derdateienhafen.de/thekendienstplugin">pluginhomepage</a> einen Kommentar hinterlassen. Ich schaue dann was ich tun kann.

== Screenshots ==

(not yet ready)

== Changelog ==

= development-version =
* there is a Problem with adding new shifts, haven't figured it out yet. Plugin is not working!

= 0.3.1 =
* changed the sort-order in backend for better maintenance of many events.
* fixed a bug with the rendering of deleted events

= 0.3.1beta =
* changed the sort-order of time frames

= 0.3beta =
* minor bugfix

= 0.3alpha =
* adding some internationalization stuff
* now it is possible to download ics-Files for the timeframes

(german)
= 0.2beta =
* Hier nun die nächste Version in der Admins nun Veranstaltungen dauerhaft(!) zuklappen können. Das ist zwar nicht besonders serverfreundlich programmiert (weil vgl. oft auf die Datenbank zugegriffen wird), funktioniert aber.
* Durch diese Funktion ist das plugin jetzt definitiv nicht mehr ohne javascript nutzbar. Es gäbe sicher eine Lösung dafür, aber dazu müsste ich vermutlich noch in die nervige (!) html-tabellenstruktur eingreifen. Da hab ich jetzt nicht den Nerv drauf.

= 0.1beta =
* Meine implementierte Löschfunktion war nicht besonders schlau gelöst. Hier ein neuer Ansatz der jeweils einen Eintrag in der Datenbank behält und so in alten Beiträgen nicht mehr eine Fehlermeldung anzeigt, sondern den Hinweis ausgibt, dass die entsprechende Veranstaltung gelöscht wurde. Das ist in der Demo zu sehen.
* Weiterhin habe ich einen Bug der aus einer alten Version herrührt gefixed. Neu angelegte Veranstaltungen können jetzt fehlerfrei bearbeitet werden. Bei alten gibt es aber kleinere Probleme: Beim Reduzieren der Mitarbeiter eines Zeitfensters werden bei alten Veranstaltungen die zuerste eingetragenen Mitarbeiter gelöscht (oder zumindest einige). Hab keine Lust das zu reparieren.

= 0.1alpha =
* in der neuen Version können ganze Veranstaltungen endlich automatisch gelöscht werden. Der Code ist noch nicht schön, aber soweit ich das bisher sehe korumpiert er zumindest die (plugineigene) Datenbank nicht mehr.

= 0.0.5b =
* Ich habe soeben wieder eine neue Version fertig gestellt. Diesmal sind bearbeitungsfunktionen hinzu gekommen. Außerdem habe ich ein paar Bugs gefixed.
* Nach wie vor ist dieses Plugin nur für Experimentierfreudige geeignet.

= 0.0.4 =
* Version 0.0.4 ist fertig. Kleines Bugfix das im Code Gänsefüßchen zu assoziativen Arrays hinzufügt (und so keine php-notice erscheint)
In 0.0.3 hatte ich den add_filter-hook falsch verwendet. Nun verwende ich den zumindest in Teilen richtig. funktioniert jetzt erstmal besser (insb. zusammen mit anderen Plugins die Shortcodes nutzen)

= 0.0.3 =
* Die Möglichkeit einem Zeitfenster um einen Kommentar zu ergänzen wurde hinzugefügt.
Dies kann hilfreichsein für “Aufbau” und “Abbau”, für “Weißes Hemd ist Pflicht” oder als Bezeichnung der Veranstaltung in einer Reihe von Veranstaltungen
* Wichtig ist: Beim Update einer früheren Version auf die Version 0.0.3 ist es notwendig die Datenbank komplett zu löschen. Hängt mit einem Fehler in den Vorversionen zusammen. Zukünftige Versionen sollten das Update-Problem nicht mehr haben.

= 0.0.2 =
* Diese Version erlaubt das Anlegen von Veranstaltungen und Zeitfenstern und die ein- und auswahl in Zeitfenster aller Benutzer des Wordpress-Systems und anderer (durch registrierte Benutzer eingetragen).

= 0.0.1 =
* Die erste Version dieses Plugins ermöglichte lediglich das erstellen von Veranstaltungen, das anlegen von Zeitfenstern und die Einwahl. Löschen und auswählen war noch nicht konzeptioniert/implementiert.


== Upgrade Notice ==

= 0.3.1beta =
* Everything seemed to be good for now. Update!

= 0.2beta =
Erste Version die es lohnt auszuprobieren. Wichtigste Funktionen sind endlich implementiert

== Arbitrary section ==
