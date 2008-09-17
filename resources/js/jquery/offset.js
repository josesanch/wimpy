/* Copyright (c) 2006 Brandon Aaron (http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 */

/**
 * This returns an object with top, left, width, height, borderLeft,
 * borderTop, marginLeft, marginTop, scrollLeft, scrollTop, 
 * pageXOffset, pageYOffset.
 *
 * The top and left values include the scroll offsets but the
 * scrollLeft and scrollTop properties of the returned object
 * are the combined scroll offets of the parent elements 
 * (not including the window scroll offsets). This is not the
 * same as the element's scrollTop and scrollLeft.
 * 
 * For accurate readings make sure to use pixel values.
 *
 * @name offset	
 * @type Object
 * @cat DOM
 * @author Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 */
/**
 * This returns an object with top, left, width, height, borderLeft,
 * borderTop, marginLeft, marginTop, scrollLeft, scrollTop, 
 * pageXOffset, pageYOffset.
 *
 * The top and left values include the scroll offsets but the
 * scrollLeft and scrollTop properties of the returned object
 * are the combined scroll offets of the parent elements 
 * (not including the window scroll offsets). This is not the
 * same as the element's scrollTop and scrollLeft.
 * 
 * For accurate readings make sure to use pixel values.
 *
 * @name offset	
 * @type Object
 * @param String refElement This is an expression. The offset returned will be relative to the first matched element.
 * @cat DOM
 * @author Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 */
/**
 * This returns an object with top, left, width, height, borderLeft,
 * borderTop, marginLeft, marginTop, scrollLeft, scrollTop, 
 * pageXOffset, pageYOffset.
 *
 * The top and left values include the scroll offsets but the
 * scrollLeft and scrollTop properties of the returned object
 * are the combined scroll offets of the parent elements 
 * (not including the window scroll offsets). This is not the
 * same as the element's scrollTop and scrollLeft.
 * 
 * For accurate readings make sure to use pixel values.
 *
 * @name offset	
 * @type Object
 * @param jQuery refElement The offset returned will be relative to the first matched element.
 * @cat DOM
 * @author Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 */
/**
 * This returns an object with top, left, width, height, borderLeft,
 * borderTop, marginLeft, marginTop, scrollLeft, scrollTop, 
 * pageXOffset, pageYOffset.
 *
 * The top and left values include the scroll offsets but the
 * scrollLeft and scrollTop properties of the returned object
 * are the combined scroll offets of the parent elements 
 * (not including the window scroll offsets). This is not the
 * same as the element's scrollTop and scrollLeft.
 * 
 * For accurate readings make sure to use pixel values.
 *
 * @name offset	
 * @type Object
 * @param HTMLElement refElement The offset returned will be relative to this elemen.
 * @cat DOM
 * @author Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 */
$.fn.offset = function(refElem) {
	if (!this[0]) throw '$.fn.offset requires an element.';
	
	refElem = (refElem) ? $(refElem)[0] : null;
	var x = 0, y = 0, elm = this[0], parent = this[0], pos = null, borders = [0,0], isElm = true, sl = 0, st = 0;
	do {
		if (parent.tagName == 'BODY' || parent.tagName == 'HTML') {
			// Safari and IE don't add margin for static and relative
			if (($.browser.safari || $.browser.msie) && pos != 'absolute') {
				x += parseInt($.css(parent, 'marginLeft')) || 0;
				y += parseInt($.css(parent, 'marginTop'))  || 0;
			}
			break;
		}
		
		pos    = $.css(parent, 'position');
		border = [parseInt($.css(parent, 'borderLeftWidth')) || 0,
							parseInt($.css(parent, 'borderTopWidth'))  || 0];
		sl = parent.scrollLeft;
		st = parent.scrollTop;
		
		x += (parent.offsetLeft || 0) + border[0] - sl;
		y += (parent.offsetTop  || 0) + border[1] - st;
		
		// Safari and Opera include the border already for parents with position = absolute|relative
		if (($.browser.safari || $.browser.opera) && !isElm && (pos == 'absolute' || pos == 'relative')) {
			x -= border[0];
			y -= border[1];
		}
		
		parent = parent.offsetParent;
		isElm  = false;
	} while(parent);
	
	if (refElem) {
		var offset = $(refElem).offset();
		x  = x  - offset.left;
		y  = y  - offset.top;
		sl = sl - offset.scrollLeft;
		st = st - offset.scrollTop;
	}
	
	return {
		top:  y,
		left: x,
		width:  elm.offsetWidth,
		height: elm.offsetHeight,
		borderTop:  parseInt($.css(elm, 'borderTopWidth'))  || 0,
		borderLeft: parseInt($.css(elm, 'borderLeftWidth')) || 0,
		marginTop:  parseInt($.css(elm, 'marginTopWidth'))  || 0,
		marginLeft: parseInt($.css(elm, 'marginLeftWidth')) || 0,
		scrollTop:  st,
		scrollLeft: sl,
		pageYOffset: window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop  || 0,
		pageXOffset: window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0
	};
};