<?php

class sitemapWeb {

	private $urls;
	private $head = '<ul class="sitemap">';
	private $foot = '</ul>';

	public function __construct() {
		$this->urls = array();
	}

	public function add($name, $url = null, $childs = null) {
		$this->urls[]= array($name, $url, $childs);
		return $this;
	}

	public function toHtml($level = 1) {
		if(count($this->urls) == 0) return;
		$str = $this->head;
		foreach($this->urls as $url) {
			$str .= "
					<li class='level-".$level."'>
						<a href='".$url[1]."'>".$url[0]."</a>";
			if($url[2]) $str .= is_string($url[2]) ?  $url[2] : $url[2]->toHtml($level + 1);
			$str .= "
					</li>";

		}
		$str .= $this->foot;
		return $str;
	}
}

?>
