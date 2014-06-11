// Viewmodel for SW
function SWUserViewModel() {
  var self = this;
  
  // SuccessWhale user API token
  self.token = ko.observable();
  
  // Display setting - number of columns per screen (honoured on wide screens only)
  self.colsPerScreen = ko.observable(3);
  // Display setting - user theme
  self.theme = ko.observable('default');
  // Display setting - highlight items newer than this (mins)
  self.highlightTime = ko.observable(0);
  // Display setting - show inline media
  self.inlineMedia = ko.observable(true);
  // Which SuccessWhale service accounts to post to
  self.postToAccounts = ko.observableArray();
  // Giant data blob of all data from all columns
  self.columns = ko.observableArray();
  
  // Content of the post entry box
  self.postEntryText = ko.observable('');
  // Using mobile view?
  self.mobileView = ko.observable(false);
  // Currently shown column in mobile view
  self.mobileCurrentColumn = ko.observable(0);
  
  self.postToAccountsString = ko.computed(function () {
     return getPostToAccountsString(self.postToAccounts());
  });
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel, document.getElementById("htmlTop")); // htmlTop is a hack to allow knockout to data-bind elements in the <head>

// Add a Knockout "visible" binding with animation to the handlers list
ko.bindingHandlers.slideVisible = {
    init: function(element, valueAccessor) {
        // Initially set the element to be instantly visible/hidden depending on the value
        var value = valueAccessor();
        $(element).toggle(ko.unwrap(value)); // Use "unwrapObservable" so we can handle values that may or may not be observable
    },
    update: function(element, valueAccessor) {
        // Whenever the value subsequently changes, slide the element in or out
        var value = valueAccessor();
        ko.unwrap(value) ? $(element).slideDown() : $(element).slideUp();
    }
};

// Set up a jQuery Form for the main and reply post forms so we can submit them in an
// AJAXy way, and handle success/failure of the post with callbacks
var postItemOptions = {  
  url:        API_SERVER+'/item',
  type:       'POST',
  dataType:   'json',
  success:    function(jsonResponse) {
    showSuccess('Item posted.');
    // Clear inputs
    $('form#postform #postentry').val("");
    $('form#postform #filetoupload').clearFields();
    $('.replyentry').each(function() {
        $(this).val("");
    });
    // Reload everything
    refreshColumns();
  },
  error:      function(jsonResponse) { 
    showError('Item could not be posted due to an error.', JSON.stringify(jsonResponse));
  } 
};

// Shows an error box for a set time, with the supplied HTML and optionally also
// a SuccessWhale API error message extracted from the returnedData of an unsuccessful
//  request
function showError(html, returnedData) {
  if (typeof(returnedData) != "undefined") {
    if (returnedData.responseText) {
      html += "<br/>The SuccessWhale API reported the following error:<br/>" + JSON.parse(returnedData.responseText).error
    }
  }
  $('#errorbox').html(html);
  $('#errorbox').show('slow', function hideLater() {
    setTimeout(function() {
      $('#errorbox').hide('slow');
    }, 5000);
  });
}

// Shows a success notification for a set time, with the supplied HTML content
function showSuccess(html) {
  $('#successbox').html(html);
  $('#successbox').show('slow', function hideLater() {
    setTimeout(function() {
      $('#successbox').hide('slow');
    }, 5000);
  });
}

// Turn an item's text into proper HTML
function makeItemTextHTML(content) {
  return linkify_entities(content, viewModel.inlineMedia()).replace(/\n/g, '<br />');
}

