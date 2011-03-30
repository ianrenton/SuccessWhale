var refreshIDs = new Array();

// Changes the content of one column, and re-sets its refresh.
function changeColumn(colnumber, url, updatedb) {
    if (url.indexOf("----------") == -1) {
        // Normal use
        $("#column" + colnumber).load((url + "&updatedb=" + updatedb), function() {
            $('.wraptext').breakly(20);
            clearInterval(refreshIDs[colnumber]);
            refreshIDs[colnumber] = setInterval(function() {
                $("#column" + colnumber).load(url);
            }, 300000);
        });
    } else {
        // "Other" selected in dropdown
        document.getElementById("customcolumnentry" + colnumber).disabled = false;
        document.getElementById("customcolumnentry" + colnumber).value = '';
        document.getElementById("customcolumnentry" + colnumber).focus();
    }
}

// Submit status
function submitStatus(status, replyId, postToAccounts) {
    var dataString = 'status=' + encodeURIComponent(status) + "&replyid=" + replyId + "&postToAccounts=" + encodeURIComponent(postToAccounts);
    $.ajax({
        type: "POST",
        url: "actions.php",
        data: dataString,
        success: function() {
            setTimeout('refreshAll()', 3000);
        }
    });
}

// Fills in the hidden "postToAccounts" field based on which account checkboxes
// are ticked.  Must be called every time a human or CPU alters those checkboxes.
function recheckAccountsSelected() {
    var $servicesEnabled = "";
    $('input.accountSelector').each(function() {
        var $box = $(this);
        if ($box.attr("checked")) {
            $servicesEnabled += $box.val() + ";";
        }
    });
    $('input#postToAccounts').val($servicesEnabled);
    var dataString = 'posttoservices=' + $servicesEnabled;
    $.ajax({
        type: "POST",
        url: "actions.php",
        data: dataString
    });
    return true;
}


// Enter to submit custom column forms
function checkForSubmitCustomColumn(field, event, colNumber) {
    var charCode;
    if (event && event.which) {
        charCode = event.which;
    } else if (window.event) {
        event = window.event;
        charCode = event.keyCode;
    }
    if (charCode == 13 || charCode == 10) {
        changeColumn(colNumber, "column.php?div=" + colNumber + "&column=" + escape(field.value) + "&count=20", 1);
    }
}

// Set the size of the mainarea div, so that we get h- and v-scroll of the tweet area.
function setDivSize() {
    var vpheight = 0;
    if (typeof window.innerHeight == 'number') {
        vpheight = window.innerHeight-1; // FF, Webkit, Opera
    } else if (document.documentElement && document.documentElement.clientHeight) {
        vpheight = document.documentElement.clientHeight; // IE 6+
    } else if (document.body && document.body.clientHeight) {
        vpheight = document.body.clientHeight; // IE 4
    }
    d = document.getElementById('mainarea');
    d.style.height= "" + (vpheight-122) + "px";
}

