// Globals
var API_SERVER = 'http://api.successwhale.com/v3';
var COOKIE_VALIDITY_DAYS = 365;

// Viewmodel for SW login form
function SWUserViewModel() {
  this.username = ko.observable("");
  this.password = ko.observable("");
  this.errormessage = ko.observable("");
}

// Checks the user is not logged in (via a cookie) - if they are, punts them
// to the main client page as they do not need to log in again
function checkLoggedOut() {
  if (readCookie('token')) {
    window.location = '/client';
  }
}

// jQuery bind to Submit button
// Authenticates, sets cookies and forwards or displays error message as appropriate
$('#login').submit(function() {
  var jqxhr = $.post(API_SERVER+'/authenticate', {username: viewModel.username(), password: viewModel.password()})
  .done(function(returnedData) {
    // If we were displaying an error message, hide it so the user isn't confused
    viewModel.errormessage('');
    $('#loginerrorbox').hide('fast');
    // Set cookie and advance to main interface
    createCookie('token',returnedData.token,COOKIE_VALIDITY_DAYS);
    window.location = '/client';
  })
  .fail(function(returnedData) {
    // Display error box
    viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
    $('#loginerrorbox').show('fast');
  });
  return false;
});

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);

// Automatic stuff on page load
checkLoggedOut();
