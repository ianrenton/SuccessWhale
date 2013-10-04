// Viewmodel for SW
function SWUserViewModel() {
  this.colsPerScreen = ko.observable();
  this.postToAccounts = ko.observableArray();
  this.columns = ko.observableArray();
}

// Activate knockout.js
viewModel = new SWUserViewModel();
ko.applyBindings(viewModel);

// Checks the user is logged in (via a cookie) - if not, punts them
// to the login page
function checkLoggedIn() {
  if (!readCookie('token')) {
    window.location = '/login';
  }
}

// Turn an item content into proper HTML
function makeHTML(content) {
  var html="";
  var i=0;
  var startIndex = 0;
  var endIndex = 0;
  for (;i<content.links.length; i++)
  {
    if (typeof content.links[i].indices !== 'undefined') {
      startIndex = content.links[i].indices[0];
      html = html + content.text.substring(endIndex, startIndex);
      endIndex = content.links[i].indices[1];
      html = html + '<a href="' + content.links[i].url + '">' + content.links[i].title + '</a>';
    }
  }
  html = html + content.text.substring(endIndex, content.text.length);
  return html;
}

// Load feed for a single column
function loadFeedForColumn(j) {
  var jqxhr = $.get("/apiproxy/feed", {sources: viewModel.columns()[j].fullpath, token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.columns()[j].items.removeAll();
      viewModel.columns()[j].items.push.apply(viewModel.columns()[j].items, returnedData.items); 
    })
    .fail(function(returnedData) {
      alert("Failed to fetch column");
    });
}

// Fetch and display column content
function refreshColumns() {
  var j = 0;
  for (; j < viewModel.columns().length; j++) {
    loadFeedForColumn(j);
  }
} 

// Get the user's display settings
function getDisplaySettings() {
  var jqxhr = $.get("/apiproxy/displaysettings", {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.colsPerScreen(returnedData.colsperscreen);
    })
    .fail(function(returnedData) {
      alert("Failed to fetch display settings");
    });
}

// Fetch and display the list of accounts to post to
function displayPostToAccounts() {
  var jqxhr = $.get("/apiproxy/posttoaccounts", {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.postToAccounts.push.apply(viewModel.postToAccounts, returnedData.posttoaccounts);
    })
    .fail(function(returnedData) {
      alert("Failed to fetch account list");
    });
}

// Fetch and display columns
function displayColumns() {
  var jqxhr = $.get("/apiproxy/columns", {token: readCookie('token')})
    .done(function(returnedData) {
      var cols = returnedData.columns;
      
      // Add a dummy observableArray to hold items later
      var i = 0;
      for (; i<cols.length; i++) {
        cols[i].items = ko.observableArray();
      }
      
      // Update the view model
      viewModel.columns.push.apply(viewModel.columns, cols);
      
      // Refresh all columns to pull in items
      refreshColumns();
    })
    .fail(function(returnedData) {
      alert("Failed to fetch column list");
    });
}


// Automatic stuff on page load
checkLoggedIn();
getDisplaySettings();
displayPostToAccounts();
displayColumns();

// Focus post entry box
document.getElementById('postentry').focus();
// Refresh every 5 minutes
setInterval( function() { refreshColumns(); }, 300000);
