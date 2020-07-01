(function($, api, fb){

	// ----------------------------------------
	// expose to public use
	// ----------------------------------------
	fb.buildButton = function(i18n){
		return $("<button/>")
			.addClass("grid-social-box__init-facebook")
			.text(i18n.enable_button)
	};
	fb.buttonAlter = function(button){ };

	// ----------------------------------------
	// constants
	// ----------------------------------------
	const INIT_FACEBOOK_EVENT = "grid-social-boxes-facebook-init";
	const config = fb.config;
	const selector = fb.selector;
	const i18n = fb.i18n;

	// ----------------------------------------
	// application
	// ----------------------------------------
	function updateWidthAttribute() {
		$(selector.target).each(function(){
			this.setAttribute("data-width", this.parentNode.clientWidth);
		});
	}

	if(config.lazy && !api.isFacebookAllowed()){
		$(function(){
			const button = fb.buildButton(i18n);
			button.on("click", function(){
				$(window).trigger(INIT_FACEBOOK_EVENT);
			});
			$(selector.target).after(button);
			fb.buttonAlter(button);
		});
		$(window).on(INIT_FACEBOOK_EVENT, function(){
			api.isFacebookAllowed(true);
			initFacebook();
			$(".grid-social-box__init-facebook").remove();
		});
	} else {
		initFacebook();
		updateWidthAttribute();
	}

	window.fb_inited = window.fb_inited || false;
	function initFacebook(){
		if(window.fb_inited) return;
		window.fb_inited = true;
		window.fbAsyncInit = function() {
			FB.init({
				appId      : config.facebook_app_id,
				xfbml      : true,
				version    : 'v2.8'
			});
		};
		(function(d, s, id){
			let js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/"+config.lang+"/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	}



})(jQuery, GridSocialBoxes_API, GridSocialBoxes_Facebook);