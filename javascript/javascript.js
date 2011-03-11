var refreshIDs = new Array();
var normallySelectedAccounts = new Array();

// Changes the content of one column, and re-sets its refresh.
function changeColumn(colnumber, url, updatedb) {
    if (url.indexOf("----------") == -1) {
        // Normal use
	    startSpinner();
        $("#column" + colnumber).load(url + "&updatedb=" + updatedb);
        clearInterval(refreshIDs[colnumber]);
        refreshIDs[colnumber] = setInterval(function() {
            $("#column" + colnumber).load(url);
        }, 300000);
    } else {
        // "Other" selected in dropdown
        document.getElementById("customcolumnentry" + colnumber).disabled = false;
        document.getElementById("customcolumnentry" + colnumber).value = '';
        document.getElementById("customcolumnentry" + colnumber).focus();
    }
}

// Expands a conversation.
function expandConvo(url, dummy, div) {
    $("#" + div).load(url + "&div=" + div);
}

// Hides a conversation or reply box.
function hideConvo(div) {
    $("#" + div).html("");
}

// Expands a reply box.
function showReplyBox(dummy, div, initialtext, replyid, account) {
    $("#" + div).load("replybox.php?div=" + div + "&initialtext=" + escape(initialtext) + "&replyid=" + replyid + "&account=" + escape(account));
}

// Updates the Chars Left counter, switches chars left style and button text
// at 140 chars, clears replyID and restores account selection to normal at zero.
function countText(field) {
	document.getElementById('chars').innerHTML = "This post is " + field.value.length + " characters long";
	if (field.value.length > 140) {
		document.statusform.submit.value = "Post with Twixt";
		document.getElementById('chars').style.color="red";
	} else {
		document.statusform.submit.value = "Post";
		document.getElementById('chars').style.color="black";
	}
	if (field.value.length == 0) {
		document.statusform.replyid.value = '';
	}
}

// Requests confirmation for an action to be performed via the actionbox.
function confirmAction(link) {
	if (confirm("Are you really sure about that?")) {
    	startSpinner();
		$("#actionbox").load(link);
	}
}

// Performs an action via the actionbox without confirmation
function doAction(link) {
	startSpinner();
	$("#actionbox").load(link);
}

function startSpinner() {
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
}

// BlockUI
$(document).ajaxStart(function() {
	startSpinner();
}).ajaxStop($.unblockUI);

// Submit status
function submitStatus(status, replyId, postToAccounts) {
    var dataString = 'status=' + encodeURIComponent(status) + "&replyid=" + replyId + "&postToAccounts=" + encodeURIComponent(postToAccounts);
    $.ajax({
        type: "POST",
        url: "actions.php",
        data: dataString,
        success: function() {
            document.statusform.status.value = '';
	        document.statusform.replyid.value = '';
	        document.statusform.status.focus();
	        countText(document.statusform.status);
            setTimeout('refreshAll()', 3000);
        }
    });
}


// Enter to submit cols per screen form
$(function() {
    $('input#colsperscreen').keydown(function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            var colsperscreen = $("input#colsperscreen").val();
            var dataString = 'colsperscreen=' + colsperscreen;
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
});


// Change submit theme form
$(function() {
    $('select#theme').change(function(e) {
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
});

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

// User Checks/unchecks services to post to, updating the current knowledge of
// the user's preferences.
$(function() {
    $('input.accountSelector').click(function() {
        recheckAccountsSelected();
    });
});

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
        vpheight = window.innerHeight-2; // FF, Webkit, Opera
    } else if (document.documentElement && document.documentElement.clientHeight) {
        vpheight = document.documentElement.clientHeight; // IE 6+
    } else if (document.body && document.body.clientHeight) {
        vpheight = document.body.clientHeight; // IE 4
    }
    d = document.getElementById('mainarea');
    d.style.height= "" + (vpheight-132) + "px";
}

// jQuery startup things (when DOM is avalable)
$(document).ready(function() {
    // Clicking main Submit button posts status
    $('input#submitbutton').unbind("click");
    $('input#submitbutton').live("click", function() {
        submitStatus($("input#status").val(), $("input#replyid").val(), $("input#postToAccounts").val());
        return false;
    });
    
    // Typing in main box updates the counter.
    // Enter in main Text input posts status
    $('input#status').unbind("keydown");
    $('input#status').live("keydown", function(e) {
        countText(e.target);
        if (e.keyCode == 13 || e.keyCode == 10) {
            submitStatus($("input#status").val(), $("input#replyid").val(), $("input#postToAccounts").val());
            return false;
        }
    });
    
    // Click to submit reply form
    $('input.replybutton').unbind("click");
    $('input.replybutton').live("click", function(e) {
        $boxname = e.target.name;
        submitStatus($("input#"+$boxname+"-reply").val(), $("input#"+$boxname+"-reply").val(), $("input#"+$boxname+"-replyaccount").val());
        hideConvo($boxname);
        return false;
    });

    // Enter to submit reply form
    $('input.reply').unbind("keydown");
    $('input.reply').live("keydown", function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            $boxname = e.target.name;
            submitStatus($("input#"+$boxname+"-reply").val(), $("input#"+$boxname+"-reply").val(), $("input#"+$boxname+"-replyaccount").val());
            hideConvo($boxname);
            return false;
        }
    });
    
    // User Checks/unchecks services to post to, updating the current knowledge of
    // the user's preferences.
    $('input.accountSelector').unbind("click");
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

// Normal startup things (when the page has fully loaded)
function init() {
    setDivSize();
    // Focus status entry box
	document.statusform.status.focus();
}
