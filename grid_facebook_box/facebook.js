(function($,gsbFacebook){

	// initialize facebook function
	function init(){
		window.fb_inited = true;
		window.fbAsyncInit = function() {
			FB.init({
				appId      : gsbFacebook.appid,
				xfbml      : true,
				version    : 'v2.8'
			});
		};

		$(".fb-post").each(function(){
			this.setAttribute("data-width", this.parentNode.clientWidth);
		});

		(function(d, s, id){
			let js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	}

	// init facebook
	window.fb_inited = window.fb_inited || false;
	if(!window.fb_inited){
		if(gsbFacebook.lazy){
			$(function(){
				$(".fb-post").after(
					$("<button/>")
						.addClass("grid-social-box__init-facebook")
						.text("Load facebook")
						.on("click", function(){
							$(window).trigger("initFacebook");
						})
				);
			});
			$(window).on("initFacebook", function(){
				init();
				$(".grid-social-box__init-facebook").remove();
			});
		} else {
			init();
		}
	}
})(jQuery, GridSocialBoxes_Facebook);