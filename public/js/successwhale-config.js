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
  
  // Which SuccessWhale service accounts are available, and which of them should
  // be posted to by default
  self.accounts = ko.observableArray();
  // Columns list
  self.columns = ko.observableArray();
  // Banned phrases list
  self.bannedPhrases = ko.observable();
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
      showError('Failed to fetch display settings.', returnedData);
    });
}

// Set the user's display settings
function setDisplaySettings() {
  var jqxhr = $.post(API_SERVER+'/displaysettings', {token: viewModel.token(), colsPerScreen: viewModel.colsPerScreen(), theme: viewModel.theme(), highlightTime: viewModel.highlightTime(), inlineMedia: viewModel.inlineMedia()})
    .done(function(returnedData) {
      showSuccess('Display settings saved.', returnedData);
    })
    .fail(function(returnedData) {
      showError('Failed to save display settings.', returnedData);
    });
}

// Get the user's banned phrases list
function getBannedPhrases() {
  var jqxhr = $.get(API_SERVER+'/bannedphrases', {token: viewModel.token()})
    .done(function(returnedData) {
      viewModel.bannedPhrases(returnedData.bannedphrases.join('\n'));
    })
    .fail(function(returnedData) {
      showError('Failed to fetch banned phrases list.', returnedData);
    });
}

// Set the user's banned phrases list
function setBannedPhrases() {
  var jqxhr = $.post(API_SERVER+'/bannedphrases', {token: viewModel.token(), bannedphrases: ko.toJSON(viewModel.bannedPhrases().match(/[^\r\n]+/g))})
    .done(function(returnedData) {
      showSuccess('Banned phrases saved.', returnedData);
    })
    .fail(function(returnedData) {
      showError('Failed to save banned phrases.', returnedData);
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
          var serviceAndUIDAndUsername = $(this).attr('name').split('/');
          var ok = confirm('This will remove the ' + serviceAndUIDAndUsername[0] + ' account "' + serviceAndUIDAndUsername[2] + '" from SuccessWhale. If you wish to use that account with SuccessWhale again, you will have to re-authorise it.\nClick OK to remove the account.');
          if (ok) {
            var jqxhr = $.post(API_SERVER+'/deleteaccount', {token: viewModel.token(), service: serviceAndUIDAndUsername[0], uid: serviceAndUIDAndUsername[1]})
            .done(function(returnedData) {
              showSuccess('Account deleted.', returnedData);
              getAccounts();
            })
            .fail(function(returnedData) {
              showError('Failed to delete account.', returnedData);
            });
          }
          return false;
        });
      });
    })
    .fail(function(returnedData) {
      showError('Failed to fetch account list.', returnedData);
    });
}

// Set the user's "default post to" accounts
function setAccountSettings() {
  var jqxhr = $.post(API_SERVER+'/posttoaccounts', {token: viewModel.token(), posttoaccounts: ko.toJSON(viewModel.accounts())})
    .done(function(returnedData) {
      showSuccess('Account settings saved.', returnedData);
    })
    .fail(function(returnedData) {
      showError('Failed to save account settings.', returnedData);
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
      showError('Failed to fetch column list.', returnedData);
    });
}

// Delete all data for the user
function deleteAllData() {
  var ok = confirm('This will delete all information SuccessWhale holds about you and your service accounts. This information is gone forever and we cannot retrieve it under any circumstances.\nClick OK if you wish to continue.');
  if (ok) {
    var jqxhr = $.post(API_SERVER+'/deletealldata', {token: viewModel.token()})
      .done(function(returnedData) {
        showSuccess('All data deleted. Logging out...', returnedData);
        setTimeout(function() {
          eraseCookie('token');
          window.location = '/';
        }, 2000);
        
      })
      .fail(function(returnedData) {
        showError('Failed to delete data.', returnedData);
      });
  }
}

// Automatic stuff on page load
$(document).ready(function() {

  // Loading overlay. Hidden in getColumns(), we just assume that happens last.
  $('body').addClass("loading");
  
  // Main API calls to display data
  checkLoggedIn();
  getAccounts();
  getDisplaySettings();
  getBannedPhrases();
  getColumns();
  
  // Bind buttons
  $('#saveaccountsettings').click(function (e) {
   setAccountSettings();
   return false;
  });
  $('#savedisplaysettings').click(function (e) {
   setDisplaySettings();
   return false;
  });
  $('#savebannedphrases').click(function (e) {
   setBannedPhrases();
   return false;
  });
  $('#savecolumnsettings').click(function (e) {
   alert("Not implemented yet! This would call the POST columns API endpoint.");
   return false;
  });
  $('#deletealldata').click(function (e) {
   deleteAllData();
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
