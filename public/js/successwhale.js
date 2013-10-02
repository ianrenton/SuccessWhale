// Viewmodel for SW
function SWUserViewModel() {
  this.colsPerScreen = ko.observable();
  this.postToAccounts = ko.observableArray();
  this.columns = ko.observableArray();
}

// Checks the user is logged in (via a cookie) - if not, punts them
// to the login page
function checkLoggedIn() {
  if (!readCookie('token')) {
    window.location = '/login';
  }
}

// Get the user's display settings
function getDisplaySettings() {
  var jqxhr = $.get("/apiproxy/displaysettings", {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.colsPerScreen(returnedData.colsperscreen);
    })
    .fail(function(returnedData) {
      alert(returnedData.responseText.error);
    });
}

// Fetch and display the list of accounts to post to
function displayPostToAccounts() {
  var jqxhr = $.get("/apiproxy/posttoaccounts", {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.postToAccounts.push.apply(viewModel.postToAccounts, returnedData.posttoaccounts);
    })
    .fail(function(returnedData) {
      alert(returnedData.responseText.error);
    });
}

// Fetch and display column info
function displayColumns() {
  var jqxhr = $.get("/apiproxy/columns", {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.columns.push.apply(viewModel.columns, returnedData.columns);
    })
    .fail(function(returnedData) {
      alert(returnedData.responseText.error);
    });
}



// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);

// Automatic stuff on page load
checkLoggedIn();
getDisplaySettings();
displayPostToAccounts();
displayColumns();
