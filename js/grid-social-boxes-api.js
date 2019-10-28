(function(){

	const FACEBOOK_COOKIE = "gridSocialBoxesFacebookCookies";
	const TWITTER_COOKIE = "gridSocialBoxesTwitterCookies";
	const INSTAGRAM_COOKIE = "gridSocialBoxesInstagramCookies";
	const YOUTUBE_COOKIE = "gridSocialBoxesYouTubeCookies";

	// ---------------------------------------------
	// expose api
	// ---------------------------------------------
	const api = window.GridSocialBoxes_API = {};
	api.isFacebookAllowed = isCookieAllowed.bind(undefined, FACEBOOK_COOKIE);
	api.isTwitterAllowed = isCookieAllowed.bind(undefined,TWITTER_COOKIE);
	api.isInstagramAllowed = isCookieAllowed.bind(undefined, INSTAGRAM_COOKIE);
	api.isYouTubeAllowed = isCookieAllowed.bind(undefined, YOUTUBE_COOKIE);
	api.isCookieAllowed = isCookieAllowed;

	// ---------------------------------------------
	// cookies
	// ---------------------------------------------
	function isCookieAllowed(name, set) {
		if(typeof name === typeof undefined) throw Error("Parameter cookie name missing.");
		if(typeof set !== typeof true){
			return cookieExists(name);
		}
		if(set){
			setCookie(name, true);
		} else {
			eraseCookie(name);
		}
		return name+" is "+set;
	}
	function setCookie(cname, cvalue, exdays) {
		const d = new Date();
		d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
		const expires = "expires="+d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
	}

	function getCookie(cname) {
		const name = cname + "=";
		const decodedCookie = decodeURIComponent(document.cookie);
		const ca = decodedCookie.split(';');
		for(let i = 0; i <ca.length; i++) {
			let c = ca[i];
			while (c.charAt(0) === ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) === 0) {
				return c.substring(name.length, c.length);
			}
		}
		return "";
	}

	function cookieExists(cname) {
		const cookieValue = getCookie(cname);
		return (cookieValue !== "")
	}

	function eraseCookie(name) {
		createCookie(name,"",-1);
	}


})();