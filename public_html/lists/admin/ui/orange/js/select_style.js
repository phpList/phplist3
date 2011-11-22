
// Files and directory structures
var cssDir = "styles/";
var stylePrefix = "";

var winNSCSS = "styles_win_ns.css";
var winIECSS = "styles_win_ie.css";
var macCSS = "styles_mac.css";
var unixCSS = "styles_unix.css";

// *************************************************************
//  CLIENT_SIDE SNIFFER CODE
// *************************************************************
// convert all characters to lowercase to simplify testing 
var agt=navigator.userAgent.toLowerCase(); 

// *** BROWSER VERSION *** 
// Note: On IE5, these return 4, so use is_ie5up to detect IE5. 
var is_major = parseInt(navigator.appVersion); 
var is_minor = parseFloat(navigator.appVersion); 

// *** BROWSER TYPE *** 
var is_nav  = ((agt.indexOf('mozilla')!=-1) && (agt.indexOf('spoofer')==-1) 
            && (agt.indexOf('compatible') == -1) && (agt.indexOf('opera')==-1) 
            && (agt.indexOf('webtv')==-1));
var is_nav4up = (is_nav && (is_major >= 4));  
var is_ie   = (agt.indexOf("msie") != -1); 
var is_ie3  = (is_ie && (is_major < 4)); 
var is_ie4  = (is_ie && (is_major == 4) && (agt.indexOf("msie 5.0")==-1) );
var is_ie4up  = (is_ie  && (is_major >= 4));
var is_ie5  = (is_ie && (is_major == 4) && (agt.indexOf("msie 5.0")!=-1) );  
var is_ie5up  = (is_ie  && !is_ie3 && !is_ie4);  

// *** PLATFORM ***
var is_win   = ( (agt.indexOf("win")!=-1) || (agt.indexOf("16bit")!=-1) );
var is_mac    = (agt.indexOf("mac")!=-1);
var is_sun   = (agt.indexOf("sunos")!=-1);
var is_irix  = (agt.indexOf("irix") !=-1);    // SGI
var is_hpux  = (agt.indexOf("hp-ux")!=-1);
var is_aix   = (agt.indexOf("aix") !=-1);      // IBM
var is_linux = (agt.indexOf("inux")!=-1);
var is_sco   = (agt.indexOf("sco")!=-1) || (agt.indexOf("unix_sv")!=-1);
var is_unixware = (agt.indexOf("unix_system_v")!=-1); 
var is_mpras    = (agt.indexOf("ncr")!=-1); 
var is_reliant  = (agt.indexOf("reliantunix")!=-1);
var is_dec   = ((agt.indexOf("dec")!=-1) || (agt.indexOf("osf1")!=-1) || 
       (agt.indexOf("dec_alpha")!=-1) || (agt.indexOf("alphaserver")!=-1) || 
       (agt.indexOf("ultrix")!=-1) || (agt.indexOf("alphastation")!=-1)); 
var is_sinix = (agt.indexOf("sinix")!=-1);
var is_freebsd = (agt.indexOf("freebsd")!=-1);
var is_bsd = (agt.indexOf("bsd")!=-1);
var is_unix  = ((agt.indexOf("x11")!=-1) || is_sun || is_irix || is_hpux || 
             is_sco ||is_unixware || is_mpras || is_reliant || 
             is_dec || is_sinix || is_aix || is_linux || is_bsd || is_freebsd);

			 
//  Select the appropriate stylesheet
if ((is_nav4up || is_ie4up) || (!is_nav && !is_ie)) {
  ChooseStyleSheet();
}

// Handle Navigator 4 window resizing
if (is_nav4up) {
   var initWidth, initHeight;
   initWidth = window.innerWidth;
   initHeight = window.innerHeight;
   window.captureEvents(Event.RESIZE);
   window.onresize = handleResize;
}

// *********************************************************************
//  UTILITY FUNCTIONS
// *********************************************************************
// Function to handle window resizing on Navigator 4
function handleResize() {
   if (((initWidth != window.innerWidth) || (initHeight != window.innerHeight)) && (typeof disableReload == "undefined")) {
     location.reload();
   }
   return false;
}

// Function to choose the style sheet for use based on the platform
// and browser version
function ChooseStyleSheet() {
  var fileHead = cssDir + stylePrefix;
  var styles;
  if (is_win) {
	if (is_nav) {
      styles = fileHead + winNSCSS;
	} else {
	  // Windows Netscape fonts need to be larger than those for IE
	  styles = fileHead + winIECSS;
	}
  } else if (is_mac) {
      if (is_ie5up) {
	    // Default font settings for Mac IE5 match PC IE fonts
        styles = fileHead + winIECSS;
	  } else {
	    styles = fileHead + macCSS;
	  }
  } else if (is_unix) {
    styles = fileHead + unixCSS;
  } else {
    // Default style = macCSS
	// Macintosh stylesheets have the largest font sizes, which
	// will ensure readability
		styles = fileHead + macCSS;
  }
  document.write("<link rel=\"stylesheet\" type=\"text/css\" href=\"" + styles + "\">");
  return true;
}

