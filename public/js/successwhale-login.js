// Globals
var API_SERVER = 'https://successwhale-api.herokuapp.com/v3';
var COOKIE_VALIDITY_DAYS = 365;

// Viewmodel for SW login form
function SWUserViewModel() {
  this.username = ko.observable("");
  this.password = ko.observable("");
  this.errormessage = ko.observable("");
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);


// Checks the user is not logged in (via a cookie) - if they are, punts them
// to the main client page as they do not need to log in again
function checkLoggedOut() {
  if (readCookie('token')) {
    window.location = '/';
  }
}

// Checks server status and shows it on-screen
function checkServerStatus() {
  var jqxhr = $.get(API_SERVER+'/status')
  .done(function(returnedData) {
    $('#serverstatus').html('<p class="statussuccess">Connected to secure server, version ' + returnedData.version + '</p>');
  })
  .fail(function(returnedData) {
    $('#serverstatus').html('<p class="statusfailure">Could not connect to SuccessWhale server.<br/>Please try again later.</p>');
  });
  return false;
}

// jQuery bind to Submit button
// Authenticates, sets cookies and forwards or displays error message as appropriate
$('#login').submit(function() {
  // Set the values of the observables based on the content of the
  // login form. This kind of defeats the point of observables,
  // but is required to handle the case where the browser auto-
  // fills the login boxes but does not throw a change event to
  // the JS.
  // See https://github.com/knockout/knockout/issues/648
  viewModel.username($('#username').val());
  viewModel.password($('#password').val());
  
  // Now proceed with the API call.
  var jqxhr = $.post(API_SERVER+'/authenticate', {username: viewModel.username(), password: viewModel.password()})
  .done(function(returnedData) {
    // If we were displaying an error message, hide it so the user isn't confused
    viewModel.errormessage('');
    $('#loginerrorbox').hide('fast');
    // Set cookie and advance to main interface
    createCookie('token',returnedData.token,COOKIE_VALIDITY_DAYS);
    window.location = '/';
  })
  .fail(function(returnedData) {
    // Display error box
    viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
    $('#loginerrorbox').show('fast');
  });
  return false;
});


// Automatic stuff on page load
$(document).ready(function() {
  checkLoggedOut();
  checkServerStatus();
  
  // Bind "Log in with Twitter" button
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
  
  // Bind "Log in with Facebook" button
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
