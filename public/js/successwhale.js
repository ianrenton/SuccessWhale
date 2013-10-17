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

// Shows an error box for a set time, with the supplied html as content
function showError(html) {
  $('#errorbox').html(html);
  $('#errorbox').show('slow', function hideLater() {
    setTimeout(function() {
      $('#errorbox').hide('slow');
    }, 5000);
  });
}

// Turn an item's text into proper HTML
function makeItemTextHTML(content) {
  return linkify_entities(content);
}

// Turn an item's 'from' structures into proper text
function makeFromUserText(content, service) {
  var html='';
  if (content.fromusername) {
    html += content.fromusername;
    if (content.fromuser) {
      html += ' (';
      if (service === 'twitter')
      {
        html += '@';
      }
      html += content.fromuser + ')';
    }
  }
  else if (content.fromuser) {
    html += content.fromuser;
  }
  return html;
}

// Turn an item's 'from' structures into the URL of the 'from' user's profile
function makeFromUserLink(content, service) {
  var url='';
  if (service === 'twitter') {
    url = 'http://twitter.com/'+content.fromuser;
  }
  else if (service === 'facebook') {
    url = 'http://facebook.com/'+content.fromuserid;
  }
  return url;
}

// Turn an item's metadata (replies, comments...) structures into proper text
function makeMetadataText(content) {
  var html='';
  if (content.numreplied > 0) {
    html += content.numreplied + ' replies ';
  }
  if (content.numretweeted > 0) {
    html += content.numretweeted + ' retweets ';
  }
  if (content.numfavourited > 0) {
    html += content.numfavourited + ' favourites ';
  }
  if (content.numcomments > 0) {
    html += content.numcomments + ' comments ';
  }
  if (content.numlikes > 0) {
    html += content.numlikes + ' likes ';
  }
  return html;
}

// Load feed for a single column
function loadFeedForColumn(j) {
  var jqxhr = $.get('/apiproxy/feed', {sources: viewModel.columns()[j].fullpath, token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.columns()[j].items.removeAll();
      viewModel.columns()[j].items.push.apply(viewModel.columns()[j].items, returnedData.items); 
    })
    .fail(function(returnedData) {
      showError('Failed to fetch column "' + viewModel.columns()[j].title + '"');
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
  var jqxhr = $.get('/apiproxy/displaysettings', {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.colsPerScreen(returnedData.colsperscreen);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch display settings');
    });
}

// Fetch and display the list of accounts to post to
function displayPostToAccounts() {
  var jqxhr = $.get('/apiproxy/posttoaccounts', {token: readCookie('token')})
    .done(function(returnedData) {
      viewModel.postToAccounts.push.apply(viewModel.postToAccounts, returnedData.posttoaccounts);
    })
    .fail(function(returnedData) {
      showError('Failed to fetch account list');
    });
}

// Fetch and display columns
function displayColumns() {
  var jqxhr = $.get('/apiproxy/columns', {token: readCookie('token')})
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
      showError('Failed to fetch column list');
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