// jQuery startup things (when DOM is avalable)
$(document).ready(function() {

    // jQuery UI formatting
    $('input#submitbutton').button().css({padding:2});
    $('input.accountSelector').button({
        icons: {
            primary: "ui-icon-gear",
            secondary: "ui-icon-triangle-1-s"
        }
    }).css({padding:0});
    $('label').css({padding:0});

    // Clicking main Submit button posts status
    $('input#submitbutton').unbind("click");
    $('input#submitbutton').live("click", function() {
        recheckAccountsSelected();
        submitStatus($("input#status").val(), $("input#replyid").val(), $("input#postToAccounts").val());
        $("input#status").val('');
        return false;
    });
    
    // Typing in main box updates the counter.
    // Enter in main Text input posts status
    $('input#status').unbind("keyup");
    $('input#status').live("keyup", function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            recheckAccountsSelected();
            submitStatus($("input#status").val(), $("input#replyid").val(), $("input#postToAccounts").val());
            $("input#status").val('');
            return false;
        }
        $(this).parent().children('span.counter').html("This post is " + $(this).val().length + " characters long.");
	    if ($(this).val().length > 140) {
	        $(this).parent().children('span.counter').css("color", "red");
	        $(this).parent().children('input#submitbutton').attr('disabled', true);
	    } else {
	        $(this).parent().children('span.counter').css("color", "black");
	        $(this).parent().children('input#submitbutton').attr('disabled', false);
	    }
        return false;
    });
    
    // Click to submit reply form
    $('input.replybutton').unbind("click");
    $('input.replybutton').live("click", function(e) {
        submitStatus($(this).parent().children('input.reply').val(), $(this).parent().children('input.replyid').val(), $(this).parent().children('input.account').val());
        $.fancybox.close();
        return false;
    });

    // Enter to submit reply form
    // Typing in reply form updates the counter
    $('input.reply').unbind("keyup");
    $('input.reply').live("keyup", function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            submitStatus($(this).parent().children('input.reply').val(), $(this).parent().children('input.replyid').val(), $(this).parent().children('input.account').val());
            $.fancybox.close();
        }
        $(this).parent().children('span.counter').html($(this).val().length);
	    if ($(this).val().length > 140) {
	        $(this).parent().children('input.replybutton').val("Twixt");
	    } else {
	        $(this).parent().children('input.replybutton').val("Post");
	    }
        return false;
    });
    
    // User Checks/unchecks services to post to, updating the current knowledge of
    // the user's preferences.
    $('input.accountSelector').unbind("click");
    $('input.accountSelector').live("click", function() {
        recheckAccountsSelected();
    });
    
    // Mouseover statuses to show the operations below.  Entering and leaving handled
    // separately due to the many strange ways in which mouse paths can break
    // hover().
    $('div.item').unbind("mouseenter mouseleave");
    $('div.item').live("mouseenter", function() {
        $(this).find('div.operations').show();
        return false;
    });
    $('div.item').live("mouseleave", function() {
        $(this).find('div.operations').hide();
        return false;
    });
    
    // Mouseover images to show the metadata below.  Entering and leaving handled
    // separately due to the many strange ways in which mouse paths can break
    // hover().
    $('img.avatar').unbind("mouseenter mouseleave");
    $('img.avatar').live("mouseenter", function() {
        $(this).parents('div.item').find('div.metatext').show();
        return false;
    });
    $('img.avatar').live("mouseleave", function() {
        $(this).parents('div.item').find('div.metatext').hide();
        return false;
    });
    
    // Headings show column options
    $('a.columnheading').unbind("click");
    $('a.columnheading').live("click", function(e) {
        $(this).parents('div.columnheading').find('a.columnheading').toggle();
        $(this).parents('div.columnheading').find('div.columnnav').toggle('fast');
        return false;
    });
    
    // Hide buttons hide column options
    $('a.hidenavform').unbind("click");
    $('a.hidenavform').live("click", function(e) {
        $(this).parents('div.columnheading').find('div.columnnav').toggle();
        $(this).parents('div.columnheading').find('a.columnheading').toggle('fast');
        return false;
    });
    
    // Convo buttons show/hide conversations
    $('a.convobutton').unbind("click");
    $('a.convobutton').live("click", function(e) {
        $url = $(this).attr('href');
        if ($(this).parents('div.item').find('div.convoarea').is(":visible")) {
            $(this).parents('div.item').find('div.convoarea').hide();
        } else {
            $(this).parents('div.item').find('div.convoarea').load($url, function() {
                $(this).parents('div.item').find('div.convoarea').show();
            });
        }
        return false;
    });
    
    // Reply buttons show the reply box
    $('a.replybutton').unbind("click");
    $('a.replybutton').live("click", function(e) {
        $url = $(this).attr('href');
        var position = $(this).position();
        $.fancybox({ 'href': $url,
                     'padding': 0,
                     'margin': 0,
                     'onComplete': function() {
                        $('input.reply').putCursorAtEnd();
                        $('input.replybutton').button();
                     }
                     });
        return false;
    });
    
    // "Do action"
    $('a.doactionbutton').unbind("click");
    $('a.doactionbutton').live("click", function(e) {
        $.ajax($(this).attr('href'));
        return false;
    });
    
    // "Confirm action"
    $('a.confirmactionbutton').unbind("click");
    $('a.confirmactionbutton').live("click", function(e) {
        if (confirm("Are you really sure about that?")) {
        	$.ajax($(this).attr('href'));
	    }
        return false;
    });
    
    // Enter submits cols-per-screen form
    $('input#colsperscreen').live("keydown", function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            var dataString = 'colsperscreen=' + $(this).val();
            $.ajax({
                type: "POST",
                url: "actions.php",
                data: dataString,
                success: function() {
                    window.location.reload();
                }
            });
            return false;
        }
    });
    
    // Change submits theme form
    $('select#theme').live("change", function(e) {
        var theme = $("select#theme").val();
        var dataString = 'theme=' + theme;
        $.ajax({
            type: "POST",
            url: "actions.php",
            data: dataString,
            success: function() {
                window.location.reload();
            }
        });
        return false;
    });
    
    // Fancybox images
    $("a.fancybox").live("click", function() {
        var twitpicRegex = new RegExp("twitpic.com\/([\\w\\n]+)", "i");
        var yfrogRegex = new RegExp("yfrog.com\/([\\w\\n]+)", "i");
        if ($(this).attr('href').match(twitpicRegex)) {
            var imageurl = $(this).attr('href').replace(twitpicRegex, 'twitpic.com/show/large/$1.jpg');
            $.fancybox({ 'href': imageurl });
            return false;
        } else if ($(this).attr('href').match(yfrogRegex)) {
            var imageurl = $(this).attr('href') + ":iphone";
            $.fancybox({ 'href': imageurl });
            return false;
        } else {
            return true;
        }
    });
    
    // User Checks/unchecks services to post to, updating the current knowledge of
    // the user's preferences.
    $('input.accountSelector').live("click", function() {
        recheckAccountsSelected();
    });
    
    // Load all columns
    refreshAll();
    
    //$("select, input[type=checkbox], input[type=radio], input[type=file], input[type=submit], a.button, button").uniform();
});

// jQuery onresize things
var resizeTimer;
$(window).resize(function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(setDivSize, 100);
});

// jQuery on AJAX start things
$(document).ajaxStart(function() {
	$.blockUI({ 
		message: '<img src="images/ajax-loader.gif" alt="Loading..."/> Thinking...', 
		timeout: 12000,
		showOverlay: false, 
		centerY: false, 
		css: { 
			width: '130px', 
			top: '10px',
			bottom: '', 
			left: '400px', 
			right: '', 
			border: '1px solid #cccccc', 
			padding: '5px', 
			backgroundColor: '#cfe2ff', 
			'-webkit-border-radius': '10px', 
			'-moz-border-radius': '10px', 
			opacity: .6, 
			color: '#000'
		} 
	});
}).ajaxStop($.unblockUI);

// Normal startup things (when the page has fully loaded)
function init() {
    setDivSize();
    // Focus status entry box
	document.statusform.status.focus();
}
