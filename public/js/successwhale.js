// Globals
var API_SERVER = 'https://successwhale-api.herokuapp.com/v3';
var NARROW_SCREEN_WIDTH = 600;

// Viewmodel for SW
function SWUserViewModel() {
  var self = this;
  
  self.colsPerScreen = ko.observable();
  self.token = ko.observable();
  self.postToAccounts = ko.observableArray();
  self.columns = ko.observableArray();
  
  self.postToAccountsString = ko.computed(function () {
     return getPostToAccountsString(self.postToAccounts());
  });
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);

// Add a Knockout "visible" binding with animation to the handlers list
ko.bindingHandlers.slideVisible = {
    init: function(element, valueAccessor) {
        // Initially set the element to be instantly visible/hidden depending on the value
        var value = valueAccessor();
        $(element).toggle(ko.unwrap(value)); // Use "unwrapObservable" so we can handle values that may or may not be observable
    },
    update: function(element, valueAccessor) {
        // Whenever the value subsequently changes, slowly fade the element in or out
        var value = valueAccessor();
        ko.unwrap(value) ? $(element).slideDown() : $(element).slideUp();
    }
};

// Checks the user is logged in (via a cookie) - if not, punts them
// to the login page
function checkLoggedIn() {
  if (!readCookie('token')) {
    window.location = '/';
  } else {
    viewModel.token(readCookie('token'));
  }
}

// JS implementation of Java .hashCode() method
function hashCode(string) {
  var hash = 0, i, chara;
  if (string.length == 0) return hash;
  for (i = 0, l = string.length; i < l; i++) {
    chara  = string.charCodeAt(i);
    hash  = ((hash<<5)-hash)+chara;
    hash |= 0; // Convert to 32bit integer
  }
  return hash;
};

// Shows an error box for a set time, with the supplied HTML and optionally also
// a SuccessWhale API error message extracted from the returnedData of an unsuccessful
//  request
function showError(html, returnedData) {
  if (typeof(returnedData) != "undefined") {
    html += "<br/>The SuccessWhale API reported the following error:<br/>" + JSON.parse(returnedData.responseText).error
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
  return linkify_entities(content);
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
        returnedData.items[i].thread = ko.observableArray();
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
      viewModel.columns()[j].loading(false);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column "' + viewModel.columns()[j].title + '"', returnedData);
      viewModel.columns()[j].loading(false);
    });
}

// Fetch and display column content
function refreshColumns() {
  var j = 0;
  for (; j < viewModel.columns().length; j++) {
    loadFeedForColumn(j);
  }
  
  // Hide loading overlay, if it's displayed, as we have now fully loaded the display.
  $('body').removeClass("loading");
} 

// Get the user's display settings
function getDisplaySettings() {
  var jqxhr = $.get(API_SERVER+'/displaysettings', {token: viewModel.token()})
    .done(function(returnedData) {
      // Store the columns-per-screen value for use in rendering, or force it to 1
      // column per screen and narrow things down if we have a narrow (mobile phone) 
      // window.
      if ($(window).width() > NARROW_SCREEN_WIDTH) {
        viewModel.colsPerScreen(returnedData.colsperscreen);
      } else {
        viewModel.colsPerScreen(1);
      }
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
      
      // Add a dummy observableArray to hold items later, and tag it as loading
      var i = 0;
      for (; i<cols.length; i++) {
        cols[i].items = ko.observableArray();
        cols[i].loading = ko.observable(true);
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

// Automatic stuff on page load
$(document).ready(function() {

  // Loading overlay. Hidden at the end of displayColumns().
  $('body').addClass("loading");
  
  // Bind "post item" on button click or textarea Ctrl+Enter
  $('#postentry').keydown(function (e) {
    if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
      $('form#postform').submit();
    }
  });
  $('#postbutton').click(function (e) {
    $('form#postform').submit();
  });
  
  // Bind jQuery Form to the main post form so we can submit it in an AJAXy way,
  // and handle success/failure of the post with callbacks
  var options = {  
    url:        API_SERVER+'/item',
    type:       'POST',
    dataType:   'json',
    resetForm:  true,
    success:    function(jsonResponse) { 
      showSuccess('Item posted.');
      refreshColumns();
    },
    error:      function(jsonResponse) { 
      showError('Item could not be posted due to an error.', JSON.stringify(jsonResponse));
    } 
  }; 
  $('form#postform').ajaxForm(options);
  
  // Bind Attach File clear button
  $('input#fileclearbutton').click(function (e) {
    $('form#postform #filetoupload').clearFields();
  });
  
  // Bind gpopover items
  $('#postbuttondropdown').gpopover({preventHide: true});
  $('#attachbutton').gpopover({preventHide: true});
  
  // Bind other menu buttons
  $('#logoutbutton').click(function (e) {
    eraseCookie('token');
    window.location = '/';
  });
  
  // Focus and enable autosize on post entry box
  $('#postentry').autosize();
  $('#postentry').focus();
  
  // Main API calls to display data
  checkLoggedIn();
  getDisplaySettings();
  displayPostToAccounts();
  displayColumns();
  
  // Refresh every 5 minutes
  setInterval( function() { refreshColumns(); }, 300000);
});
