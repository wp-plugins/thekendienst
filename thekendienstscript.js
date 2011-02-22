function ein_ausklappen(name, id) {
	var nameid=name+id;
	if(document.getElementById(nameid).style.display=='none') {
		document.getElementById(nameid).style.display='';
	} 
	else {
	document.getElementById(nameid).style.display='none';
	}
}