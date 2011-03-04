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
function expandConvo(url, div) {
    startSpinner();
    $("#" + div).load(url + "&div=" + div);
}

// Hides a conversation.
function hideConvo(div) {
    $("#" + div).html("");
}

// Updates the Chars Left counter, switches chars left style and button text
// at 140 chars, clears replyID and restores account selection to normal at zero.
function countText(field) {
	document.getElementById('charsLeft').innerHTML = 140 - field.value.length;
	if (field.value.length > 140) {
		document.statusform.submit.value = "Post with Twixt";
		document.getElementById('charsLeft').style.color="red";
	} else {
		document.statusform.submit.value = "Post";
		document.getElementById('charsLeft').style.color="black";
	}
	if (field.value.length == 0) {
		document.statusform.replyid.value = '';
		$('.accountSelector').each(function() {
		    var $box = $(this);
		    if (normallySelectedAccounts[$box.val()] != null) {
                $box.attr('checked', normallySelectedAccounts[$box.val()]);
            } else {
                $box.attr('checked', true);
            }
            $box.attr('disabled', false);
        });
        recheckAccountsSelected();
	}
}

// Sets the contents of the status field, so clicking the "@" link on a tweet
// auto-fills the box with "@username ", etc.  We also use this to blank the box.
// If we're not blanking the box, we're about to do an account-specific action,
// so disable all the account checkboxes and only check the relevant one.
function setStatusField(text, id, sendAccount) {
	document.statusform.status.value = text;
	document.statusform.replyid.value = id;
	if (sendAccount != '') {
	    $('.accountSelector').each(function() {
            var $box = $(this);
            normallySelectedAccounts[$box.val()] = $box.attr('checked');
            $box.attr('disabled', true);
            if ($box.val() == sendAccount) {
                $box.attr('checked', true);
                normallySelectedAccounts[$box.val()] = $box.attr('checked');
            } else {
                $box.attr('checked', false);
            }
        });
        recheckAccountsSelected();
    }
	document.statusform.status.focus();
	countText(document.statusform.status, document.statusform.charsLeft);
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
			color: '#000',
			fontSize: '80%'
		} 
	});
}

// BlockUI
$(document).ajaxStart(function() {
	startSpinner();
}).ajaxStop($.unblockUI);

// Submit status
function submitStatus() {
    var status = $("input#status").val();
    var replyId = $("input#replyid").val();
    var postToAccounts = $("input#postToAccounts").val();
    //alert(postToAccounts);
    var dataString = 'status=' + encodeURIComponent(status) + "&replyid=" + replyId + "&postToAccounts=" + postToAccounts;
    $.ajax({
        type: "POST",
        url: "actions.php",
        data: dataString,
        success: function() {
            setStatusField('', 0, '');
            setTimeout('refreshAll()', 3000);
        }
    });
}

// Click to submit status form
$(function() {
    $('.submitbutton').click(function() {
        submitStatus();
        return false;
    });
});

// Enter to submit status form
$(function() {
    $('.status').keydown(function(e) {
        if (e.keyCode == 13 || e.keyCode == 10) {
            submitStatus();
            return false;
        }
    });
});

// Fills in the hidden "postToAccounts" field based on which account checkboxes
// are ticked.  Must be called every time a human or CPU alters those checkboxes.
function recheckAccountsSelected() {
    var $servicesEnabled = "";
    $('.accountSelector').each(function() {
        var $box = $(this);
        if ($box.attr("checked")) {
            $servicesEnabled += $box.val() + ";";
        }
    });
    $('input#postToAccounts').val($servicesEnabled);
    return true;
}

// User Checks/unchecks services to post to, updating the current knowledge of
// the user's preferences.
$(function() {
    $('.accountSelector').click(function() {
        $('.accountSelector').each(function() {
            var $box = $(this);
            normallySelectedAccounts[$box.val()] =  $box.attr("checked");
        });
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
    d.style.height= "" + (vpheight-125) + "px";
}

// jQuery startup things (when DOM is avalable)
$(document).ready(function() {
    // Load all columns
    refreshAll();
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
    recheckAccountsSelected();
    // Focus status entry box
	document.statusform.status.focus();
}
