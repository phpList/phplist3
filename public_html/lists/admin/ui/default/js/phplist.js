
var menuArrowsrc = 'ui/default/images/menuarrow.png';
var menuArrowActivesrc = 'ui/default/images/menuarrow_active.png';

/* JS function to handle media queries */
window.matchMedia = window.matchMedia || (function(doc, undefined){
  
  var bool,
      docElem = doc.documentElement,
      refNode = docElem.firstElementChild || docElem.firstChild,
      // fakeBody required for <FF4 when executed in <head>
      fakeBody = doc.createElement('body'),
      div = doc.createElement('div');
  
  div.id = 'mq-test-1';
  div.style.cssText = "position:absolute;top:-100em";
  fakeBody.appendChild(div);
  
  return function(q){
    
    div.innerHTML = '&shy;<style media="'+q+'"> #mq-test-1 { width: 42px; }</style>';
    
    docElem.insertBefore(fakeBody, refNode);
    bool = div.offsetWidth == 42;
    docElem.removeChild(fakeBody);
    
    return { matches: bool, media: q };
  };
  
})(document);


/* JS to execute on loading document */
$(document).ready(function() {
	// adding add and even classes to table in dbcheck page
	$(".dbcheck tr.row:even").addClass("even");
	$(".dbcheck tr.row:odd").addClass("odd");
	// make the content collapsible
	$('.dbcheck table div.header').each(function(index) {
		$(this).click(function () {
		$(this).next("div.content").toggle("slow");
		});
	});
	
    // open/close div global help    
        $("#globalhelp").click(function(){        
    	    if(matchMedia('only screen and (max-width: 767px)').matches){ $("#menuTop").hide(); }
     	    $("#globalhelp .content").toggle();
	});
	
/* sliding menu for mobile screen */
	 $(window).bind("load resize", function(){
	    if(matchMedia('only screen and (max-width: 767px)').matches){
		    $("span#menu-button").show();
		    $("#menuTop").hide();
		    $("span#menu-button").toggle(function() {
            	$("#menuTop").show();
            	$("#globalhelp .content").hide();
			},function(){
			    $("#menuTop").hide();
			});
	  }
	  else{
		  $("span#menu-button").hide();
		  $("#menuTop").show();
	  }
	});
	
/* sub menus on mobile */
	 $(window).bind("load resize", function(){
    var org=[];	
    $("#menuTop>ul>li>a").each(function(index) { 
		org.push($(this).attr("href"));
		});
	  if(matchMedia('only screen and (max-width: 767px)').matches){
		$("#menuTop>ul>li>a").each(function(index) { 
		if($(this).parent('li').children('ul').length!=0)
		$(this).attr("href","javascript:void(0);").addClass("collaps");
		else
		$(this).addClass("nocollaps");
		});
		$("#menuTop>ul>li>a.collaps").each(function(index) {
		$(this).toggle(function() {
			$(this).parent().children("ul").addClass("visible");
			
			}, function() {
			$(this).parent().children("ul").removeClass("visible"); 
			});
			});
	  }
	  else{
		  $("#menuTop>ul>li>a").each(function(index) { $(this).attr("href",org[index]).addClass("collaps");});
	  }
	});

    // dropdown menu 1
    $('#webblertabs').each(function(){
        $(this).find('ul li').hide();
        $(this).find('ul li.current').show();
    });
    $('#webblertabs .current a').click(function () {return false;});
    
    $('#webblertabs').hover(function(){
        $(this).find('ul li').slideDown();
    },
    function(){
        $(this).find('ul li').hide();
        $(this).find('ul li.current').show();
    });
    
    // dropdown menu 2
    $('.dropButton').hover(function(){
        $(this).find('.submenu').slideDown();
    },
    function(){
        $(this).find('.submenu').hide();
    });
/* Draggable behaviour for table sorting*/	
	// disable drag n drop for description rows
	$( ".disable-draggable tr" ).each(function( index ) {
		$(this).attr('id','row-'+index);
	});
	$("tr:first").addClass('nodrag');
	// Make a nice striped effect on the table
	table_2 = $(".disable-draggable");
	// Initialise the second table specifying a dragClass and an onDrop function that will display an alert
	var rowsH = $( ".rows" ).height();
	var actionsH = $( ".actions" ).height();
	table_2.tableDnD({
		onDragClass: "isDragging",
		onDrop: function(table, row) {
			var rows = table.tBodies[0].rows;
			
			var id = $(row).attr("data-row-position"); // current row
			// rebuid ids
			$( "tr.rows" ).each(function( index ) {
				var newIndex = index + 0;
				$(this).attr('data-row-position',newIndex);
				$(this).find('input').val(newIndex);
			});
			// repositionning descriptions
			$( "tr.actions" ).each(function( index ) {
				var descParent = "#"+$(this).attr('data-row-parent');
				$(this).insertAfter($(descParent));
			});
			// Stuff after drag
			$(row).addClass("dragged"); // add class
			$("[name$='update']").html("<span class='save'></span>"+$("[name$='update']").text()); // expand button's content
			$( ".rows" ).height(rowsH); // reset row height
			$( "tr.actions" ).show(); // show action rows
			// remove created header
			$('.tmptable').remove();
			// show original header
			$( "th" ).parent('tr').show();
		},
		onDragStart: function(table, row) {
			// building header content
			var firstLine = $( "tr:first" );
			var html = '<table class="tmptable" style="padding:0;margin:0;opacity:0.7"><tr>';
			html += firstLine.html();
			html += '</tr></table>';
			// hide original header
			firstLine.hide();
			// insert created header
			$(table).before(html);
			// save rows position then hide
			$( "tr.actions" ).each(function( index ) {
				var rowBefore = $(this).prev().attr('id');
				$(this).attr('data-row-parent',rowBefore);
			}).hide(); // hide actions rows
			// change row height
			$( ".rows" ).height(rowsH + actionsH);
		}
		
	});
	// fix if click with no drag
	table_2.mouseup(function(){
	  $( ".nodrag:hidden" ).show();
	  $( ".rows" ).height(rowsH);
	  $('.tmptable').remove();
	});	

        // styling list tab in send page
	$('body.send').find('.ui-tabs-panel li').each(function(){
	  var li = $(this);
	  listify(li);
	});
    listify_finish_tab('.campaignTracking');
    listify_finish_tab('.resetStatistics');
    listify_finish_tab('.isTestCampaign');

    function listify_finish_tab(selector)
    {
        var cbx = $(selector).find('input[type=checkbox]');
        var cbx_name = $(cbx).attr('name');
        var label = $(selector).find('label');
        $(cbx).attr('id', cbx_name);
        $(label).attr('for', cbx_name);
    }

    function listify(selector) 
    {
        $(selector).each(function(index, val) {
            // Give all checkboxes the same ID as the name attribute
            var cbx_name = $(this).find('input[type=checkbox]').attr('name');
            $(this).find('input[type=checkbox]').attr('id', cbx_name);

            // Wrap the contents of the <li> with a <label>
            var content = $(this).html().replace('(<span', '<span');
            content = content.replace('span>)','span><small>');
            content = content + "</small>";
            $(this).html('<label for="' + cbx_name + '">' + content + '</label>');

            // Pop the checkbox out of the label (for CSS selecting reasons)
            var cbx = $(this).find('input[type=checkbox]');
            $(this).prepend(cbx);
        });
        $('li input[type=checkbox]').hide();


    }



});
