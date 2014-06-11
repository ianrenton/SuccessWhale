// Viewmodel for SW
function SWUserViewModel() {
  var self = this;
  
  // SuccessWhale user API token
  self.token = ko.observable();
  
  // Display setting - number of columns per screen (honoured on wide screens only)
  self.colsPerScreen = ko.observable(3);
  // Display setting - user theme
  self.availableThemes = ko.observableArray(['default', 'inverted', 'minimal', 'METAL'])
  self.theme = ko.observable('default');
  // Display setting - highlight items newer than this (mins)
  self.highlightTime = ko.observable(0);
  // Display setting - show inline media
  self.inlineMedia = ko.observable(true);
  
  // Alternative login
  self.hasAltLogin = ko.observable(false);
  self.altLoginUsername = ko.observable('');
  self.altLoginPassword = ko.observable('');
  
  // Which SuccessWhale service accounts are available, and which of them should
  // be posted to by default
  self.accounts = ko.observableArray();
  // Possible sources list, used to build columns
  self.sources = ko.observableArray();
  // Columns list
  self.columns = ko.observableArray();
  // Banned phrases list
  self.bannedPhrases = ko.observable();
  
  // Using mobile view?
  self.mobileView = ko.observable(false);
  
  // Column management
  self.addColumn = function() {
    var column = {title: "New Column", sources: [], fullpath: ""};
    bindToColumn(column);
    self.columns.push(column);
  };
  self.removeColumn = function(item) {
    self.columns.remove(item);
  };
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel, document.getElementById("htmlTop")); // htmlTop is a hack to allow knockout to data-bind elements in the <head>

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
      viewModel.inlineMedia(returnedData.inlinemedia);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch display settings.', returnedData);
    });
}

// Set the user's display settings
function setDisplaySettings() {
  var jqxhr = $.post(API_SERVER+'/displaysettings', {token: viewModel.token(), colsperscreen: viewModel.colsPerScreen(), theme: viewModel.theme(), highlighttime: viewModel.highlightTime(), inlinemedia: viewModel.inlineMedia()})
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

// Fetch the list of accounts to post to
function getAccounts() {
  var jqxhr = $.get(API_SERVER+'/posttoaccounts', {token: viewModel.token()})
    .done(function(returnedData) {
      var accounts = returnedData.posttoaccounts
      var i = 0;
      for (; i<accounts.length; i++) {
        accounts[i].enabled = ko.observable(accounts[i].enabled);
      }
      viewModel.accounts.removeAll();
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
    
    var jqxhr = $.get(API_SERVER+'/altlogin', {token: viewModel.token()})
    .done(function(returnedData) {
      viewModel.hasAltLogin(returnedData.hasaltlogin);
      viewModel.altLoginUsername(returnedData.username);
      viewModel.altLoginPassword('');
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

// Fetch sources for use when building columns
function getSources() {
  var jqxhr = $.get(API_SERVER+'/sources', {token: viewModel.token()})
    .done(function(returnedData) {
      // Update the view model
      viewModel.columns.push.apply(viewModel.sources, returnedData.sources);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch sources list.', returnedData);
    });
}

// Fetch columns
function getColumns() {
  var jqxhr = $.get(API_SERVER+'/columns', {token: viewModel.token()})
    .done(function(returnedData) {
      // Wrap in observables
      var columns = returnedData.columns
      var i = 0;
      for (; i<columns.length; i++) {
        bindToColumn(columns[i]);
      }
      // Update the view model
      viewModel.columns.push.apply(viewModel.columns, columns);
      // Hide loading overlay.
      $('body').removeClass("loading");
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column list.', returnedData);
      // Hide loading overlay.
      $('body').removeClass("loading");
    });
}

// Utility function to bind observables to a new column
function bindToColumn(column) {
  column.sources = ko.observableArray(column.sources);
  // Temp var and func for holding a source that the user has selected for adding
  // from the column
  column.selectedNewSource = ko.observable();
  column.addSource = function() {
    this.sources.push(this.selectedNewSource());
  };
  // Temp var and func for holding a source that the user has selected for deletion
  // from the column
  column.selectedExistingSource = ko.observable();
  column.removeSource = function() {
    this.sources.remove(this.selectedExistingSource());
  };
}

// Sets the user's columns
function setColumns() {
  var jqxhr = $.post(API_SERVER+'/columns', {token: viewModel.token(), columns: ko.toJSON(viewModel.columns())})
    .done(function(returnedData) {
      showSuccess('Columns saved.', returnedData);
    })
    .fail(function(returnedData) {
      showError('Failed to save columns.', returnedData);
    });
}

// Create alternative login for the user
function createAltLogin() {
  var jqxhr = $.post(API_SERVER+'/createaltlogin', {token: viewModel.token(), username: viewModel.altLoginUsername(), password: viewModel.altLoginPassword()})
    .done(function(returnedData) {
      showSuccess('Alternative login created.', returnedData);
      getAccounts();
    })
    .fail(function(returnedData) {
      showError('Failed to create alternative login.', returnedData);
    });
}

// Delete alternative login for the user
function deleteAltLogin() {
  var ok = confirm('This will delete your SuccessWhale alternative login credentials. You will still be able to log in via Twitter and Facebook.\nClick OK if you wish to continue.');
  if (ok) {
    var jqxhr = $.post(API_SERVER+'/deletealtlogin', {token: viewModel.token()})
      .done(function(returnedData) {
        showSuccess('Alternative login data deleted.', returnedData);
        getAccounts();
      })
      .fail(function(returnedData) {
        showError('Failed to delete alternative login data.', returnedData);
      });
  }
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
  
  // Check window size
  viewModel.mobileView($(window).width() <= NARROW_SCREEN_WIDTH);
  
  // Main API calls to display data
  checkLoggedIn();
  getAccounts();
  getDisplaySettings();
  getBannedPhrases();
  getSources();
  getColumns();
  
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
    var jqxhr = $.get(API_SERVER+'/authwithfacebook', {callback_url: location.origin+'/facebookcallback'})
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

// Recalculate the mobile view logic on window resize
$( window ).resize(function() {
  viewModel.mobileView($(window).width() <= NARROW_SCREEN_WIDTH);
});

