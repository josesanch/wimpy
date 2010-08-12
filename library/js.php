<?php

class js {

	public function fancybox($easing  = false, $css = true) {
		$str = 	js_once("jquery")."\n	".
				js_once("jquery/fancybox/jquery.fancybox-1.3.1.pack")."\n	".
				($easing ? js_once("jquery/fancybox/jquery.easing-1.3.pack") : "");

		if ($css) $str .=css_once("jquery/fancybox/jquery.fancybox-1.3.1")."\n";
		return $str;
	}

	public function validate() {
		return 	js_once("jquery")."\n	".
				js_once("jquery/validate")."\n	".
				js_once("jquery/validate/messages_es")."\n	".
				js_once("jquery/metadata");
	}

	public function jqueryui()
	{
		return
			js_once("jquery")."\n	".
			js_once("jquery/ui")."\n	";
	}
}
