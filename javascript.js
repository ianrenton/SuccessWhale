var refreshId1 = 1;
var refreshId2 = 2;
var refreshId3 = 3;

// Changes the content of one column, and re-sets its refresh.
function changeColumn(div, url) {
	startSpinner();
    $("#" + div).load(url + "&updatedb=1");
    if (div == 'column1') {
        clearInterval(refreshId1);
        refreshId1 = setInterval(function() {
            $("#" + div).load(url);
        }, 300000);
    } else if (div == 'column2') {
        clearInterval(refreshId2);
        refreshId2 = setInterval(function() {
            $("#" + div).load(url);
        }, 300000);
    } else {
        clearInterval(refreshId3);
        refreshId3 = setInterval(function() {
            $("#" + div).load(url);
        }, 300000);
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

// Updates the Chars Left counter
function countText(field) {
	document.getElementById('charsLeft').innerHTML = 140 - field.value.length;
	if (field.value.length > 140) {
		document.statusform.submit.value = "Post with Twixt";
	} else {
		document.statusform.submit.value = "Post";
	}
	if (field.value.length == 0) {
		document.statusform.replyid.value = '';
	}
}

// Sets the contents of the status field, so clicking the "@" link on a tweet
// auto-fills the box with "@username ", etc.  We also use this to blank the box.
function setStatusField(text, id) {
	document.statusform.status.value = text;
	document.statusform.replyid.value = id;
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

function newWindow(url, name, w, h) {
    w += 32;
    h += 96;
    var win = window.open(url,
      name, 
      'width=' + w + ', height=' + h + ', ' +
      'location=no, menubar=no, ' +
      'status=no, toolbar=no, scrollbars=no, resizable=no');
    win.resizeTo(w, h);
    win.focus();
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
$(function() {
    $('.submitbutton').click(function() {
        var status = $("input#status").val();
        var replyId = $("input#replyid").val();
        var dataString = 'status=' + encodeURIComponent(status) + "&replyid=" + replyId;
        $.ajax({
            type: "POST",
            url: "actions.php",
            data: dataString,
            success: function() {
                setStatusField('', 0);
                setTimeout('refreshAll()', 3000);
            }
        });
        return false;
    });
});

// Fix Enter-to-submit-form issue in IE
$(function() {
    $('.status').keydown(function(e) {
        if (e.keyCode == 13) {
            var status = $("input#status").val();
            var replyId = $("input#replyid").val();
            var dataString = 'status=' + encodeURIComponent(status) + "&replyid=" + replyId;
            $.ajax({
                type: "POST",
                url: "actions.php",
                data: dataString,
                success: function() {
                    setStatusField('', 0);
                    setTimeout('refreshAll()', 3000);
                }
            });
            return false;
        }
    });
});

// jQuery startup things (when DOM is avalable)
$(document).ready(function() {
    // Load all columns
    refreshAll();
});

// Normal startup things (when the page has fully loaded)
function init() {
    // Focus status entry box
	document.statusform.status.focus();
}
