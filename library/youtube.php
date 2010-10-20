<?php

class youtube
{
		public $id;
		private $info;

		public function __construct($id) {
			if (substr($id, 0, 7) == "http://") {	// Parse the url to obtain the id
				preg_match("/v=([^&]+)/", $id, $arr);
				$id = $arr[1];
			}
			$this->id = $id;
		}

		public function getThumbnail($pos = 0) {
			return "http://img.youtube.com/vi/$this->id/$pos.jpg";
		}

		public function getDuration() {
			$this->loadInfo();
			preg_match("/duration='(\d+)'/", $this->info, $arr);
			return $arr[1];
		}

		public function getTitle() {
			$this->loadInfo();
			preg_match('/<title[^>]*>([^<]+)<\/title>/', $this->info, $arr);
			return $arr[1];
		}

		public function getAuthor() {
			$this->loadInfo();
			preg_match('/<author><name[^>]*>([^<]+)<\/name>/', $this->info, $arr);
			return $arr[1];
		}

		public function getPlayer($size = "425x350") {
			$size = explode("x", $size);
			return '<object type="application/x-shockwave-flash" style="width:'.$size[0].'px; height:'.$size[1].'px;" data="http://www.youtube.com/v/'.$this->id.'?rel=0" wmode="transparent">
					<param name="wmode" value="transparent">
					<param name="movie" value="http://www.youtube.com/v/'.$this->id.'?rel=0" />
					</object>';
		}

		private function loadInfo() {
			if(!$this->info) $this->info = file_get_contents("http://gdata.youtube.com/feeds/api/videos/$this->id");
		}


}
