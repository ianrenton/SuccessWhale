// Globals
var API_SERVER = 'https://api.successwhale.com/v3';
var NARROW_SCREEN_WIDTH = 800;
var COOKIE_VALIDITY_DAYS = 365;

// Checks the user is logged in (via a cookie) - if not, punts them
// to the login page
function checkLoggedIn() {
  if (!readCookie('token')) {
    // No cookie, so no token
    window.location = '/';
  } else {
    viewModel.token(readCookie('token'));
    // Check token is valid
    var jqxhr = $.get(API_SERVER+'/checkauth', {token: viewModel.token()})
    .done(function(returnedData) {
      // If it's not a good auth, punt the user back to login
      if (!returnedData.authenticated) {
        showError('Failed to check authentication token', returnedData);
        eraseCookie('token'); // Cookie token no longer valid
        window.location = '/';
      }
    })
    .fail(function(returnedData) {
      // Server problems, back to login as this page will be pretty useless anyway
      showError('Failed to check authentication token', returnedData);
        eraseCookie('token'); // Cookie token may be invalid, clear it to prevent endless loops
      window.location = '/';
    });
  }
}
