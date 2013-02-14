function ein_ausklappen(name, id, gleichzeitigzuklappen) {
	var nameid=name+id;
	if(gleichzeitigzuklappen==true) {
		ein_ausklappen(name, id + '_ausloeser',false);
	}
	if(document.getElementById(nameid).style.display=='none') {
		document.getElementById(nameid).style.display='';
	} 
	else {
	document.getElementById(nameid).style.display='none';
	}
	return
}

function veranstaltung_ein_ausklappen(name, id, gleichzeitigzuklappen) {
	var nameid=name+id;
	//ein_ausklappen(name, id, gleichzeitigzuklappen);
	var button=document.getElementById(nameid)
	button.submit();
}

function einklappen(name, id, gleichzeitigzuklappen) {
	var nameid=name+id;
	if(gleichzeitigzuklappen==true) {
		ein_ausklappen(name, id + '_ausloeser',false);
	}
	document.getElementById(nameid).style.display='none';
}

function bearbeitenformularaufmachen(name, id, id_2) {
	var id_voll=id+"_"+id_2;
	var nameid=name+id_voll;
	var formular=document.getElementById('FormularZeitfenster_'+id);
	if(formular==null) {
		formular=document.getElementById(nameid);
		}
	else {
		var id_str=formular.firstElementChild.firstElementChild.firstElementChild.id;
		id_str=id_str.substring(0,id_str.length-3)+id_voll;
		formular.firstElementChild.firstElementChild.firstElementChild.id=id_str;
	}
	var ersaetzendes=document.getElementById(nameid);
	formular.parentNode.insertBefore(formular, ersaetzendes);
	var datum=ersaetzendes.children[1].innerHTML.substr(5,10);
	formular.firstElementChild.firstElementChild[0].value=datum;
	formular.firstElementChild.firstElementChild[1].value=ersaetzendes.children[2].innerText; //Startzeit
	formular.firstElementChild.firstElementChild[2].value=ersaetzendes.children[3].innerText; //Endzeit
	formular.firstElementChild.firstElementChild[3].value=ersaetzendes.children[4].innerText; //Anzahl der Mitarbeiter
	formular.firstElementChild.firstElementChild[4].value=ersaetzendes.children[5].innerText.replace("(bearbeiten)", "").replace("(loeschen)", ""); //Kommentar
	formular.firstElementChild.firstElementChild[5].value=id; //Veranstaltungsid
	formular.firstElementChild.firstElementChild[7].value=id_2; //Zeitfensterid
	formular.firstElementChild.firstElementChild[8].name="zeitfensteraendern"; //trigger beim übergeben in die $_POST-Variable
	document.getElementById('ueberschriftformular').innerHTML="<strong>Zeitfenster &auml;ndern</strong>";
	ersaetzendes.style.background = 'white';
	ein_ausklappen('bearbeitenzurueck','', false);
}