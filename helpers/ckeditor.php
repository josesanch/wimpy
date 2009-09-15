<?php

class helpers_ckeditor {
	protected $path = "/assets";

	public function upload() {
	    $file = array_shift($_FILES);

		if(is_uploaded_file($file['tmp_name']) && checkFileSafety($file)) {
			$dir = $_SERVER['DOCUMENT_ROOT'].$this->path;
       		if(!is_dir($dir)) mkdir($dir, 0755);
	   	   	move_uploaded_file($file['tmp_name'], $dir."/".$file['name']);
			echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(1, \''.$this->path."/".$file['name'].'\', \'\');</script>';
		} else {

		}
	}

	public function destroy($file) {
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->path;
		unlink("$dir/".urldecode($file));
	}


	public function browse() {

		echo js("jquery");
		echo <<<EOF
<html>
<body>
		<script type="text/javascript" src='/resources/uploadify/jquery.uploadify.v2.0.3.min.js'></script>
		<script type='text/javascript' src='/resources/uploadify/swfobject.js'></script>
		<link href='/resources/admin/css/admin.css' rel="stylesheet" type="text/css" />
		<link href='/resources/uploadify/uploadify.css' rel='stylesheet' type='text/css' />
		<style type='text/css'>

		</style>
		<div id='container-files'>
EOF;
		$this->files();
		echo <<<EOF
		</div>
		<div id='fileQueue'></div>
		<div style='clear: both; float: right; width: 120px; margin-top: 5px;margin-right: 10%;'>
			<input type='file' name='uploadify_button' id='uploadify_button' />
		</div>
		<div id='loader'></div>
		<script type='text/javascript'>

			function select_image(item) {
				window.opener.CKEDITOR.tools.callFunction(1, item, '');
				window.close();
			}

			$(document).ready(function() {
				$('#uploadify_button').uploadify({
					'uploader'       : '/resources/uploadify/uploadify.swf',
					'script'         : '/helpers/ckeditor/upload/',
					'cancelImg'      : '/resources/uploadify/cancel.png',
					'folder'         : 'uploads',
					'queueID'        : 'fileQueue',
					'auto'           : true,
					'multi'          : true,
					'fileDataName' 	: 'file',
					'onComplete' : function () {
						$('#container-files').load('/helpers/ckeditor/files');
					}
				});
			});


		</script>
</body>
</html>
EOF;

	}

	public function files() {
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->path;
		$files = glob($dir."/*");
		echo "<h5 class='images'>".count($files)." archivos</h5>
				<ul id='files' class='images-dataview clearfix' style='width: 80%; margin: auto; height: 70%; overflow: auto;'>";

		foreach($files as $name) {
			$item = new helpers_files($name);
			$str .="
				<li id='images-$item->nombre'>
					<div>
						<a href='javascript:select_image(\"".$this->path."/".$item->nombre."\")'><img src='".$item->src("80x60", "INABOX")."' title='$item->nombre ".html_template_filters::bytes($item->size())."'/></a>
						<a href='#' class='images-delete' id='".urlencode($item->nombre)."'><img src='/resources/icons/cross.gif' border='0'/></a>
					</div>
						<p class='editable' id='file-$item->id'>$item->nombre</p>
				</li>
					";

		}
		$str .= "</ul>
		<script type='text/javascript'>
			$('a.images-delete').bind('click', delete_image);
			function delete_image(e) {
				id = this.id;
				$('#loader').load('/helpers/ckeditor/destroy/' + id, function() {
					$('#container-files').load('/helpers/ckeditor/files');
				});
				return false;
			}

		</script>
		";
		echo $str;
	}

}


?>
