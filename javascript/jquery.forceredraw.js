/**
 * Force Redraw
 *
 * Created by Pascal Beyeler (anvio.ch)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Sometimes the browser just does not redraw the elements after a CSS change or whatever. 
 * This plugin allows you to force a redraw in your browser.
 *
 * @example $('#myelement').forceRedraw(); //works in most cases
 * @example $('#myelement').forceRedraw(true); //use this one to force a redraw by changing the element's padding
 * @desc force a browser redraw.
 *
 * @param brutal
 * @return jQuery object
 *
 * @name $.fn.forceRedraw
 * @cat Plugins/Browser
 * @author Pascal Beyeler (anvio.ch)
 */
(function($) {
	
	$.fn.forceRedraw = function(brutal) {

		//this fix works for most browsers. it has the same effect as el.className = el.className.
		$(this).addClass('forceRedraw').removeClass('forceRedraw');
		
		//sometimes for absolute positioned elements the above fix does not work.
		//there's still a "brutal" way to force a redraw by changing the padding.
		if(brutal) {
			var paddingLeft = $(this).css('padding-left');
			var parsedPaddingLeft = parseInt(paddingLeft, 10);
			$(this).css('padding-left', ++parsedPaddingLeft);
			
			//give it some time to redraw
			window.setTimeout($.proxy(function() {
				//change it back
				$(this).css('padding-left', paddingLeft);
			}, this), 1);		
		}

		return this;
		
	}
	
})(jQuery);