<?php
header('Content-type: application/txt');
header('Content-Disposition: attachment; filename="thekendienst.ics"');
$start = $_GET['start'];
$end= $_GET['end'];
$title = $_GET['title'];
$description=$_GET['comment'];

echo 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:'.$_GET['blogname'].'
METHOD:PUBLISH
BEGIN:VEVENT
UID:'.$title.$date.$start.$description.time().'@Thekendienst-Plugin
ORGANIZER;CN="thekendienstplugin":MAILTO:
SUMMARY:Thekendienst - '.$title.'
DESCRIPTION:Thekendienst bei der Veranstaltung "'.$title.'"\n am '.date("d.m.Y",$_GET['day']).' um '.$_GET['start'].'  eingetragen. Kommentar zur Veranstaltung: '.$_GET['comment'].'
CLASS:PUBLIC
DTSTART;TZID="(GMT+0200)":'.date("Ymd",$_GET['day']).'T'.str_replace(":","",$_GET['start']).'
DTEND;TZID="(GMT+0200)":'.date("Ymd",$_GET['day']).'T'.str_replace(":","",$_GET['end']).'
DTSTAMP:'.date("Ymd",time()).'T'.date("His",time()).'Z
URL:'.$_GET['url'].'
END:VEVENT
END:VCALENDAR';
//Timezone-Stuff have to be adjusted. This solution ist quick and dirty???
?>