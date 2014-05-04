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
  //self.postToAccounts = ko.observableArray();
  // Columns list
  //self.columns = ko.observableArray();
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
/*function displayPostToAccounts() {
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

// Build a list of service/uid:service/uid... for every service we have selected
function getPostToAccountsString(postToAccounts) {
  var postToAccountString = '';
  for (i=0; i<postToAccounts.length; i++) {
    if (postToAccounts[i].enabled()) {
      postToAccountString += postToAccounts[i].service + "/" + postToAccounts[i].uid + ":";
    }
  } 
  return postToAccountString;
}*/

// Automatic stuff on page load
$(document).ready(function() {

  // Loading overlay. Hidden when data is retrieved
  $('body').addClass("loading");
  
  // Main API calls to display data
  checkLoggedIn();
  getDisplaySettings();
  
  // Bind save buttons
  $('#savedisplaysettings').click(function (e) {
   setDisplaySettings();
   return false;
  });
  
  // Hide loading overlay.
  $('body').removeClass("loading");
});
