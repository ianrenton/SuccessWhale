// Get GET params
function getSearchParameters() {
      var prmstr = window.location.search.substr(1);
      return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
}

function transformToAssocArray( prmstr ) {
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        params[tmparr[0]] = tmparr[1];
    }
    return params;
}
var params = getSearchParameters();


// Automatically submit callback to SW and reload
$(document).ready(function() {

  // If Twitter access was denied, punt back to the main interface
  if ('denied' in params) {
    window.location = '/';
  }

  var paramsToPassOn = {swsessionkey: params.swsessionkey, oauth_token: params.oauth_token, oauth_verifier: params.oauth_verifier};
  // Get token from cookie if we're already logged in, so the API binds the new Twitter
  // account to the right user
  if (readCookie('token')) {
    paramsToPassOn['token'] = readCookie('token');
  }
  
  var jqxhr = $.get(API_SERVER+'/authwithtwitter', paramsToPassOn)
    .done(function(returnedData) {
      // Set cookie and advance to main interface
      createCookie('token',returnedData.token,COOKIE_VALIDITY_DAYS);
      window.location = '/';
    })
    .fail(function(returnedData) {
      window.location = '/';
    });
  
});
