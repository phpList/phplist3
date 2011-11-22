// Timer Bar - Version 1.0
// Author: Brian Gosselin of http://scriptasylum.com
// Script featured on http://www.dynamicdrive.com

var loadedcolor='darkgray' ;       // PROGRESS BAR COLOR
var unloadedcolor='lightgrey';     // COLOR OF UNLOADED AREA
var bordercolor='navy';            // COLOR OF THE BORDER
var barheight=25;                  // HEIGHT OF PROGRESS BAR IN PIXELS
var barwidth=450;                  // WIDTH OF THE BAR IN PIXELS
var waitTime=25;                   // NUMBER OF SECONDS FOR PROGRESSBAR

// THE FUNCTION BELOW CONTAINS THE ACTION(S) TAKEN ONCE BAR REACHES 100%.
// IF NO ACTION IS DESIRED, TAKE EVERYTHING OUT FROM BETWEEN THE CURLY BRACES ({})
// BUT LEAVE THE FUNCTION NAME AND CURLY BRACES IN PLACE.
// PRESENTLY, IT IS SET TO DO NOTHING, BUT CAN BE CHANGED EASILY.
// TO CAUSE A REDIRECT TO ANOTHER PAGE, INSERT THE FOLLOWING LINE:
// window.location="http://redirect_page.html";
// JUST CHANGE THE ACTUAL URL OF COURSE :)

var action=function()
{
//alert("Welcome to Dynamic Drive!");
//window.location="http://www.dynamicdrive.com
}

//*****************************************************//
//**********  DO NOT EDIT BEYOND THIS POINT  **********//
//*****************************************************//

var ns4=(document.layers)?true:false;
var ie4=(document.all)?true:false;
var blocksize=(barwidth-2)/waitTime/10;
var loaded=0;
var PBouter;
var PBdone;
var PBbckgnd;
var Pid=0;
var txt='';
var dir = 1;
if(ns4){
txt+='<table border=0 cellpadding=0 cellspacing=0><tr><td>';
txt+='<ilayer name="PBouter" visibility="hide" height="'+barheight+'" width="'+barwidth+'" onmouseup="hidebar()">';
txt+='<layer width="'+barwidth+'" height="'+barheight+'" bgcolor="'+bordercolor+'" top="0" left="0"></layer>';
txt+='<layer width="'+(barwidth-2)+'" height="'+(barheight-2)+'" bgcolor="'+unloadedcolor+'" top="1" left="1"></layer>';
txt+='<layer name="PBdone" width="'+(barwidth-2)+'" height="'+(barheight-2)+'" bgcolor="'+loadedcolor+'" top="1" left="1"></layer>';
txt+='</ilayer>';
txt+='</td></tr></table>';
}else{
txt+='<div id="PBouter" style="position:relative; visibility:hidden; background-color:'+bordercolor+'; width:'+barwidth+'px; height:'+barheight+'px;">';
txt+='<div style="position:absolute; top:1px; left:1px; width:'+(barwidth-2)+'px; height:'+(barheight-2)+'px; background-color:'+unloadedcolor+'; font-size:12px; color:black; text-align:center;">Processing please wait. Do not interrupt!</div>';
txt+='<div id="PBdone" style="position:absolute; top:1px; left:1px; width:0px; height:'+(barheight-2)+'px; background-color:'+loadedcolor+'; font-size:12px; color:white"></div>';
txt+='</div>';
}

//document.write(txt);
var progressmeter = txt;

function incrCount(){
window.status="Loading...";
loaded+=dir;
if(loaded<0) {
  loaded=0;
  dir = -dir;
}
if(loaded>=waitTime*10){
//  loaded=0;
  dir = -dir;
  //clearInterval(Pid);
  //loaded=waitTime*10;
  //setTimeout('hidebar()',100);
}
resizeEl(PBdone, 0, blocksize*loaded, barheight-2, 0);
}

function hidebar(){
clearInterval(Pid);
window.status='';
if(ns4)PBouter.visibility="hide";
else PBouter.style.visibility="hidden";
//action();
}

//THIS FUNCTION BY MIKE HALL OF BRAINJAR.COM
function findlayer(name,doc){
var i,layer;
for(i=0;i<doc.layers.length;i++){
layer=doc.layers[i];
if(layer.name==name)return layer;
if(layer.document.layers.length>0)
if((layer=findlayer(name,layer.document))!=null)
return layer;
}
return null;
}

function start(){
PBouter=(ns4)?findlayer('PBouter',document):(ie4)?document.all['PBouter']:document.getElementById('PBouter');
PBdone=(ns4)?PBouter.document.layers['PBdone']:(ie4)?document.all['PBdone']:document.getElementById('PBdone');
resizeEl(PBdone,0,0,barheight-2,0);
if(ns4)PBouter.visibility="show";
else PBouter.style.visibility="visible";
Pid=setInterval('incrCount()',95);
}

function resizeEl(id,t,r,b,l){
if(ns4){
id.clip.left=l;
id.clip.top=t;
id.clip.right=r;
id.clip.bottom=b;
}else id.style.width=r+'px';
}

function stop() {
  stopped = true;
}

function finish() {
  hidebar();
}

