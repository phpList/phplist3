//var waitImg1 = 'busy/busy-phplist_alpha50-3.gif';
//var waitImg2 = 'busy/busy-phplist_black.gif';
//var waitImg3 = 'busy/busy-phplist_mix.gif';

$(document).ready(function () {
    var waitimg = new Image();
    waitimg.src = waitImage;
    $("#phplistsubscribeform").submit(function () {
        var emailaddress = $("#emailaddress").val();
        var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        var subscribeaddress = this.action;
        var ajaxaddress = subscribeaddress.replace(/subscribe/, 'asubscribe');
        $('#phplistsubscriberesult').html('<img src="' + waitimg.src + '" width="' + waitimg.width + '" height="' + waitimg.height + '" border="0" alt="Please wait" title="powered by phpList, www.phplist.com" />');

        if (emailReg.test(emailaddress)) {
            var jqxhr = $.ajax({
                type: 'POST',
                url: ajaxaddress,
                crossDomain: true,
                data: "email=" + emailaddress,
                success: function (data, textStatus, jqXHR) {
                    if (data.search(/FAIL/) >= 0) {
                        document.location = subscribeaddress + "&email=" + emailaddress;
                    } else {
                        $('#phplistsubscriberesult').html("<div id='subscribemessage'></div>");
                        $('#subscribemessage').html(data)
                            .hide()
                            .fadeIn(1500);
                        $("#phplistsubscribeform").hide();
                        document.cookie = "phplistsubscribed=yes";
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    document.location = subscribeaddress + "&email=" + emailaddress;
                }
            });
        } else {
            document.location = subscribeaddress + "&email=" + emailaddress;
        }
        return false;
    });

    $("#emailaddress").val(pleaseEnter);
    $("#emailaddress").focus(function () {
        var v = $("#emailaddress").val();
        if (v == pleaseEnter) {
            $("#emailaddress").val("")
        }
    });
    $("#emailaddress").blur(function () {
        var v = $("#emailaddress").val();
        if (v == "") {
            $("#emailaddress").val(pleaseEnter)
        }
    });
    var cookie = document.cookie;
    if (cookie.indexOf('phplistsubscribed=yes') >= 0) {
        $("#phplistsubscribeform").html(thanksForSubscribing);
    }

});

// cross domain fix for IE
// http://forum.jquery.com/topic/cross-domain-ajax-and-ie
$.ajaxTransport("+*", function (options, originalOptions, jqXHR) {
    if (jQuery.browser.msie && window.XDomainRequest) {
        var xdr;
        return {
            send: function (headers, completeCallback) {
                // Use Microsoft XDR
                xdr = new XDomainRequest();
                // would be nicer to keep it post
                xdr.open("get", options.url + "&" + options.data);
                xdr.onload = function () {
                    if (this.contentType.match(/\/xml/)) {
                        var dom = new ActiveXObject("Microsoft.XMLDOM");
                        dom.async = false;
                        dom.loadXML(this.responseText);
                        completeCallback(200, "success", [dom]);
                    } else {
                        completeCallback(200, "success", [this.responseText]);
                    }
                };
                xdr.ontimeout = function () {
                    completeCallback(408, "error", ["The request timed out."]);
                };

                xdr.onerror = function () {
                    completeCallback(404, "error", ["The requested resource could not be found."]);
                };
                xdr.send();
            },
            abort: function () {
                if (xdr)xdr.abort();
            }
        };
    }
});

if (pleaseEnter == undefined) {
    var pleaseEnter = "Please enter your email";
}
if (thanksForSubscribing == undefined) {
    var thanksForSubscribing = '<div class="subscribed">Thanks for subscribing. Please click the link in the confirmation email you will receive.</div>';
}
if (waitImage == undefined) {
    var waitImage = 'https://s3.amazonaws.com/phplist/img/busy.gif';
}  

