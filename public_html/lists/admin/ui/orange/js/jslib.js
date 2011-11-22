
var helpwin = null;
var helploc = null;
function deleteRec(url) {
	if (confirm("Are you sure you want to delete this record?")) {
		document.location = url;
	}
}

function deleteRec2(msg,url) {
	if (confirm(msg))
		document.location = url;
}

function help(loc) {
	if (helpwin && !helpwin.closed) {
			helpwin.close();
			helpwin = '';
      helploc = loc;
			setTimeout("openhelp()",500)
	} else {
  	helploc = loc;
		openhelp();
  }
}

function openhelp() {
helpwin=window.open(helploc,"help",'screenX=100,screenY=100,width=350,height=350,scrollbars=yes');
  if (window.focus) {helpwin.focus()}
}
function print_self(){
  window.focus();
  if (typeof(window.print) != "undefined"){
    window.print();
  } else {
    show_print_alert();                         
  }
}

function show_print_alert() {
   alert("Please use your browser's print button");
}

function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}

var pic = null;
var t_url;
var t_w;
var t_h;
//window.onunload =  closePic;

function viewImage(url,w,h) {
   openpic(url,w,h);
}

function openpic(url,w,h) {
    if (w == null || w == 0) {
		w = 120;
    }
    if (h == null || h == 0){
	h = 120;
    }

    if (pic){
	pic.close();
	pic = null;
	t_url = url;
	t_w = w;
	t_h = h;
	setTimeout("openit()",500);
	} else {
	do_openpic(url,w,h);
	}
}

function openit() {
	do_openpic(t_url,t_w,t_h);
	t_url = null;
	t_w = null;
	t_h = null;
}

function do_openpic(url,w,h) {
    w +=30;
    h +=30;
    //var features = "scrollbars=auto,toolbar=yes,location=yes,menubar=yes,screenx=150,screeny=150";
    var	features = "dependent=yes,width=" + w + ",height=" + h + ",noresize,scrollbars=auto,toolbar=no,location=no,menubar=no,screenx=150,screeny=150";
//    alert(features);
    pic = window.open(url,"picwin",features);
    //    setTimeout("createContent()",100);

}
function createContent(){
    if (!pic.document){
	setTimeout("createContent()",100);
    } else {
	var url ="aaa";
	var w=100;
	var h=200;
	pic.document.write('<html><head><style>body{margin:0}</style></head><body>');
	pic.document.write('<img border=0 src=\"');
	pic.document.write(url);
	pic.document.write('\" width=');
	pic.document.write(w);
	pic.document.write(' height=');
	pic.document.write(h);
	pic.document.write('></body></html>');
	if (window.focus){pic.focus();}
    }
}

