
$(document).ready(function() {

  $("#phplistsubscribeform").submit(function() {
    var emailaddress = $("#emailaddress").val();
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    var subscribeaddress = this.action;
    ajaxaddress = subscribeaddress.replace(/subscribe/,'asubscribe');
          
    if(emailReg.test(emailaddress)) {
      var jqxhr = $.ajax({
        type: 'POST',
        url: ajaxaddress,
        crossDomain: true,
        data: "email="+emailaddress,
        success: function(data, textStatus, jqXHR ) {
          if (data.search(/FAIL/) >= 0) {
            document.location = subscribeaddress+"&email="+emailaddress;
          } else {
            $('#phplistsubscriberesult').html("<div id='subscribemessage'></div>");
            $('#subscribemessage').html(data)
            .hide()
            .fadeIn(1500);
            $("#phplistsubscribeform").hide();
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          document.location = subscribeaddress+"&email="+emailaddress;
        }
      });
    } else {
      document.location = subscribeaddress+"&email="+emailaddress;
    }
    return false;
  });

  $("#emailaddress").val(pleaseEnter);
  $("#emailaddress").focus(function() {
    var v = $("#emailaddress").val();
    if (v == pleaseEnter) {
      $("#emailaddress").val("")
    }
  });
  $("#emailaddress").blur(function() {
    var v = $("#emailaddress").val();
    if (v == "") {
      $("#emailaddress").val(pleaseEnter)
    }
  });
});

// cross domain fix for IE
// http://forum.jquery.com/topic/cross-domain-ajax-and-ie
$.ajaxTransport("+*", function( options, originalOptions, jqXHR ) {
  if(jQuery.browser.msie && window.XDomainRequest) {
    var xdr;
    return {
        send: function( headers, completeCallback ) {
            // Use Microsoft XDR
            xdr = new XDomainRequest();
            // would be nicer to keep it post
            xdr.open("get", options.url+"&"+options.data);
            xdr.onload = function() {
                if(this.contentType.match(/\/xml/)){
                    var dom = new ActiveXObject("Microsoft.XMLDOM");
                    dom.async = false;
                    dom.loadXML(this.responseText);
                    completeCallback(200, "success", [dom]);
                } else {
                    completeCallback(200, "success", [this.responseText]);
                }
            };
            xdr.ontimeout = function(){
                completeCallback(408, "error", ["The request timed out."]);
            };
           
            xdr.onerror = function(){
                completeCallback(404, "error", ["The requested resource could not be found."]);
            };
            xdr.send();
      },
      abort: function() {
          if(xdr)xdr.abort();
      }
    };
  }
});

if (pleaseEnter == undefined) {
  var pleaseEnter = "Please enter your email";
}


