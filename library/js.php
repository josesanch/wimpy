<?php

class js {

	public function fancybox($easing  = false) {
		return 	js_once("jquery")."\n	".
				js_once("jquery/fancybox/jquery.fancybox-1.3.0.pack")."\n	".
				($easing ? js_once("jquery/fancybox/jquery.easing-1.3.pack") : "").
				css_once("jquery/fancybox/jquery.fancybox-1.3.0")."\n";
	}

	public function validate() {
		return 	js_once("jquery")."\n	".
				js_once("jquery/validate")."\n	".
				js_once("jquery/validate/messages_es")."\n	".
				js_once("jquery/metadata");
	}
}

?>
