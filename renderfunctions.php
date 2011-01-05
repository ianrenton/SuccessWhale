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
		
		// Expand short URLs etc first, so we can apply the blocklist to real URLs.
		// A bit of processing overhead, but it stops unwelcome URLs in tweets
		// evading the blocker by using a URL shortener.
		$tweetbody = parseLinks($data[$i]["text"], $numusers);
			
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
			
			// We do this first so we know how many @users were linked up, if >0
			// then we can do a reply-all.
			$numusers = 0;
		
	    	if ($isConvo) {
			    $content .= '<div class="convotweet">';
			} else {
			    $content .= '<div class="tweet">';
			}
			$content .= '<div class="text">';
			$content .= '<table class="metatable"><tr><td>';
			$content .= '<div class="metatext"><span class="name">';
			$content .= '<a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank">';
			$content .= $data[$i][$userString]["name"];
			$content .= '</a></span>';
			$content .= ' &bull; ' . makeFriendlyTime(strtotime($data[$i]["created_at"])+$utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
			$content .= '</div>';
			$content .= '</td><td>';
			$content .= makeOperations($data[$i][$userString]["screen_name"], $data[$i]["text"], $thisUser, $data[$i]["id"], $isMention, $isDM, $isConvo, $i, $data[$i]["in_reply_to_screen_name"], $data[$i]["in_reply_to_status_id"], $numusers);
			$content .= '</td></tr></table>';
			$content .= '<table><tr><td>';
			$content .= '<a href="http://www.twitter.com/' . $data[$i][$userString]["screen_name"] . '" target="_blank">';
			$content .= '<img class="avatar" src="' . $data[$i][$userString]["profile_image_url"] . '" alt="' . $data[$i][$userString]["name"] . '" title="' . $data[$i][$userString]["name"] . '" border="0" width="48" height="48"><br/>';
			$content .= '</a></td>';
			$content .= '<td class="tweettextcell"><span class="tweettext">';
			foreach(explode(" ", strip_tags($tweetbody)) as $key => $line) {
				if (strlen($line) > 30) $tweetbody = str_replace($line, wordwrap($line, 25, "- ", 1), $tweetbody);
			} 
			$content .= $tweetbody;
			$content .= '</span>';
			$content .= '</td></tr></table></div><div class="clear"></div></div>';
			if (!$isConvo) {
			    $content .= '<div id="' . $_GET['div'] . '-box' . $i . '-below"></div>';
			}
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
function makeOperations($username, $tweet, $thisUser, $tweetid, $isMention, $isDM, $isConvoTweet, $i, $replyToUser, $replyToID, $numusers) {
	$content = '<div class="operations">';
	if ($isDM == true)  {
		if ($username != $thisUser) {
			$content .= '<a href="javascript:setStatusField(\'d ' . $username . ' \', \'' . $tweetid . ' \')"><img src="images/dm.png" alt="DM this user" title="DM this user"></a>&nbsp;';
		} else {
			$content .= '<a href="javascript:confirmAction(\'actions.php?destroydm=' . $tweetid . '\')"><img src="images/delete.png" alt="Delete this DM" title="Delete this DM"></a>';
		}
	} else {
	    if ((!$isConvoTweet) && ($replyToID > 0)) {
	        $replyURL = 'convo.php?status=' . $tweetid;
	        $targetDiv = $replyURL . '\', \'' . $_GET['div'] . '-box' . $i . '-below';
	        $content .= '<a href="' . $replyURL . '" target="convo" onClick="expandConvo(\'' . $targetDiv . '\'); return false;"><img src="images/thread.png" alt="View Conversaion" title="View Conversation"></a>&nbsp;';
	    }
		if ($username != $thisUser) {
			$content .= '<a href="javascript:setStatusField(\'@' . $username . ' \', \'' . $tweetid . ' \')"><img src="images/reply.png" alt="@-reply to this user" title="@-reply to this user"></a>&nbsp;';	
			
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
					$content .= '<a href="javascript:setStatusField(\'@' . $username . $matchString . ' \', \'' . $tweetid . ' \')"><img src="images/replyall.png" alt="@-reply to all users" title="@-reply to all users"></a>&nbsp;';	
				}
			}
			
			$content .= '<a href="javascript:doAction(\'actions.php?retweet=' . $tweetid . '\')"><img src="images/retweet.png" alt="Retweet this" title="Retweet this"></a>&nbsp;';
			$content .= '<a href="javascript:setStatusField(\'RT @' . $username . ': ' . str_replace("'","\\'",str_replace('"','&quot;',$tweet)) . ' \', \'' . $tweetid . ' \')"><img src="images/oldretweet.png" alt="Old-style Retweet" title="Old-style Retweet"></a>&nbsp;';
			$content .= '<a href="javascript:setStatusField(\'d ' . $username . ' \', \'' . $tweetid . ' \')"><img src="images/dm.png" alt="DM this user" title="DM this user"></a>&nbsp;';
			if ($isMention == true) {
				$content .= '<a href="javascript:confirmAction(\'actions.php?report=' . $username . '\')"><img src="images/report.png" alt="Report this user as a spammer" title="Report this user as a spammer"></a>';
			}
		} else {
			$content .= '<a href="javascript:confirmAction(\'actions.php?destroystatus=' . $tweetid . '\')"><img src="images/delete.png" alt="Delete this Tweet" title="Delete this Tweet"></a>';
		}
	}
	$content .= '</div>';
	return $content;
}

