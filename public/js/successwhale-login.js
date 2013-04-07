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

// jQuery bind to Submit button
// Authenticates, sets cookies and forwards or displays error message as appropriate
$('#login').submit(function() {
  var data = ko.toJSON(viewModel);
  var jqxhr = $.post("/apiproxy/authenticate", {username: viewModel.username(), password: viewModel.password()})
  .done(function(returnedData) {
    createCookie('sw_uid',returnedData.sw_uid,COOKIE_VALIDITY_DAYS);
    createCookie('secret',returnedData.secret,COOKIE_VALIDITY_DAYS);
    window.location = '/'
  })
  .fail(function(returnedData) {
    viewModel.errormessage((JSON.parse(returnedData.responseText)).error);
  })
  return false;
});