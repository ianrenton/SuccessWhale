SuccessWhale
============

SuccessWhale is a web-based Twitter and (formerly) Facebook client using the SuccessWhale API, written in JavaScript using Knockout.js. It’s a multi-column client that can merge and display feeds from several social networks however you like. It’s free to use by anyone, has no advertising, and source code is available on GitHub under the BSD licence.

Facebook support has been broken since Facebook denied stream-reading permissions to third-party apps in May 2015. The code to support Facebook remains in SuccessWhale in case they reverse this decision, but currently no Facebook use is possible from SuccessWhale.

Visit [https://successwhale.com](https://successwhale.com) to try it out!

Features
--------

* You can add as many Twitter accounts as you like, displaying data from each, and you can choose which to post to every time.
* SuccessWhale has a multi-column view, which can be scrolled through if you want more than will sensibly fit on your screen. Many columns are available for each Twitter and Facebook account you register, including ones that combine notifications from all your accounts.
* SuccessWhale is integrated with my Twitter pastebin, Twixt. Enter a reply longer than 140 characters into the box in SuccessWhale, and it will be shortened automatically using Twixt. SuccessWhale also displays the contents of Twixt posts inline, and expands short URLs.
* You can use SuccessWhale from places where twitter.com and facebook.com are blocked. To do so, you’ll have to log in from a computer that can see the sites first, then click “Accounts” in the top-right and create yourself a SuccessWhale account. You’ll then be able to log in using that password from any computer.
* You can maintain a "banned phrases" list, which will hide tweets containing certain phrases — great if you’ve got too many friends that spam their Foursquare check-ins to Twitter.

Screenshot
----------

[![Successwhale 3 Screenshot](http://files.ianrenton.com/successwhale-screenshot.png)](http://files.ianrenton.com/successwhale-screenshot.png)

Status
------

This is version 3 of SuccessWhale, which has been written from the ground up using client-side Javascript and an API server.

SuccessWhale is used by around 20 people — including myself — as their main Twitter client, and a number of sites around the internet have used the source code to integrate SuccessWhale into their own sites. As far as I’m aware it has no bugs, but if you find any or would like to request any new features for the next version, you can contact me on Twitter (I'm [@i_renton](http://www.twitter.com/i_renton)) or via the Issues page on GitHub.

Libraries
---------

 * [Cookies.js](https://github.com/ScottHamper/Cookies)
 * [Fancybox](http://fancybox.net/)
 * [Font Awesome](http://fortawesome.github.io/Font-Awesome/)
 * [Knockout.js](http://knockoutjs.com/)
 * [Knockout Sortable](https://github.com/rniemeyer/knockout-sortable)
 * [jQuery](http://jquery.com/)
 * [jQuery Autosize](http://www.jacklmoore.com/autosize/)
 * [jQuery Forms](http://malsup.com/jquery/form/)
 * [jQuery GPopover](https://github.com/markembling/jquery-gpopover)
 * [jQuery TouchSwipe](https://github.com/mattbryson/TouchSwipe-Jquery-Plugin)
 * [jQuery UI](http://jqueryui.com/)
 * [Moment.js](http://momentjs.com/)
 * [Normalise.css](http://git.io/normalize)
 * [Twitter Entities](https://gist.github.com/wadey/442463)
 * [XDR](https://gist.github.com/michaelcox/2655118)

Thanks to...
------------

  * My early beta testers [@aefaradien](http://www.twitter.com/aefaradien) and [@tikakino](http://www.twitter.com/tikakino)!
  * Everyone who's helped out over the years