// Parses the tweet text, and links up URLs, @names and #tags.
// $numusers is returned with how many @users were linked up, if this is >0
// we can do a "reply all".
function parseLinks($html, &$numusers) {

	$shortservers = array(
		"is.gd",
		"bit.ly",
		"yfrom.com",
		"tinyurl.com",
		"snipurl.com",
		"goo.gl",
		"zz.gd",
		"t.co",
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
			    $replacetext = '<a href="' . $urlParts[0] . '" target="_blank">[' . $urlParts[2] . ']</a>';
		    } else {
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
				    $replacetext = '<a href="' . $loc . '" target="_blank">[' . $domainMatch[1] . ']</a>';
			    } else {
				    // Must be Twixt, so replace the whole text with the twixt data.
				    $wholeblock = true;
				    $twixtText = file_get_html($loc);
				    foreach($twixtText->find('p') as $e)
					    $replacetext = $e->innertext;
			    }
		    }
		    // As it wasn't cached, cache it now.
		    if (DB_SERVER != '') {
		        $query = "INSERT INTO linkcache VALUES ('" . mysql_real_escape_string($match) . "','" . mysql_real_escape_string($replacetext) . "','" . $wholeblock . "')";
                mysql_query($query);
            }
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
	$html = preg_replace('/(^|\s|>)@(\w+)/', '\1<a href="http://www.twitter.com/\2" target="_blank">@\2</a>', $html, -1, $numusers);
	$html = preg_replace('/(^|\s|>)#(\w+)/', '\1<a href="http://search.twitter.com/search?q=%23\2" target="_blank">#\2</a>', $html);
	
	return $html;
}

// Generates the pager
function makeNavForm($count, $columnOptions, $thisColName) {
	$content = '<div id="colswitcher">';
	$content .= '<form name="colswitcherform">';
	$content .= '<a href="#"><img src="images/top.png" alt="Back to Top"/></a>&nbsp;';
	$content .= '<select name="colswitcherbox" onchange="changeColumn(\'' . $_GET['div'] . '\', \'column.php?div=' . $_GET['div'] . '&column=\' + escape(this.options[this.selectedIndex].value) + \'&count=' . $count . '\')">';
	foreach ($columnOptions as $key => $value) {
		$content .= '<option value="' . $key . '"';
		if ($key == $thisColName) {
		    $content .= ' selected';
		}
		$content .= '>' . $key . '</option>';
	}
    $content .= '</select></form>';
	$content .= '</div>';
	
	$content .= '<div id="nav"><ul>';
	if ($count > 20) {
		$content .= '<li><a href="javascript:changeColumn(\'' . $_GET['div'] . '\', \'column.php?div=' . $_GET['div'] . '&column=' . urlencode($_GET['column']) . '&count=' . ($count-20) . '\')"><img src="images/less.png" alt="Show fewer tweets"/></a></li>';
	}
	$content .= '<li><a href="javascript:changeColumn(\'' . $_GET['div'] . '\', \'column.php?div=' . $_GET['div'] . '&column=' . urlencode($_GET['column']) . '&count=' . ($count+20) . '\')"><img src="images/more.png" alt="Show more tweets"/></a></li>';
	$content .= '</ul></div>';
	return $content;
}


// Generates the convo hider at the left
function makeConvoHider($div) {
	$content = '<div id="colswitcher">';
	$content .= '<a href="javascript:hideConvo(\'' . $div . '\');"><img src="images/hideconvo.png" alt="Hide Conversation"/></a>';
	$content .= '</div>';
	return $content;
}

// Generates the convo hider at the bottom
function makeConvoHiderLower($div) {
	$content = '<div id="nav"><ul>';
	$content .= '<li><a href="javascript:hideConvo(\'' . $div . '\');"><img src="images/hideconvo.png" alt="Hide Conversation"/></a></li>';
	$content .= '</ul></div>';
	return $content;
}

?>
