<?php
date_default_timezone_set('UTC');

include('simplehtmldom/simple_html_dom.php');

// Generate certain times used in the creation of friendly display times.
// We do this here so we only have to do it once, not on every tweet.
$midnightYesterday = strtotime("midnight -1 day");
$oneWeekAgo = strtotime("midnight -6 days");
$janFirst = strtotime("january 1st");


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

// Generates an individual tweet list (one of timeline, mentions or directs) depending on
// the JSON and bool that's passed to it.  $isDM required because while normal tweets have
// "user", DMs have "sender".  $isMention is required because we only show the Report button
// on mentions, if you've followed the person already we assume non-spammer.
function generateTweetList($data, $isMention, $isDM, $isConvo, $thisUser, $blocklist, $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst) {
	
	//Unpack blocklist
	$blocks = explode(";", $blocklist);
	
	$userString = "user";
	if ($isDM == true) $userString = "sender";
	for ($i=0; $i<count($data); $i++) {
	    
			
		// We do this first so we know how many @users were linked up, if >0
		// then we can do a reply-all.  User counting happens in parseLinks(),
		// so we set this to zero before we call it.
		$numusers = 0;
	
	    // Spot truncated RTs and expand them
	    if (isset($data[$i]["retweeted_status"])) {
	        $avatar = '<a href="http://www.twitter.com/' . $data[$i]["retweeted_status"][$userString]["screen_name"] . '" target="_blank"><img class="avatar" src="' . $data[$i]["retweeted_status"][$userString]["profile_image_url"] . '" alt="' . $data[$i]["retweeted_status"][$userString]["name"] . '" title="' . $data[$i]["retweeted_status"][$userString]["name"] . '" border="0" width="48" height="48"></a>';
	        $nameField = '<a href="http://www.twitter.com/' . $data[$i]["retweeted_status"][$userString]["screen_name"] . '" target="_blank">' . $data[$i]["retweeted_status"][$userString]["name"] . '</a>, RT by <a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank">' . $data[$i][$userString]["name"] . '</a></span>';
	        $tweetbody = "RT @" . $data[$i]["retweeted_status"][$userString]["screen_name"] . " " . $data[$i]["retweeted_status"]["text"];
	        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
		    // A bit of processing overhead, but it stops unwelcome URLs in tweets
		    // evading the blocker by using a URL shortener.
		    $tweetbody = parseLinks($tweetbody, $numusers);
	        $operations = makeTwitterOperations($data[$i]["retweeted_status"][$userString]["screen_name"], $data[$i]["retweeted_status"]["text"], $thisUser, $data[$i]["retweeted_status"]["id"], $isMention, $isDM, $isConvo, $i, $data[$i]["retweeted_status"]["in_reply_to_screen_name"], $data[$i]["retweeted_status"]["in_reply_to_status_id"], $numusers);
	    } else {
	        $avatar = '<a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank"><img class="avatar" src="' . $data[$i][$userString]["profile_image_url"] . '" alt="' . $data[$i][$userString]["name"] . '" title="' . $data[$i][$userString]["name"] . '" border="0" width="48" height="48"></a>';
	        $nameField = '<a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank">' . $data[$i][$userString]["name"] . '</a></span>';
	        $tweetbody = $data[$i]["text"];
	        // Expand short URLs etc first, so we can apply the blocklist to real URLs.
		    // A bit of processing overhead, but it stops unwelcome URLs in tweets
		    // evading the blocker by using a URL shortener.
		    $tweetbody = parseLinks($tweetbody, $numusers);
		    $operations = makeTwitterOperations($data[$i][$userString]["screen_name"], $data[$i]["text"], $thisUser, $data[$i]["id"], $isMention, $isDM, $isConvo, $i, $data[$i]["in_reply_to_screen_name"], $data[$i]["in_reply_to_status_id"], $numusers);
	    }
			
		// Check blocklist
		$match = false;
		foreach ($blocks as $blockstring) {
			if ($blockstring != '') {
				$pos = strpos($tweetbody, $blockstring);
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
			    $content .= '<div class="item twitterstatus">';
			}
			$content .= '<div class="text">';
			if (strtotime($data[$i]["created_at"]) == 0) {
                $content .= '<div class="metatext"><span class="name">Protected or deleted tweet.</span></div>';
			} else {
                $content .= '<table><tr><td>';
                $content .= $avatar;
                $content .= '</td>';
                $content .= '<td class="tweettextcell"><span class="tweettext wraptext">';
                $content .= $tweetbody;
                $content .= '</span>';
                $content .= '</td></tr></table>';
                if (!$isConvo) {
                    $content .= '<div class="metatable">';
                    $content .= $operations;
                    $content .= '<div class="metatext">';
                    $content .= makeFriendlyTime(strtotime($data[$i]["created_at"])+$utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst) . '<br/>';
                    $content .= '<span class="name">';
                    $content .= $nameField;
                    $content .= '</span>';
                    $content .= '</div>';
                    $content .= '</div>';
                }
			}
			$content .= '</div>';
			if (!$isConvo) {
			    $content .= '<div class="convoarea"></div>';
			}
			$content .= '</div><div class="clear"></div>';
		}
	}
	return $content;
}


