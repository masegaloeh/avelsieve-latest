function checkOther(id){
	for(var i=0;i<document.addrule.length;i++){
		if(document.addrule.elements[i].value == id){
			document.addrule.elements[i].checked = true;
		}
	}
}
function el(id) {
  if (document.getElementById) {
    return document.getElementById(id);
  }
  return false;
}

function ShowDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "";
  }
  return false;
}
function HideDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "none";
  }
}
function ToggleShowDiv(divname) {
  if(el(divname)) {
    if(el(divname).style.display == "none") {
      el(divname).style.display = "";
	} else {
      el(divname).style.display = "none";
	}
  }	
}
function ToggleShowDivWithImg(divname) {
  if(el(divname)) {
    img_name = divname + '_img';
    if(el(divname).style.display == "none") {
      el(divname).style.display = "";
	  if(document[img_name]) {
	  	document[img_name].src = "images/opentriangle.gif";
	  }	
	  if(el('divstate_' + divname )) {
	  	el('divstate_'+divname).value = 1;
	  }
	} else {
      el(divname).style.display = "none";
	  if(document[img_name]) {
	  	document[img_name].src = "images/triangle.gif";
	  }	
	  if(el('divstate_'+divname)) {
	  	el('divstate_'+divname).value = 0;
	  }
	}
  }	
}
function radioCheck(me,group) {
    var checked = me.checked; 
    if (checked) for (var i = 1; i < arguments.length; i++) { 
        var ck = document.getElementById(arguments[i]); 
        if (ck) ck.checked = false; 
    } else {
        return;
    }
    //me.checked = checked; // checkbox action 
    me.checked = true; // radiobox action 
}
