// Globals
var API_SERVER = 'https://successwhale-api.herokuapp.com/v3';

// Viewmodel for SW
function SWUserViewModel() {
  var self = this;
  
  // SuccessWhale user API token
  self.token = ko.observable();
  
  // Display setting - number of columns per screen (honoured on wide screens only)
  self.colsPerScreen = ko.observable(3);
  // Display setting - user theme
  self.availableThemes = ko.observableArray(['default', 'inverted'])
  self.theme = ko.observable('default');
  // Display setting - highlight items newer than this (mins)
  self.highlightTime = ko.observable(0);
  // Display setting - show inline media
  self.inlineMedia = ko.observable(true);
  
  // Which SuccessWhale service accounts to post to
  self.accounts = ko.observableArray();
  // Columns list
  self.columns = ko.observableArray();
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel, document.getElementById("htmlTop")); // htmlTop is a hack to allow knockout to data-bind elements in the <head>


// Checks the user is logged in (via a cookie) - if not, punts them
// to the login page
function checkLoggedIn() {
  if (!readCookie('token')) {
    window.location = '/login';
  } else {
    viewModel.token(readCookie('token'));
  }
}

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


// Get the user's display settings
function getDisplaySettings() {
  var jqxhr = $.get(API_SERVER+'/displaysettings', {token: viewModel.token()})
    .done(function(returnedData) {
      viewModel.colsPerScreen(returnedData.colsperscreen);
      viewModel.theme(returnedData.theme);
      viewModel.highlightTime(returnedData.highlighttime);
      viewModel.inlineMedia(returnedData.inlineMedia);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch display settings', returnedData);
    });
}

// Set the user's display settings
function setDisplaySettings() {
  var jqxhr = $.post(API_SERVER+'/displaysettings', {token: viewModel.token(), colsPerScreen: viewModel.colsPerScreen(), theme: viewModel.theme(), highlightTime: viewModel.highlightTime(), inlineMedia: viewModel.inlineMedia()})
    .done(function(returnedData) {
      showMessage('Display settings saved.', returnedData);
    })
    .fail(function(returnedData) {
      showError('Failed to save display settings', returnedData);
    });
}

// Fetch and display the list of accounts to post to
function getAccounts() {
  var jqxhr = $.get(API_SERVER+'/posttoaccounts', {token: viewModel.token()})
    .done(function(returnedData) {
      var accounts = returnedData.posttoaccounts
      var i = 0;
      for (; i<accounts.length; i++) {
        accounts[i].enabled = ko.observable(accounts[i].enabled);
      }
      viewModel.accounts.push.apply(viewModel.accounts, accounts);
      
      // Bind delete buttons for each
      $('.deleteaccountbutton').each(function() {
        $(this).click(function (e) {
          alert("Not implemented yet! This would prompt for confirmation, then call the delete service account API endpoint.");
          return false;
        });
      });
    })
    .fail(function(returnedData) {
      showError('Failed to fetch account list', returnedData);
    });
}

// Fetch and display columns
function getColumns() {
  var jqxhr = $.get(API_SERVER+'/columns', {token: viewModel.token()})
    .done(function(returnedData) {
      // Update the view model
      viewModel.columns.push.apply(viewModel.columns, returnedData.columns);
      // Hide loading overlay.
      $('body').removeClass("loading");
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column list', returnedData);
    });
}

// Automatic stuff on page load
$(document).ready(function() {

  // Loading overlay. Hidden in getColumns(), we just assume that happens last.
  $('body').addClass("loading");
  
  // Main API calls to display data
  checkLoggedIn();
  getDisplaySettings();
  getAccounts();
  getColumns();
  
  // Bind buttons
  $('#savedisplaysettings').click(function (e) {
   setDisplaySettings();
   return false;
  });
  $('#saveaccountsettings').click(function (e) {
   alert("Not implemented yet! This would call the POST posttoaccounts API endpoint.");
   return false;
  });
  $('#savecolumnsettings').click(function (e) {
   alert("Not implemented yet! This would call the POST columns API endpoint.");
   return false;
  });
  $('a#authwithtwitter').click(function (e) {
    $(this).addClass("loading");
    var jqxhr = $.get(API_SERVER+'/authwithtwitter', {callback_url: location.origin+'/twittercallback'})
    .done(function(returnedData) {
      window.location = returnedData.url;
    })
    .fail(function(returnedData) {
      // Display error box
      viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
      $('#loginerrorbox').show('fast');
      $(this).removeClass("loading");
    });
  });
  $('a#authwithfacebook').click(function (e) {
    $(this).addClass("loading");
    var jqxhr = $.get(API_SERVER+'/authwithfacebook', {callback_url: escape(location.origin+'/facebookcallback')})
    .done(function(returnedData) {
      window.location = returnedData.url;
    })
    .fail(function(returnedData) {
      // Display error box
      viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
      $('#loginerrorbox').show('fast');
      $(this).removeClass("loading");
    });
  });
});
