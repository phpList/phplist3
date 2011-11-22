
var busyImage = '<img src="ui/orange/styles/images/ui-anim_basic_16x16.gif" with="16" height="16" border="0">';

function urlParameter( name, link) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( link );
  if( results == null )
    return "";
  else
    return results[1];
}

function messageStatusUpdate(msgid) {
   $('#messagestatus'+msgid).load('./?page=pageaction&ajaxed=true&action=msgstatus&id='+msgid,"",function() {
   });
   setTimeout("messageStatusUpdate("+msgid+")",5000);
}

function getServerTime() {
   $('#servertime').load('./?page=pageaction&ajaxed=true&action=getservertime',"",function() {
   });
   setTimeout("getServerTime()",60100); // just over a minute
}

function refreshCriteriaList() {
  var id = urlParameter('id',document.location);
  $("#existingCriteria").html(busyImage);
  $("#existingCriteria").load('./?page=pageaction&ajaxed=true&action=listcriteria&id='+id);
}

function openDialog(url) {
  $("#dialog").dialog({
    minHeight: 400,
    width: 600,
    modal: true,
    show: 'blind',
    hide: 'explode'
  });
  var destpage = urlParameter('page',url);
  url = url.replace(/page=/,'origpage=');
  $("#dialog").load(url+'&ajaxed=true&page=pageaction&action='+destpage);
}

function totalSentUpdate(msgid) {
   $('#totalsent'+msgid).load('./?page=pageaction&ajaxed=true&action=msgsent&id='+msgid,"",function() {
   });
   setTimeout("totalSentUpdate("+msgid+")",5000);
}

