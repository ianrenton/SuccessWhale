<?php

require_once('../common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

if (LINKEDIN_ENABLED) {
	
	try {
	  // display constants
	  $API_CONFIG = array(
	    'appKey'       => LINKEDIN_APP_KEY,
		  'appSecret'    => LINKEDIN_SECRET_KEY,
		  'callbackUrl'  => LINKEDIN_CALLBACK
	  );
	  define('CONNECTION_COUNT', 20);
	  define('PORT_HTTP', '80');
	  define('PORT_HTTP_SSL', '443');
	  define('UPDATE_COUNT', 10);

	  /**
	   * Handle user initiated LinkedIn connection, create the LinkedIn object.
	   */
    
	  // check for the correct http protocol (i.e. is this script being served via http or https)
	  if($_SERVER['HTTPS'] == 'on') {
	    $protocol = 'https';
	  } else {
	    $protocol = 'http';
	  }
  
	  // set the callback url
	  $API_CONFIG['callbackUrl'] = $protocol . '://' . $_SERVER['SERVER_NAME'] . ((($_SERVER['SERVER_PORT'] != PORT_HTTP) || ($_SERVER['SERVER_PORT'] != PORT_HTTP_SSL)) ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['PHP_SELF'] . '?' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1';
	  $OBJ_linkedin = new LinkedIn($API_CONFIG);
  
	  // check for response from LinkedIn
	  $_GET[LINKEDIN::_GET_RESPONSE] = (isset($_GET[LINKEDIN::_GET_RESPONSE])) ? $_GET[LINKEDIN::_GET_RESPONSE] : '';
	  if(!$_GET[LINKEDIN::_GET_RESPONSE]) {
	    // LinkedIn hasn't sent us a response, the user is initiating the connection
    
	    // send a request for a LinkedIn access token
	    $response = $OBJ_linkedin->retrieveTokenRequest();
	    if($response['success'] === TRUE) {
	      // store the request token
	      $_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];
      
	      // redirect the user to the LinkedIn authentication/authorisation page to initiate validation.
	      header('Location: ' . LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token']);
	    } else {
	      // bad token request
	      echo "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
	    }
	  } else {
	    // LinkedIn has sent a response, user has granted permission, take the temp access token, the user's secret and the verifier to request the user's real secret key
	    $response = $OBJ_linkedin->retrieveTokenAccess($_SESSION['oauth']['linkedin']['request']['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
    
		if($response['success'] === TRUE) {
	        // the request went through without an error, cache the user's tokens
		    $uidReturn = $OBJ_linkedin->profile('~:(id)');
		    $nameReturn = $OBJ_linkedin->profile('~:(first-name,last-name)');
			$uidBlob = $linkedin->xmlToArray($uidReturn['linkedin'], true);
			$nameBlob = $linkedin->xmlToArray($nameReturn['linkedin'], true);
			$uid = $nameBlob['person']['children']['id']['content'];
			$name = $nameBlob['person']['children']['first-name']['content'] . " " . $nameBlob['person']['children']['last-name']['content'];
			
			$query = "SELECT COUNT(*) FROM linkedin_users WHERE uid='" . mysql_real_escape_string($uid) . "';";
	        $result = mysql_query($query) or die (mysql_error());
	        $row = mysql_fetch_assoc($result);
	        if ($row['COUNT(*)'] == 0) {
	            // This LinkedIn account is new to SuccessWhale
	            if (!isset($_SESSION['sw_uid'])) {
	                // No user is logged in, so make a new one.
	                logInUser(addSWUser());
	            }
	            // The user is now logged in, so record their LinkedIn details alongside
	            // their other details.
	            $query="INSERT INTO linkedin_users (sw_uid,uid,username,access_token)
	                    VALUES ('" . mysql_real_escape_string($_SESSION['sw_uid']) . "', '".
	                                mysql_real_escape_string($uid)."','".
	                                mysql_real_escape_string($name)."','".
	                                mysql_real_escape_string(serialize($response['linkedin']))."');";
	            mysql_query($query) or die(mysql_error());
	        } else {
	            // This LinkedIn account has been seen before, so update details.
	            $query = "UPDATE linkedin_users SET username='" . mysql_real_escape_string($name) . 
	                                                "', access_token='" . mysql_real_escape_string(serialize($response['linkedin'])) . 
	                                                "' WHERE uid='" . mysql_real_escape_string($uid) . "';";
	            mysql_query($query) or die (mysql_error());
	            // Now log in the appropriate user to SuccessWhale
	            $query = "SELECT sw_uid FROM linkedin_users WHERE uid='" . $uid . "';";
	            $result = mysql_query($query) or die (mysql_error());
	            $row = mysql_fetch_assoc($result);
	            logInUser($row['sw_uid']);
	        }
            
	        // redirect the user back to SuccessWhale
	        header('Location: /index.php');
	    } else {
	        // bad token access
	        echo "Access token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
	    }
	  }

	} catch(LinkedInException $e) {
	  // exception raised by library call
	  echo $e->getMessage();
	}

} else {
    // LinkedIn is disabled
    if (DEBUG) {
        die("Attempted to use a LinkedIn callback when LinkedIn integration is disabled.");
    } else {
        header('Location: ../index.php');
    }
}

mysql_close();

?>