// Load feed for a single column
function loadFeedForColumn(j) {
  viewModel.columns()[j].loading(true);
  var jqxhr = $.get(API_SERVER+'/feed', {sources: viewModel.columns()[j].fullpath, token: viewModel.token()})
    .done(function(returnedData) {
      // Add dummy observables to manage the inline display of thread and comment boxes
      var i = 0;
      for (; i<returnedData.items.length; i++) {
        returnedData.items[i].replyvisible = ko.observable(false);
        returnedData.items[i].threadvisible = ko.observable(false);
        returnedData.items[i].threadloading = ko.observable(false);
        returnedData.items[i].thread = ko.observableArray();
        returnedData.items[i].replyText = ko.observable('');
        
        // Pre-populate replyText for tweets
        if (returnedData.items[i].service=='twitter') {
          var text = '@'+returnedData.items[i].content.fromuser+' ';
          for (var n=0; n<returnedData.items[i].content.usernames.length; n++) {
            if (returnedData.items[i].content.usernames[n].user != returnedData.items[i].fetchedforuser) {
              text = text + '@' + returnedData.items[i].content.usernames[n].user + ' ';
            }
          }
          returnedData.items[i].replyText(text);
        }
      }
      // Put all the items into the viewmodel for display
      viewModel.columns()[j].items.removeAll();
      viewModel.columns()[j].items.push.apply(viewModel.columns()[j].items, returnedData.items);
      // Add fancybox to items that need it
      $('.fancybox').each(function() {
        $(this).fancybox({
          'scrolling'   : 'no'
        });
      });
      // Add autosize to items that need it
      $('.replyentry').each(function() {
        $(this).autosize();
      });
      // Bind the reply forms
      $('.replyentry').each(function() {
        $(this).keydown(function (e) {
          if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
            $(this).parents('form:first').submit();
          }
        });
      });
      $('.replybutton').each(function() {
        $(this).click(function (e) {
          $(this).parents('form:first').submit();
          $(this).parents('div.inlinereply').hide(fast);
        });
      });
      $('form.replyform').ajaxForm(postItemOptions);
      // Set the loading status to false
      viewModel.columns()[j].loading(false);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column "' + viewModel.columns()[j].title + '"', returnedData);
      viewModel.columns()[j].loading(false);
    });
}

// Load the thread for a single item into its "thread" array
// Set skipfirst = true for items where the original post is already displayed, or false
// where it isn't (e.g. Facebook notifications, where we need to display the post that
// the notification was about
function loadThreadForItem(item, skipfirst) {
  item.threadloading(true);
  var jqxhr = $.get(API_SERVER+'/thread', {service: item.service, uid: item.fetchedforuserid, postid: item.content.replytoid, skipfirst: skipfirst, token: viewModel.token()})
    .done(function(returnedData) {
      item.thread.removeAll();
      item.thread.push.apply(item.thread, returnedData.items);
      item.threadloading(false);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch thread for this item.', returnedData);
      item.threadloading(false);
    });
}

// Fetch and display column content, with a delay between each load to avoid overloading
// the browser and the API.
function refreshColumns() {
  var j = 0;
  for (; j < viewModel.columns().length; j++) {
    (function(index) {
        setTimeout(function() { loadFeedForColumn(index); }, 5000*index);
    })(j);
  }
  
  // Hide loading overlay, if it's displayed, as we have now fully loaded the display.
  $('body').removeClass("loading");
} 

// Get the user's display settings
function getDisplaySettings() {
  var jqxhr = $.get(API_SERVER+'/displaysettings', {token: viewModel.token()})
    .done(function(returnedData) {
      // Store the columns-per-screen value for use in rendering
      // (ignored in mobile view)
      viewModel.colsPerScreen(returnedData.colsperscreen);
      // Get theme, highlight time and inline media setting
      viewModel.theme(returnedData.theme);
      viewModel.highlightTime(returnedData.highlighttime);
      viewModel.inlineMedia(returnedData.inlinemedia);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch display settings', returnedData);
    });
}

// Fetch and display the list of accounts to post to
function displayPostToAccounts() {
  var jqxhr = $.get(API_SERVER+'/posttoaccounts', {token: viewModel.token()})
    .done(function(returnedData) {
      var accounts = returnedData.posttoaccounts
      var i = 0;
      for (; i<accounts.length; i++) {
        accounts[i].enabled = ko.observable(accounts[i].enabled);
      }
      viewModel.postToAccounts.push.apply(viewModel.postToAccounts, accounts);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch account list', returnedData);
    });
}

// Fetch and display columns
function displayColumns() {
  var jqxhr = $.get(API_SERVER+'/columns', {token: viewModel.token()})
    .done(function(returnedData) {
      var cols = returnedData.columns;
      
      // Add a dummy observableArray to hold items later, and an observable for the
      // loading state
      var i = 0;
      for (; i<cols.length; i++) {
        cols[i].items = ko.observableArray();
        cols[i].loading = ko.observable(false);
      }
      
      // Update the view model
      viewModel.columns.push.apply(viewModel.columns, cols);
      
      // Refresh all columns to pull in items
      refreshColumns();
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column list', returnedData);
    });
}

