<?php


namespace Palasthotel\Grid\SocialBoxes;


class OEmbed {

	/**
	 * @param string $videoId
	 * @param array $extend extend default options
	 * @return string
	 */
	public function getYouTubeHTML( $videoId, $extend = array()){
		$options = array_merge(array(
			"scheme" => "http",
			"show_info" => 0,
			"related" => 0,
		), $extend);

		$content_url = $options["scheme"]."://www.youtube.com/watch?v=".urlencode($videoId);
		$url=$options['scheme']."://www.youtube.com/oembed?url=".$content_url."&format=json";

		$request=curl_init($url);
		curl_setopt($request,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($request,CURLOPT_HEADER,FALSE);
		$result=curl_exec($request);
		if($result===FALSE)
		{
			var_dump(curl_error($request));
			die();
		}
		$responseCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
		curl_close($request);
		$html = "";
		if($responseCode == 200){
			$result=json_decode($result);
			if(is_object($result)) $html = $result->html;
		}


		$url_show_info = "&showinfo=";
		if($options["show_info"]){
			$url_show_info.="1";
		} else {
			$url_show_info.="0";
		}
		$url_related = "&rel=";
		if($options["related"]){
			$url_related.="1";
		} else {
			$url_related.="0";
		}

		$html = str_replace("src=\"http://", "src=\"".$options["scheme"]."://", $html);
		$html = str_replace('feature=oembed', 'feature=oembed&wmode=transparent&html5=1'.$url_related.$url_show_info, $html);

		return $html;
	}

}