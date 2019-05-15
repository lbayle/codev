
// use ajax to log Javascript errors on SERVER side
window.onerror = null;
window.onerror = function(desc, file, line) {


	try {
	desc = desc || '';

		// use image to call the url (do not use jQuery in this file)
		var url = document.getElementsByTagName('base')[0].href + 'include/jsErrorCatch_ajax.php?' +
		//var url = document.location.protocol + '//' + document.location.host + '/codevtt/include/jsErrorCatch_ajax.php?' +

			// No cache for IE
		  '&no_cache=' + encodeURIComponent(Math.random()) +
		  '&desc=' + encodeURIComponent(desc) +
		  "&line=" + line +
		  "&useragent=" + encodeURIComponent(navigator.userAgent) +
		  "&os=" + encodeURIComponent(navigator.platform) +
		  "&url=" + encodeURIComponent(document.location.toString()) +
		  "&file=" + encodeURIComponent(file);

		new Image().src = url;

		// do not loop on error procedure (but only the first error will be reported)
		onerror = null;
		return true;
	}
	catch (e) {
		onerror = null;
	}
};