// Perform an action on an item. 2013 winner of the "most generic function description"
// award. See https://github.com/ianrenton/successwhale-api/blob/master/docs/action.md
function performAction(params) {
  // We have already been given all the params we need, except for the token
  params['token'] = viewModel.token();
  // Now make the call
  var jqxhr = $.post(API_SERVER+'/action', params)
    .done(function(returnedData) {
      showSuccess('Item ' + params['action'] + ' successful');
      refreshColumns();
    })
    .fail(function(returnedData) {
      showError('Failed to ' + params['action'] + ' the item', returnedData);
    });
}

// Build a list of service/uid:service/uid... for every service we have selected
function getPostToAccountsString(postToAccounts) {
  var postToAccountString = '';
  for (i=0; i<postToAccounts.length; i++) {
    if (postToAccounts[i].enabled()) {
      postToAccountString += postToAccounts[i].service + "/" + postToAccounts[i].uid + ":";
    }
  } 
  return postToAccountString;
}

// METAL easter egg
function metallise(textarea) {
  var text = textarea.val().toUpperCase();
  var newText = '';
  for (var i = 0; i < text.length; i++) {
    if ((text[i] == "A") || (text[i] == "Ä"))
    {
      if (Math.random() < 0.3)
      {
        newText = newText + "Ä";
      } else {
        newText = newText + "A";
      }
    }
    else if ((text[i] == "O") || (text[i] == "Ö"))
    {
      if (Math.random() < 0.3)
      {
        newText = newText + "Ö";
      } else {
        newText = newText + "O";
      }
    }
    else if ((text[i] == "U") || (text[i] == "Ü"))
    {
      if (Math.random() < 0.3)
      {
        newText = newText + "Ü";
      } else {
        newText = newText + "U";
      }
    }
    else
    {
      newText = newText + text[i];
    }
  }
  textarea.val(newText);
}

// Automatic stuff on page load
$(document).ready(function() {

  // Loading overlay. Hidden at the end of displayColumns().
  $('body').addClass("loading");
  
  // Check window size
  viewModel.mobileView($(window).width() <= NARROW_SCREEN_WIDTH);
  
  // Bind "post item" on button click or textarea Ctrl+Enter
  // and remove focus on Escape
  $('#postentry').keydown(function (e) {
    if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
      $('form#postform').submit();
    } else if (e.keyCode == 27) {
      $('#postentry').blur();
    }
  });
  // Easter egg
  $('#postentry').keyup(function (e) {
    if (viewModel.theme() == 'METAL') {
      metallise($('#postentry'));
    }
  });
  $('#postbutton').click(function (e) {
    $('form#postform').submit();
  });
  $('form#postform').ajaxForm(postItemOptions);
  
  // Bind the "T" key when an input is unfocussed to focus on the
  // text entry box.
  $(document).keydown(function (e) {
    if ($(e.target).is("textarea")) {
      return true;
    } else if (e.keyCode == 84) {
      $('#postentry').focus();
      return false; // prevent 't' being typed in post entry box
    }
  });
  
  // Bind Attach File clear button
  $('input#fileclearbutton').click(function (e) {
    $('form#postform #filetoupload').clearFields();
  });
  
  // Bind gpopover items
  $('#posttoaccountsbutton').gpopover({preventHide: true});
  $('#attachbutton').gpopover({preventHide: true});
  
  // Bind other menu buttons
  $('#logoutbutton').click(function (e) {
    eraseCookie('token');
    window.location = '/login';
  });
  
  // Enable autosize on post entry box
  $('#postentry').autosize();
  
  // Bind swipe actions for mobile
  if (viewModel.mobileView()) {
    $("#columns").swipe( {
      swipeLeft:function(event, direction, distance, duration, fingerCount) {
        if (viewModel.mobileCurrentColumn() < viewModel.columns().length-1) {
          viewModel.mobileCurrentColumn(viewModel.mobileCurrentColumn()+1);
          window.scrollTo(0, 0);
        }
      },
      swipeRight:function(event, direction, distance, duration, fingerCount) {
        if (viewModel.mobileCurrentColumn() > 0) {
          viewModel.mobileCurrentColumn(viewModel.mobileCurrentColumn()-1);
          window.scrollTo(0, 0);
        }
      },
      threshold:25
    });
  }
  
  // Main API calls to display data
  checkLoggedIn();
  getDisplaySettings();
  displayPostToAccounts();
  displayColumns();
  
  // Refresh every 5 minutes
  setInterval( function() { refreshColumns(); }, 300000);
});

// Recalculate the mobile view logic on window resize
$( window ).resize(function() {
  viewModel.mobileView($(window).width() <= NARROW_SCREEN_WIDTH);
});