$(document).ready(function(){

$(".paging").scrollable();

$(".configurelink").click(function() {
 // alert(this.href);
  $("#configurecontent").load('./?page=ajaxcall&action=test');
  $("#configurecontent").show();
  return false;
})

$("a.ajaxable").click(function() {
  var url = this.href;
  var thispage = urlParameter('page',window.location.href);
  var action = urlParameter('action',url);
  if (action == "") {
    url += '&action='+thispage;
  }
  parent = $(this).parent();
  parent.html(busyImage);
  url = url.replace(/page=/,'origpage=');
//  alert(url+'&ajaxed=true&page=pageaction');
  parent.load(url+'&ajaxed=true&page=pageaction');
  return false;
})

$("input:checkbox.checkallcheckboxes").click(function() {
  if (this.checked) {
    $("input[type=checkbox]:not(:checked)").each(function(){
      this.checked = true;
    });
  } else {
    $("input[type=checkbox]:checked").each(function(){
      this.checked = false;
    });
  }
})

  var stop = false;

$(".accordion").accordion({
    autoHeight: false,
    navigation: true,
    collapsible: true
  })

$(".opendialog").click(function() {
  openDialog(this.href);
  return false;
});
$(".helpdialog").click(function() {
  openDialog(this.href);
  return false;
});
$(".closedialog").click(function() {
  $("#dialog").dialog('close');
});
						   
//dropbuttons						   
$("div.dropButton img.arrow").click(function(){ 					
		submenu = $(this).parent().parent().find("div.submenu");		
		if(submenu.css('display')=="block"){
			submenu.hide(); 		
			$(this).attr('src','ui/orange/images/menuarrow.png');									
		} else {
			submenu.fadeIn(); 		
			$(this).attr('src','ui/orange/images/menuarrow_active.png');	
		}	
		return false;					
})						   


/* hmm, doesn't work yet, but would be nice at some point
$("#emailsearch").autocomplete({
  source: "?page=pageaction&ajaxed=true&action=searchemail",
  minLength: 2,
  select: function(event, ui) {
  log(ui.item ? ("Selected: " + ui.item.value + " aka " + ui.item.id) : "Nothing selected, input was " + this.value);
  }
});
*/

  $("#listinvalid").load("./?page=pageaction&action=listinvalid&ajaxed=true",function() {
 //  alert("Loaded")
   });

  $(".tabbed").tabs({
    ajaxOptions: {
      error: function(xhr, status, index, anchor) {
        $(anchor.hash).html("Error fetching page");
      }
    }
  });
  $(".tabbed1").tabs();

  $("#remoteurlinput").blur(function() {
    if (!this.value) return;
    $("#remoteurlstatus").html(busyImage);
    $("#remoteurlstatus").load("./?page=pageaction&action=checkurl&ajaxed=true&url="+this.value);
  })

  $("input:radio[name=sendmethod]").change(function() {
    if (this.value == "remoteurl") {
      $("#remoteurl").show();
      $("#messagecontent").hide();
    } else {
      $("#remoteurl").hide();
      $("#messagecontent").show();
    }
  })
  
  $("a.savechanges").click(function() {
    if (changed) {
      document.sendmessageform.followupto.value = this.href;
      document.sendmessageform.submit();
      return false;
    }
  });

  $("#criteriaSelect").change(function() {
    var val = $("#criteriaSelect").val();
    var operator = '';
    switch (aT[val]) {
      case 'checkbox':
        $("#criteriaAttributeOperator").html('<input type="hidden" name="criteria_operator" value="is" />');
        $("#criteriaAttributeValues").html('CHECKED <input type="radio" name="criteria_values" value="checked" /> UNCHECKED <input type="radio" name="criteria_values" value="unchecked" />');
        break;
      case 'checkboxgroup':
      case 'select':
      case 'radio':
        $("#criteriaAttributeOperator").html('IS <input type="radio" name="criteria_operator" value="is" checked="checked" /> IS NOT <input type="radio" name="criteria_operator" value="isnot" />');
        $("#criteriaAttributeValues").html(busyImage);
        $("#criteriaAttributeValues").load("./?page=pageaction&ajaxed=true&action=attributevalues&name=criteria_values&type=multiselect&attid="+val);
        break;
      case 'date':
        $("#criteriaAttributeOperator").html('IS <input type="radio" name="criteria_operator" value="is" checked="checked" /> IS NOT <input type="radio" name="criteria_operator" value="isnot" /> IS BEFORE <input type="radio" name="criteria_operator" value="isbefore" /> IS AFTER <input type="radio" name="criteria_operator" value="isafter" />');
        $("#criteriaAttributeValues").html('<input type="text" id="datepicker" name="criteria_values" size="30"/>');
        $("#datepicker").datepicker({dateFormat: 'yy-mm-dd' });
        break;
      default:
        $("#criteriaAttributeOperator").html('');
        $("#criteriaAttributeValues").html('');
        break;
    }
      
//    alert(val + " "+aT[val]);
  })

  $("#initialadminpassword").keyup(function() {
    if (this.value.length > 8) {
      $("#initialisecontinue").removeAttr('disabled');
    }
  });

  $("#refreshCriteria").click(refreshCriteriaList);
  
  $("#addcriterionbutton").click(function() {
    $("#addcriterionbutton").addClass('disabled');
    var request = document.location.search+'&'+$("#sendmessageform").serialize();
    var attr = urlParameter('criteria_attribute',request);
    if (attr == '') {
      alert('Select an attribute to add');
      return false;
    }
    var vals = urlParameter('criteria_values',request);
    var arrVals = urlParameter('criteria_values[]',request);
    if (vals == '' && arrVals) {
      alert('Select a value to add');
      return false;
    }
    
    request = request.replace(/\?/,'');
    request = request.replace(/page=/,'origpage=');
    request = './?page=pageaction&action=storemessage&'+request;
    alert(request);
    $("#existingCriteria").html(busyImage);
 //   $("#hiddendiv").load(request);
 //   $("#sendmessageform").submit();
 //   setTimeout("refreshCriteriaList()",5000);

//    refreshCriteriaList();
    return true;
  });

  var chopOff = document.title.lastIndexOf(":") + 2;
  var doctitle = document.title.substring(chopOff);
  var docurl = document.location.search;
  document.cookie="browsetrail="+escape(docurl+"SEP"+doctitle);

})
