<?php

class sitemap {

	private $urls;
	private $head = '<?xml version="1.0" encoding="UTF-8"?>
	<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

	public function __construct() {
		$this->urls = array();
		$this->add("/");
	}

	public function add($url, $priority = 1.0, $frequency = "daily") {
		$this->urls[]= array($url, $priority, $frequency);
		return $this;
	}

	public function display() {
//	var_dump($this->urls);
		header("Content-type: text/xml");
		echo $this->head;
		foreach($this->urls as $url) {
			echo "<url>
					  <loc>http://".$_SERVER["HTTP_HOST"].$url[0]."</loc>
					  <priority>".$url[1]."</priority>
					  <changefreq>".$url[2]."</changefreq>
				</url>";

		}
		echo "</urlset>";
	}
}

?>
