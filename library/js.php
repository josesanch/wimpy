<?php

class js {

	public function fancybox() {
		return 	js_once("jquery")."\n	".
				js_once("jquery/fancybox/jquery.fancybox-1.2.1.pack")."\n	".
				css_once("jquery/fancybox/jquery.fancybox")."\n";
	}

	public function validate() {
		return 	js_once("jquery")."\n	".
				js_once("jquery/validate")."\n	".
				js_once("jquery/validate/messages_es")."\n	".
				js_once("jquery/metadata");
	}
}

?>