// Generates an individual facebook status list
function generateFBStatusList($data, $isNotifications, $thisUser, $blocklist, $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst) {
	
	//Unpack blocklist
	$blocks = explode(";", $blocklist);
	
	if (!$isNotifications) {
	    $data = $data['data'];
	}
	
	for ($i=0; $i<count($data); $i++) {
		// Get the status body based on what the data contains
		if ($isNotifications) {
		    $statusbody = $data[$i]["title_html"] . '<br/>' . parseLinks($data[$i]["body_html"], $ignore);
		    $avatar = '<img class="avatar" src="http://graph.facebook.com/' .$data[$i]["sender_id"] . '/picture" border="0" width="48" height="48"><br/>';
            $timeString = makeFriendlyTime($data[$i]["created_time"]+$utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
		} else {
	        if ($data[$i]["type"] == "status") {
		        $statusbody = parseLinks($data[$i]["message"],$ignore);
		    } elseif ($data[$i]["type"] == "link") {
		        $statusbody = '<a href="' . $data[$i]["link"] . '">' . $data[$i]["name"] . '</a><br/>' . parseLinks($data[$i]["description"],$ignore);
		    } elseif ($data[$i]["type"] == "photo") {
		        $statusbody = '<a href="' . $data[$i]["link"] . '">' . $data[$i]["name"] . '</a><br/>' . parseLinks($data[$i]["message"],$ignore);
		    } elseif ($data[$i]["type"] == "video") {
		        $statusbody = '<a href="' . $data[$i]["link"] . '">' . $data[$i]["name"] . '</a><br/>' . parseLinks($data[$i]["message"],$ignore);
		    } else {
		        $statusbody = "";
		    }
		    $avatar = '<img class="avatar" src="http://graph.facebook.com/' .$data[$i]["from"]["id"] . '/picture" alt="' . $data[$i]["from"]["name"] . '" title="' . $data[$i]["from"]["name"] . '" border="0" width="48" height="48"><br/>';
            $timeString = makeFriendlyTime(strtotime($data[$i]["created_time"])+$utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
		}
			
		// Check blocklist
		$match = false;
		foreach ($blocks as $blockstring) {
			if ($blockstring != '') {
				$pos = strpos($statusbody, $blockstring);
				if ($pos !== false) {
					$match = true;
				}
			}
		}
		
		// Display tweet if it didn't match, of if it's part of a convo
		// (Convos explicitly request a thread, it would look weird if
		// we hid parts of it.)
		if (!$match) {
			$content .= '<div class="item facebookstatus">';
			$content .= '<div class="text">';
            $content .= '<table><tr><td>';
            $content .= $avatar;
            $content .= '</td>';
            $content .= '<td class="tweettextcell"><span class="tweettext wraptext">';
            $content .= $statusbody;
            $content .= '</span>';
            $content .= '</td></tr></table>';
            if (!$isConvo) {
                $content .= '<div class="metatable">';
                $content .= makeFBOperations($data[$i]["id"], $data[$i]["actions"][0]["link"], $data[$i]["actions"][1]["link"]);
                $content .= '<div class="metatext">';
                if (!$isNotifications) {
                    $content .= '<span class="name">';
                    //$content .= '<a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank">';
                    $content .= $data[$i]["from"]["name"];
                    $content .= /*</a>*/'</span>';
                    $content .= ' &bull; ';
                }
                $content .= $timeString;
                $content .= '</div>';
                $content .= '</div>';
            }
			$content .= '</div>';
			if (!$isConvo) {
			    $content .= '<div class="convoarea"></div>';
			}
			$content .= '</div><div class="clear"></div>';
		}
	}
	return $content;
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


// Generates the "@ dm rt" options for each tweet.  $isMention is required because we only show the Report button
// on mentions, if you've followed the person already we assume non-spammer.
// $numusers > 0 means we need to do a reply-all button
function makeTwitterOperations($username, $tweet, $thisUser, $tweetid, $isMention, $isDM, $isConvoTweet, $i, $replyToUser, $replyToID, $numusers) {
	$content = '<div class="operations">';
	if ($isDM == true)  {
		if ($username != $thisUser) {
			$content .= '<a class="replybutton" href="replybox.php?initialtext=' . urlencode('d '.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">d</a>&nbsp;';
		} else {
			$content .= '<a class="confirmactionbutton" href="actions.php?destroydm=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">del</a>&nbsp;';
		}
	} else {
	    if ((!$isConvoTweet) && ($replyToID > 0)) {
	        $content .= '<a class="convobutton" href="convo.php?service=twitter&thisUser=' . $thisUser . '&status=' . $tweetid . '">convo</a>&nbsp;';
	    }
		if ($username != $thisUser) {
			$content .= '<a class="replybutton" href="replybox.php?initialtext=' . urlencode('@'.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">@</a>&nbsp;';
		
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
					$content .= '<a class="replybutton" href="replybox.php?initialtext=' . urlencode('@'.$username . $matchString) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">@*</a>&nbsp;';
				}
			}
			
			$content .= '<a class="doactionbutton" href="actions.php?retweet=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">rt</a>&nbsp;';
			$content .= '<a class="replybutton" href="replybox.php?initialtext=' . urlencode('RT @'.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">rt:</a>&nbsp;';
			$content .= '<a class="replybutton" href="replybox.php?initialtext=' . urlencode('d '.$username) . '&replyid=' . $tweetid . '&account=' . urlencode('twitter:' . $thisUser) . '">d</a>&nbsp;';
			if ($isMention == true) {
				$content .= '<a class="confirmactionbutton" href="actions.php?report=' . urlencode($username) . '&thisUser=' . urlencode($thisUser) . '">b&</a>&nbsp;';
			}
		} else {
			$content .= '<a class="confirmactionbutton" href="actions.php?destroystatus=' . $tweetid . '&thisUser=' . urlencode($thisUser) . '">del</a>&nbsp;';
		}
	}
	$content .= '</div>';
	return $content;
}


// Generates the "comment like convo" options for each FB status.
function makeFBOperations($statusID, $commentLink, $likeLink) {
	$content = '<div class="operations">';
	$content .= '<a class="replybutton" href="' . $commentLink . '">comment</a>&nbsp;';
	$content .= '<a class="replybutton" href="' . $likeLink . '">like</a>&nbsp;';
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
		"digs.by"
	);
	$picservers = array(
		"twitpic.com",
		"yfrog.com"
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
			    // Not a shortened or image server link, so give the URL text an a href
			    $wholeblock = false;
			    if(in_array($server,$picservers)) {
			        $replacetext = '<a href="' . $urlParts[0] . '" class="fancybox">[' . $urlParts[2] . ']</a>';
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
			    if (!$loc) {
				    // Not a shortened link, so give the URL text an a href
				    $wholeblock = false;
			        $replacetext = '<a href="' . $urlParts[0] . '" target="_blank">[' . $urlParts[2] . ']</a>';
			    } elseif (!preg_match("~http://twixt.successwhale.com/(.+)~",$loc,$locationMatches)) {
				    // Shortened link (but not Twixt), so replace the URL text with an an href to the real location
			        preg_match('/^http[s]?:\/\/([^\/]+)\/?(.*)$/', $loc, $domainMatch);
				        $wholeblock = false;
				    if(in_array($domainMatch[1],$picservers)) {
			            // Image server link, so give the URL text an a href and a fancybox class
				        $replacetext = '<a href="' . $loc . '" class="fancybox">[' . $domainMatch[1] . ']</a>';
		            } else {
				        $replacetext = '<a href="' . $loc . '" target="_blank">[' . $domainMatch[1] . ']</a>';
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
	$html = preg_replace('/(^|[^\&\w\/])#(\w+)/', '\1<a href="http://search.twitter.com/search?q=%23\2" target="_blank">#\2</a>', $html);
	
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
	$content .= '<option value="--" disabled="disabled">Miscellaneous</option>';
		
	// This column's name, if it's not in columnOptions
	if (!array_key_exists($thisColName, $columnOptions)) {
	    // Column identifiers are in three colon-separate bits, e.g.
        // twitter:tsuki_chama:statuses/user_timeline, just grab the last bit
        // as it's friendlier.
	    $thisColNameBits = explode(":", $thisColName);
	    $content .= '<option value="' . $thisColName . '" selected>' . $thisColNameBits[2] . '</option>';
	}
	
	$content .= '<option value="----------">(Custom)</option>';
    $content .= '</select><br/>';
    $content .= '<input id="customcolumnentry' . $thisColNumber . '" class="customcolumnentry" size="20" disabled="true" value="@usr, @usr/list" onKeyUp="checkForSubmitCustomColumn(this, event, ' . $thisColNumber . ');"/><br/>';
    
    $content .= '<a class="confirmactionbutton boxedbutton" href="actions.php?delcol=' . $thisColNumber . '">del</a>&nbsp;';
    $content .= '<a class="hidenavform boxedbutton" href="">hide</a>&nbsp;';
	
	if ($count > 20) {
		$content .= '<a href="javascript:changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=' . urlencode($_GET['column']) . '&count=' . ($count-20) . '\', 1)" class="boxedbutton">less</a>&nbsp;';
	}
	$content .= '<a href="javascript:changeColumn(\'' . $thisColNumber . '\', \'column.php?div=' . $thisColNumber . '&column=' . urlencode($_GET['column']) . '&count=' . ($count+20) . '\', 1)" class="boxedbutton">more</a>';
	$content .= '</div>';
	$content .= '</div>';
	return $content;
}

?>
