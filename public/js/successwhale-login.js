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
// to the main page as they do not need to log in again
function checkLoggedOut() {
  if (readCookie('token')) {
    window.location = '/';
  }
}

// jQuery bind to Submit button
// Authenticates, sets cookies and forwards or displays error message as appropriate
$('#login').submit(function() {
  var data = ko.toJSON(viewModel);
  var jqxhr = $.post(API_SERVER+'/authenticate', {username: viewModel.username(), password: viewModel.password()})
  .done(function(returnedData) {
    createCookie('token',returnedData.token,COOKIE_VALIDITY_DAYS);
    window.location = '/';
  })
  .fail(function(returnedData) {
    viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
  });
  return false;
});

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);

// Automatic stuff on page load
checkLoggedOut();
