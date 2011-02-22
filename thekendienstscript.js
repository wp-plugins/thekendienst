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
}

function einklappen(name, id, gleichzeitigzuklappen) {
	var nameid=name+id;
	if(gleichzeitigzuklappen==true) {
		ein_ausklappen(name, id + '_ausloeser',false);
	}
	document.getElementById(nameid).style.display='none';
}