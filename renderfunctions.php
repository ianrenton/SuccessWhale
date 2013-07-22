<?php
date_default_timezone_set('UTC');

include('libraries/simple_html_dom.php');


// @tikakino's Twixt/URL expansion code
function grab($site,$id) {
	$fp = fsockopen($site, 80, $errno, $errstr, 30);
	if (!$fp) {
		die("$errstr ($errno)<br />\n");
	} else {
		$result = array();
		$out = "GET /$id HTTP/1.1\r\n";
		$out .= "Host: $site\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite($fp, $out);
		while (!feof($fp)) {
			$result[] = fgets($fp, 4096);
		}
		fclose($fp);
		return $result;
	}
}

// Generates an individual tweet (one of timeline, mentions or directs) depending on
// the JSON and bool that's passed to it.  $isDM required because while normal tweets have
// "user", DMs have "sender".  $isMention is required because we only show the Report button
// on mentions, if you've followed the person already we assume non-spammer.
function generateTweetItem($data, $isMention, $isDM, $isConvo, $thisUser, $blocklist) {
	
	$userString = "user";
	if ($isDM == true) $userString = "sender";
	
	$time = strtotime($data["created_at"])+$_SESSION['utcOffset'];
			
	// We do this first so we know how many @users were linked up, if >0
	// then we can do a reply-all.  User counting happens in parseLinks(),
	// so we set this to zero before we call it.
	$numusers = 0;

    // Spot truncated RTs and expand them
    if (isset($data["retweeted_status"])) {
        $avatar = '<a><img class="avatar" src="' . $data["retweeted_status"][$userString]["profile_image_url"] . '" alt="' . $data["retweeted_status"][$userString]["name"] . '" title="' . $data["retweeted_status"][$userString]["name"] . '" border="0" width="48" height="48"></a>';
        $nameField = $data["retweeted_status"][$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data["retweeted_status"][$userString]["screen_name"] . '" target="_blank">@' . $data["retweeted_status"][$userString]["screen_name"] . '</a>)<br/>RT by ' . $data[$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data[$userString]["screen_name"] . '" target="_blank">@' . $data[$userString]["screen_name"] . '</a>)';
        $tweetbody = /*"RT @" . $data["retweeted_status"][$userString]["screen_name"] . " " .*/ $data["retweeted_status"]["text"];
        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
	    // A bit of processing overhead, but it stops unwelcome URLs in tweets
	    // evading the blocker by using a URL shortener.
	    $tweetbody = parseLinks($tweetbody, $numusers);
		$isInReplyToAnotherTweet = ($data["retweeted_status"]["in_reply_to_status_id"] > 0);
		$thisTweetID = $data["retweeted_status"]["id_str"];
        $operations = makeTwitterOperations($data["retweeted_status"][$userString]["screen_name"], $data["retweeted_status"]["text"], $thisUser, $data["retweeted_status"]["id_str"], $isMention, $isDM, $isConvo, $i, $data["retweeted_status"]["in_reply_to_screen_name"], $data["retweeted_status"]["in_reply_to_status_id"], $numusers);
    } else {
        $avatar = '<a><img class="avatar" src="' . $data[$userString]["profile_image_url"] . '" alt="' . $data[$userString]["name"] . '" title="' . $data[$userString]["name"] . '" border="0" width="48" height="48"></a>';
        if ($isDM && ($data["sender_screen_name"] == $thisUser)) {
            $nameField = 'Sent to ' . $data["recipient_screen_name"] . ' (<a href="http://www.twitter.com/' . $data["recipient_screen_name"] . '" target="_blank">@' . $data["recipient_screen_name"] . '</a>)';
        } else {
            $nameField = $data[$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data[$userString]["screen_name"] . '" target="_blank">@' . $data[$userString]["screen_name"] . '</a>)';
        }
        $tweetbody = $data["text"];
        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
	    // A bit of processing overhead, but it stops unwelcome URLs in tweets
	    // evading the blocker by using a URL shortener.
	    $tweetbody = parseLinks($tweetbody, $numusers);
		$isInReplyToAnotherTweet = ($data["in_reply_to_status_id"] > 0);
		$thisTweetID = $data["id_str"];
	    $operations = makeTwitterOperations($data[$userString]["screen_name"], $data["text"], $thisUser, $data["id_str"], $isMention, $isDM, $isConvo, $i, $data["in_reply_to_screen_name"], $data["in_reply_to_status_id"], $numusers);
    }	

	// Convo
	$convo = "";
	if (($isInReplyToAnotherTweet) && (!$isConvo)) {
		$convo = '<br/><span class="convolikearea"><span style="display:inline-block;"><a href="convo.php?service=twitter&thisUser=' . $thisUser . '&status=' . $thisTweetID . '" class="convobutton"><img src="images/convo.png" alt="In reply to..." title="In reply to..."></a></span></span>';
	}
		
	// Check blocklist
	$match = false;
	$tweetbodyLowerCase = strtolower($tweetbody);
	foreach ($blocklist as $blockstring) {
		if ($blockstring != '') {
			$pos = strpos($tweetbodyLowerCase, $blockstring);
			if ($pos !== false) {
				$match = true;
			}
		}
	}		
	
	// Display tweet if it didn't match, of if it's part of a convo
	// (Convos explicitly request a thread, it would look weird if
	// we hid parts of it.)
	if ((!$match) || ($isConvo)) {
	
    	if ($isConvo) {
		    $content .= '<div class="item twitterstatus convo">';
		} else {
		    $content .= '<div class="item twitterstatus' . (isRecent(strtotime($data["created_at"])+$_SESSION['utcOffset'])?' recent':'') . '">';
		}
		$content .= '<div class="text">';
		if (strtotime($data["created_at"]) == 0) {
            $content .= '<div class="metatext"><span class="name">Protected or deleted tweet.</span></div>';
		} else {
            $content .= '<table><tr><td>';
            $content .= $avatar;
            $content .= $convo;
            $content .= '</td>';
            $content .= '<td class="tweettextcell"><span class="tweettext wraptext">';
            $content .= $tweetbody;
            $content .= '</span>';
            $content .= '<span class="metatext wraptext">';
            $content .= $nameField . '<br/>';
            $content .= makeFriendlyTime(strtotime($data["created_at"])+$_SESSION['utcOffset'], $_SESSION['midnightYesterday'], $_SESSION['oneWeekAgo'], $_SESSION['janFirst']) . '<br/>';
            $content .= $operations;
            $content .= '</span>';
            $content .= '</td></tr></table>';
		}
		$content .= '</div>';
		$content .= '</div><div class="clear"></div>';
	}
	$item = array('time' => $time, 'html' => $content);
	return $item;
}


// Generates an individual facebook status
function generateFBStatusItem($data, $isNotifications, $isComment, $thisUser, $blocklist) {
	
	// Get the status body based on what the data contains
	if ($isNotifications) {
		$statusbody = $data["title_html"] . '<br/>' . parseLinks($data["body_html"], $ignore);
	    $avatar = '<a><img class="avatar" src="http://graph.facebook.com/' .$data["sender_id"] . '/picture" border="0" width="48" height="48"></a>';
	    $time = $data["created_time"]+$_SESSION['utcOffset'];
		$renderCommentsLikes = false;
		
		$facebook = $_SESSION['facebooks'][$thisUser];
	
		// Mark as read
		if (($facebook != null) && ($data['notification_id'] != null) && ($data['is_unread'] == true)) {
	       	try {
				$facebook->api("notif_" . $data['recipient_id'] . "_" . $data['notification_id'] . "?unread=0", "POST", array());
			} catch (Exception $e) {
			}
		}
		
		// We don't need the data for this notification anymore, from here on we want the
		// data for the item that the notification was referring to. So request that from
		// the API.
		if (($facebook != null) && ($data['object_id'] != null)) {
	        try {
				$data = $facebook->api("/" . $data['object_id']);
				$renderCommentsLikes = true;
			} catch (Exception $e) {
			}
		}
	} elseif ($isComment) {
	    $statusbody = parseLinks($data["message"], $ignore);
	    $avatar = '<a><img class="avatar" src="http://graph.facebook.com/' .$data["from"]["id"] . '/picture" border="0" width="48" height="48"></a>';
	    $time = strtotime($data["created_time"])+$_SESSION['utcOffset'];
	} else {
        if ($data["type"] == "status") {
	        $statusbody = parseLinks($data["message"],$ignore);
	        if (!empty($data["to"])) {
	            $statusbody = "&#9654; " . $data["to"]["data"][0]["name"] . "<br/>" . $statusbody;
	        }
	    } elseif ($data["type"] == "link") {
			//var_dump($data); echo("<br><br>");
			if (isset($data['name'])) {
				$statusbody = '<a href="' . $data["link"] . '">' . parseLinks($data["name"], $ignore) . '</a>';
			} else {
				$statusbody = parseLinks($data["link"], $ignore);
			}
			if (isset($data['description'])) {
	        	$statusbody .= '<br/>' . parseLinks($data["description"], $ignore);
			}
			if (isset($data['message'])) {
	        	$statusbody .= '<br/>' . parseLinks($data["message"], $ignore);
			}
	    } elseif ($data["type"] == "photo") {
	        $statusbody = '<a href="' . $data["link"] . '">' . $data["name"] . '</a><br/>' . parseLinks($data["message"],$ignore);
	    } elseif ($data["type"] == "video") {
	        $statusbody = '<a href="' . $data["link"] . '">' . $data["name"] . '</a><br/>' . parseLinks($data["message"],$ignore);
	    } else {
	        $statusbody = "";
	    }
	    $avatar = '<img class="avatar" src="http://graph.facebook.com/' .$data["from"]["id"] . '/picture" alt="' . $data["from"]["name"] . '" title="' . $data["from"]["name"] . '" border="0" width="48" height="48">';
	    $time = strtotime($data["created_time"])+$_SESSION['utcOffset'];
		$renderCommentsLikes = true;
	}

	$commentsLikes = "";
	$match = false;
	if (!$isComment) {
		if ($renderCommentsLikes) {
			// Check comments & likes
			$commentCount = 0;
			$likeCount = 0;
			if (isset($data['comments'])) {
				$commentCount = count($data['comments']['data']);
			}
			if (isset($data['likes'])) {
				$likeCount = count($data['likes']['data']);
			}
			$commentsLikes = '<br/><span class="convolikearea"><span style="display:inline-block;"><a href="convo.php?service=facebook&thisUser=' . $thisUser . '&status=' . $data['id'] . '" class="convobutton"><img src="images/convo.png" alt="Comments" title="Comments">' . $commentCount . '</a></span> <span style="display:inline-block;"><a class="likebutton" href="actions.php?likeID=' . $data['id'] . '&thisUser=' . $thisUser . '"><img src="images/like.png" alt="Likes" title="Likes">' . $likeCount . '</a></span></span>';
		}
		
		// Check blocklist
		$statusbodyLowerCase = strtolower($statusbody);
		foreach ($blocklist as $blockstring) {
			if ($blockstring != '') {
				$pos = strpos($statusbodyLowerCase, $blockstring);
				if ($pos !== false) {
					$match = true;
				}
			}
		}
	}
	
	// Display tweet if it didn't match, or (as blocks are only checked for
	// non-comment threads) if it's a comment thread.
	if (!$match) {
	    if ($isComment) {
	        $content .= '<div class="item facebookstatus convo">';
	    } else {
		    $content .= '<div class="item facebookstatus' . ((isRecent($time) && (!$isComment))?' recent':'') . '">';
		}
		$content .= '<div class="text">';
        $content .= '<table><tr><td>';
        $content .= $avatar;
		$content .= $commentsLikes;
        $content .= '</td>';
        $content .= '<td class="tweettextcell"><span class="tweettext wraptext">';
        $content .= $statusbody;
        $content .= '</span>';
        $content .= '<span class="metatext wraptext">';
        if (!$isNotifications) {
            $content .= $data["from"]["name"] . "<br/>";
        }
        $content .= makeFriendlyTime($time, $_SESSION['midnightYesterday'], $_SESSION['oneWeekAgo'], $_SESSION['janFirst']);
        if (!$isNotifications) {
            $content .= makeFBOperations($data["id"], $data["actions"][0]["link"], $data["actions"][1]["link"]);
        }
        $content .= '</span>';
        $content .= '</td></tr></table>';
		$content .= '</div>';
		$content .= '</div><div class="clear"></div>';
	}
	$item = array('time' => $time, 'html' => $content);
	return $item;
}


// Generates an individual LinkedIn post
function generateLinkedInItem($data, $blocklist) {
	
	$time = strtotime($data["timestamp"]['content'])+$_SESSION['utcOffset'];
			
	// We do this first so we know how many @users were linked up, if >0
	// then we can do a reply-all.  User counting happens in parseLinks(),
	// so we set this to zero before we call it.
	$numusers = 0;
	
	var_dump($data);
	/*$avatar = '<a><img class="avatar" src="' . $data[$userString]["profile_image_url"] . '" alt="' . $data[$userString]["name"] . '" title="' . $data[$userString]["name"] . '" border="0" width="48" height="48"></a>';
        

    // Spot truncated RTs and expand them
    if (isset($data["retweeted_status"])) {
        $avatar = '<a><img class="avatar" src="' . $data["retweeted_status"][$userString]["profile_image_url"] . '" alt="' . $data["retweeted_status"][$userString]["name"] . '" title="' . $data["retweeted_status"][$userString]["name"] . '" border="0" width="48" height="48"></a>';
        $nameField = $data["retweeted_status"][$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data["retweeted_status"][$userString]["screen_name"] . '" target="_blank">@' . $data["retweeted_status"][$userString]["screen_name"] . '</a>)<br/>RT by ' . $data[$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data[$userString]["screen_name"] . '" target="_blank">@' . $data[$userString]["screen_name"] . '</a>)';
        $tweetbody = /*"RT @" . $data["retweeted_status"][$userString]["screen_name"] . " " .*//* $data["retweeted_status"]["text"];
        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
	    // A bit of processing overhead, but it stops unwelcome URLs in tweets
	    // evading the blocker by using a URL shortener.
	    $tweetbody = parseLinks($tweetbody, $numusers);
		$isInReplyToAnotherTweet = ($data["retweeted_status"]["in_reply_to_status_id"] > 0);
		$thisTweetID = $data["retweeted_status"]["id_str"];
        $operations = makeTwitterOperations($data["retweeted_status"][$userString]["screen_name"], $data["retweeted_status"]["text"], $thisUser, $data["retweeted_status"]["id_str"], $isMention, $isDM, $isConvo, $i, $data["retweeted_status"]["in_reply_to_screen_name"], $data["retweeted_status"]["in_reply_to_status_id"], $numusers);
    } else {
        $avatar = '<a><img class="avatar" src="' . $data[$userString]["profile_image_url"] . '" alt="' . $data[$userString]["name"] . '" title="' . $data[$userString]["name"] . '" border="0" width="48" height="48"></a>';
        if ($isDM && ($data["sender_screen_name"] == $thisUser)) {
            $nameField = 'Sent to ' . $data["recipient_screen_name"] . ' (<a href="http://www.twitter.com/' . $data["recipient_screen_name"] . '" target="_blank">@' . $data["recipient_screen_name"] . '</a>)';
        } else {
            $nameField = $data[$userString]["name"] . ' (<a href="http://www.twitter.com/' . $data[$userString]["screen_name"] . '" target="_blank">@' . $data[$userString]["screen_name"] . '</a>)';
        }
        $tweetbody = $data["text"];
        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
	    // A bit of processing overhead, but it stops unwelcome URLs in tweets
	    // evading the blocker by using a URL shortener.
	    $tweetbody = parseLinks($tweetbody, $numusers);
		$isInReplyToAnotherTweet = ($data["in_reply_to_status_id"] > 0);
		$thisTweetID = $data["id_str"];
	    $operations = makeTwitterOperations($data[$userString]["screen_name"], $data["text"], $thisUser, $data["id_str"], $isMention, $isDM, $isConvo, $i, $data["in_reply_to_screen_name"], $data["in_reply_to_status_id"], $numusers);
    }	

	// Convo
	$convo = "";
	if (($isInReplyToAnotherTweet) && (!$isConvo)) {
		$convo = '<br/><span class="convolikearea"><span style="display:inline-block;"><a href="convo.php?service=twitter&thisUser=' . $thisUser . '&status=' . $thisTweetID . '" class="convobutton"><img src="images/convo.png" alt="In reply to..." title="In reply to..."></a></span></span>';
	}
		
	// Check blocklist
	$match = false;
	$tweetbodyLowerCase = strtolower($tweetbody);
	foreach ($blocklist as $blockstring) {
		if ($blockstring != '') {
			$pos = strpos($tweetbodyLowerCase, $blockstring);
			if ($pos !== false) {
				$match = true;
			}
		}
	}		
	
	// Display tweet if it didn't match, of if it's part of a convo
	// (Convos explicitly request a thread, it would look weird if
	// we hid parts of it.)
	if ((!$match) || ($isConvo)) {
	
    	if ($isConvo) {
		    $content .= '<div class="item twitterstatus convo">';
		} else {
		    $content .= '<div class="item twitterstatus' . (isRecent(strtotime($data["created_at"])+$_SESSION['utcOffset'])?' recent':'') . '">';
		}
		$content .= '<div class="text">';
		if (strtotime($data["created_at"]) == 0) {
            $content .= '<div class="metatext"><span class="name">Protected or deleted tweet.</span></div>';
		} else {
            $content .= '<table><tr><td>';
            $content .= $avatar;
            $content .= $convo;
            $content .= '</td>';
            $content .= '<td class="tweettextcell"><span class="tweettext wraptext">';
            $content .= $tweetbody;
            $content .= '</span>';
            $content .= '<span class="metatext wraptext">';
            $content .= $nameField . '<br/>';
            $content .= makeFriendlyTime(strtotime($data["created_at"])+$_SESSION['utcOffset'], $_SESSION['midnightYesterday'], $_SESSION['oneWeekAgo'], $_SESSION['janFirst']) . '<br/>';
            $content .= $operations;
            $content .= '</span>';
            $content .= '</td></tr></table>';
		}
		$content .= '</div>';
		$content .= '</div><div class="clear"></div>';
	}*/
	$content = "";
	$item = array('time' => $time, 'html' => $content);
	return $item;
}



// Turns a UNIX time into a friendly time string, form depending on how
// far in the past the given time is.
function makeFriendlyTime($time, $midnightYesterday, $oneWeekAgo, $janFirst) {
	$timeString = "";
	$timeAgo = time() - $time;
	if ($timeAgo < 2) {
		$timeString = "1 second ago";
	} else if ($timeAgo < 60) {
		$timeString = $timeAgo . " seconds ago";
	} else if ($timeAgo < 120) {
		$timeString = "1 minute ago";
	} else if ($timeAgo < 3600) {
		$timeString = floor($timeAgo/60) . " minutes ago";
	} else if ($timeAgo < 7200) {
		$timeString = "1 hour ago";
	} else if ($timeAgo < 86400) {
		$timeString = floor($timeAgo/3600)  . " hours ago";
	} else if ($time >= $midnightYesterday) {
		$timeString = "Yesterday";
	} else if ($time >= $oneWeekAgo) {
		$timeString = date("l", $time);
	} else if ($time >= $janFirst) {
		$timeString = date("D M j", $time);
	} else {
		$timeString = date("M j Y", $time);
	}
	return $timeString;
}


// Is the time within the last highlighttime minutes?
function isRecent($time) {
    if ((!isset($_SESSION['highlighttime'])) || ($_SESSION['highlighttime'] == 0)) {
        return false;
    } else {
        $timeAgo = time() - $time;
        return ($timeAgo < ($_SESSION['highlighttime']*60));
    }
}


// Generates the "@ dm rt" options for each tweet.  $isMention is required because we only show the Report button
// on mentions, if you've followed the person already we assume non-spammer.
// $numusers > 0 means we need to do a reply-all button
function makeTwitterOperations($username, $tweet, $thisUser, $tweetid, $isMention, $isDM, $isConvoTweet, $i, $replyToUser, $replyToID, $numusers) {
	$content = '<div class="operations">';
	if ($isDM == true)  {
		if ($username != $thisUser) {
			$content .= '<a class="button replybutton" href="replybox.php?initialtext=' . urlencode('d '.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">d</a>';
		} else {
			$content .= '<a class="button confirmactionbutton" href="actions.php?destroydm=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">del</a>';
		}
	} else {
		if ($username != $thisUser) {
			$content .= '<a class="button left replybutton" href="replybox.php?initialtext=' . urlencode('@'.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">@</a>';
		
			// Reply-all
			if ($numusers > 0) {
				$matches = array();
				preg_match_all('/(^|\s)@(\w+)/', $tweet, $matches);
				$matchString = "";
				foreach($matches[2] as $match) {
					if ($match != $thisUser) {
						$matchString .= " @" . $match;
					}
				}
				if ($matchString != "") {
					$content .= '<a class="button middle replybutton" href="replybox.php?initialtext=' . urlencode('@'.$username . $matchString) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">@*</a>';
				}
			}
			
			$content .= '<a class="button middle confirmactionbutton" href="actions.php?retweet=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">rt</a>';
			$content .= '<a class="button middle replybutton" href="replybox.php?initialtext=' . urlencode('RT @'.$username.' '.$tweet) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">rt:</a>';
			$content .= '<a class="button right replybutton" href="replybox.php?initialtext=' . urlencode('d '.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">d</a>';
			if ($isMention == true) {
				$content .= '<a class="button confirmactionbutton" href="actions.php?report=' . urlencode($username) . '&thisUser=' . urlencode($thisUser) . '">ban</a>';
			}
		} else {
			$content .= '<a class="button confirmactionbutton" href="actions.php?destroystatus=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">del</a>';
		}
	}
	$content .= '</div>';
	return $content;
}


// Generates the "comment like convo" options for each FB status.
function makeFBOperations($statusID, $commentLink, $likeLink) {
	$content = '<div class="operations">';
	$content .= '</div>';
	return $content;
}

// Parses the tweet text, and links up URLs, @names and #tags.
// $numusers is returned with how many @users were linked up, if this is >0
// we can do a "reply all".
function parseLinks($html, &$numusers) {

	$shortservers = array(
		"bit.ly",
		"yfrom.com",
		"tinyurl.com",
		"snipurl.com",
		"goo.gl",
		"zz.gd",
		"t.co",
		"wp.me",
		"digs.by",
		"s.coop",
		"bbc.in",
		"post.ly",
		"wp.me"
	);
	$picservers = array(
		"twitpic.com",
		"yfrog.com",
		"lockerz.com"
	);
	$imageExtensions = array(
		".png",
		".jpg",
		".gif"
	);
	
	// Modify links depending on their type: link up for normal, lengthen for short URLs, inline text for Twixts
	preg_match_all('/\\b(http[s]?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]/i', $html, $matches);
	foreach($matches[0] as $match) {
	    $wholeblock = false;
		$replacetext = '';
		
	    // Check if it's cached
	    if (DB_SERVER != '') {
	        // No database, so it can't be cached
	        $query = "SELECT * FROM linkcache WHERE url='" . $match . "'";
            $result = mysql_query($query);
        }
        if ((DB_SERVER != '') && (mysql_num_rows($result) )) {
            // It's cached.
            $wholeblock = mysql_result($result,0,"wholeblock");
		    $replacetext = mysql_result($result,0,"replacetext");
        } else {
            // Not cached, so look it up.
		    preg_match('/^(http[s]?|ftp|file):\/\/([^\/]+)\/?(.*)$/', $match, $urlParts);
		    $server = $urlParts[2];
		    if(!in_array($server,$shortservers)) {
			    // Not a shortened link, so give the URL text an a href
			    $wholeblock = false;
			    if(in_array($server,$picservers)) {
					$replacetext = getReplaceTextForPicture($urlParts);
			    } elseif (in_array(substr($urlParts[3],-4),$imageExtensions)) {
					$replacetext = getReplaceTextForPicture($urlParts);
		        } else {
			        $replacetext = '<a href="' . $urlParts[0] . '" target="_blank">[' . $urlParts[2] . ']</a>';
			    }
		    } else {
		        // Shortened link, so let's follow it and see what we get
			    $id = preg_replace("~[^A-Za-z0-9]+~","",$urlParts[3]);
			    $result = grab($server,$id);
			    $loc = false;
			    foreach($result as $line) {
				    if(preg_match("~^Location: (.+)\r\n~",$line,$locationMatches))
					    $loc = $locationMatches[1];
			    }
			
				if ($loc) {
					// Check again against the list of URL shorteners, in case it's doubly wrapped
					preg_match('/^(http[s]?|ftp|file):\/\/([^\/]+)\/?(.*)$/', $loc, $urlParts);
				    $server = $urlParts[2];
				    if(in_array($server,$shortservers)) {
						$id = preg_replace("~[^A-Za-z0-9]+~","",$urlParts[3]);
					    $result = grab($server,$id);
					    $loc = false;
					    foreach($result as $line) {
						    if(preg_match("~^Location: (.+)\r\n~",$line,$locationMatches))
							    $loc = $locationMatches[1];
					    }
					}
				}
				
			    if (!$loc) {
				    // Not a shortened link (oops?), so give the URL text an a href
				    $wholeblock = false;
			        $replacetext = '<a href="' . $urlParts[0] . '" target="_blank">[' . $urlParts[2] . ']</a>';
			    } elseif (!preg_match("~http://twixt.successwhale.com/(.+)~",$loc,$locationMatches)) {
				    // Shortened link (but not Twixt), so replace the URL text with an an href to the real location
			        //preg_match('/^http[s]?:\/\/([^\/]+)\/?(.*)$/', $loc, $domainMatch);
					preg_match('/^(http[s]?|ftp|file):\/\/([^\/]+)\/?(.*)$/', $loc, $urlParts);
				    $wholeblock = false;
				    if (in_array($urlParts[2],$picservers)) {
						$replacetext = getReplaceTextForPicture($urlParts);
		            } elseif (in_array(substr($urlParts[3],-4),$imageExtensions)) {
						$replacetext = getReplaceTextForPicture($urlParts);
		            } else {
				        $replacetext = '<a href="' . $loc . '" target="_blank">[' . $urlParts[2] . ']</a>';
				    }
			    } else {
				    // Must be Twixt, so replace the whole text with the twixt data.
				    $wholeblock = true;
				    $twixtText = file_get_html($loc);
				    foreach($twixtText->find('p') as $e)
					    $replacetext = $e->innertext;
			    }
		    }
		    // As it wasn't cached, cache it now.
	        $query = "INSERT INTO linkcache VALUES ('" . mysql_real_escape_string($match) . "','" . mysql_real_escape_string($replacetext) . "','" . $wholeblock . "')";
            mysql_query($query);
		}
		// Do the replacement
		if ($wholeblock) {
		    $html = $replacetext;
		} else {
    		$html = preg_replace('/' . preg_quote($match, '/') . '/', $replacetext, $html);
        }
	}
	
	// Link up @users and #tags
	$numusers = 0;
	$html = preg_replace('/(^|\W)@(\w+)/', '\1<a href="http://www.twitter.com/\2" target="_blank">@\2</a>', $html, -1, $numusers);
	$html = preg_replace('/(^|[^\&\w\/])#(\w+)/', '\1<a href="https://twitter.com/search?q=%23\2" target="_blank">#\2</a>', $html);
	
	return $html;
}

// Generates the pager
function makeNavForm($count, $columnOptions, $thisColName) {
    $thisColNumber = substr($_GET['div'], 0);
	$content = '<div class="columnnav">';	
	$content .= '<div name="colswitcherform" class="colswitcherform">';
	
	// Dropdown
	$content .= '<select name="colswitcherbox" size="10" onchange="changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=\' + escape(this.options[this.selectedIndex].value) + \'&count=' . $count . '\', 1)">';
	
	// Everything that is in columnOptions
	foreach ($columnOptions as $key => $value) {
		$content .= '<option value="' . $key . '"';
		if ($key == $thisColName) {
		    $content .= ' selected';
		}
		if (strpos($value, "--") !== FALSE) {
		    $content .= ' disabled="disabled"';
		}
		$itemName = $columnOptions[$key];
		$content .= '>' . $itemName . '</option>';
	}
	
	// Other
	$content .= '<option value="--" disabled="disabled">-- Miscellaneous --</option>';
		
	// This column's name, if it's not in columnOptions
	if (!array_key_exists($thisColName, $columnOptions)) {
	    // Column identifiers are in three colon-separate bits, e.g.
        // twitter:tsuki_chama:statuses/user_timeline, just grab the last bit
        // as it's friendlier.
	    $content .= '<option value="' . $thisColName . '" selected>' . $thisColName . '</option>';
	}
	
	$content .= '<option value="----------">(Custom)</option>';
    $content .= '</select><br/>';
    $content .= '<input id="customcolumnentry' . $thisColNumber . '" class="customcolumnentry" size="20" disabled="true" value="@usr, @usr/list" onKeyUp="checkForSubmitCustomColumn(this, event, ' . $thisColNumber . ');"/><br/>';

	$content .= '</div>';
	$content .= '</div>';
	return $content;
}


// Generates the pager
function makeMoreLessForm($count, $columnOptions, $thisColName) {
    $thisColNumber = substr($_GET['div'], 0);
	$content = '<div class="morelessform">';	

	if ($count > 20) {
		$content .= '<a href="javascript:changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=' . urlencode($_GET['column']) . '&count=' . ($count-20) . '\', 1)" class="button left">less</a>&nbsp;';
	    $content .= '<a href="javascript:changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=' . urlencode($_GET['column']) . '&count=' . ($count+20) . '\', 1)" class="button right">more</a>';
	} else {
	    $content .= '<a href="javascript:changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=' . urlencode($_GET['column']) . '&count=' . ($count+20) . '\', 1)" class="button">more</a>';
	}
	$content .= '</div>';
	return $content;
}

// We've got the URL to an image server such as twitpit, so let's build special URLs for fetching the real pictures.
function getReplaceTextForPicture($urlParts) {
	if ($urlParts[2] == "yfrog.com") {
		// yFrog needs a special ":iphone" URL, then gives us a 301 to the real image
		$id = preg_replace("~[^A-Za-z0-9]+~","",$urlParts[3]);
	    $result = grab($urlParts[2],$id.":iphone");
	    $loc = false;
	    foreach($result as $line) {
		    if(preg_match("~^Location: (.+)\r\n~",$line,$locationMatches))
			    $loc = $locationMatches[1];
	    }
	} else if ($urlParts[2] == "twitpic.com") {
		// Twitpic just needs some URL substitution
		$loc = "http://twitpic.com/show/large/" . $urlParts[3] . ".jpg";
	} else if ($urlParts[2] == "lockerz.com") {
		// Lockerz (formerly plixi) needs an API call, then gives us a 301 to the real image
		$result = grab("api.plixi.com","api/tpapi.svc/imagefromurl?url=" . $urlParts[0] . "&size=medium");
	    $loc = false;
	    foreach($result as $line) {
		    if(preg_match("~^Location: (.+)\r\n~",$line,$locationMatches))
			    $loc = $locationMatches[1];
	    }
	} else {
		// It was a raw image link, found by file extension (.jpg etc)
		$loc = $urlParts[0];
	}
    $text = '<a href="' . $loc . '" class="fancybox">[' . $urlParts[2] . ']</a>';
	return $text;
}
?>
