<?

function __autoload($class)
{

	$file = str_replace("_", "/", strtolower($class)).".php";
//	$file = strtolower($class).".php";
//

//	$application_path = web::instance()->getApplicationPath();
	$application_path = $_SERVER["DOCUMENT_ROOT"]."/../application/";
	$dirs = array(
		$application_path."models/$class.php",
		$application_path."controllers/$class.php",
		$application_path."components/$class.php",
//		$_SERVER["DOCUMENT_ROOT"]."/inc/$class.php",
		dirname(__FILE__)."/".$file,
		dirname(__FILE__)."/components/".$file,
		dirname(__FILE__)."/library/".$file,
	);

	foreach($dirs as $file)	{
		if(file_exists($file))	{
			include_once($file);
#			echo("Autoloading: $file");

			break;
		}
	}
}


function js($module) {
	$file = str_replace("::", "/", $module).".js";
	$directories = array("/js/", "/resources/js/");
	foreach($directories as $dir)
		if(file_exists($_SERVER["DOCUMENT_ROOT"].basename("/").$dir.$file) || file_exists(dirname(__file__)."/".$dir.$file) )
			return  "<script src=\"$dir$file\" type=\"text/javascript\"></script>";
}

function js_once($module) {
	static $js_archivos = array();
	if(in_array($module, $js_archivos)) return;
	$js_archivos[]= $module;
	return js($module);
}

function css($module) {
	$file = str_replace("::", "/", $module).".css";
	$directories = array("/css/", "/resources/css/", "/resources/js/", "/resources/");
	foreach($directories as $dir) {
		if(file_exists($_SERVER["DOCUMENT_ROOT"].basename("/").$dir.$file) || file_exists(dirname(__file__)."/".$dir.$file)) {
			return "<link href=\"$dir$file\" rel=\"stylesheet\" type=\"text/css\" />";
		}
	}
}

function css_once($module) {
	static $css_archivos = array();
	if(in_array($module, $css_archivos)) return;
	$css_archivos[]= $module;
	return css($module);
}

function _t($str) {
	return l10n::instance()->get($str);
}

function __($str) {
	return l10n::instance()->get($str);
}

function init_extjs($version = "ext") {
	return css_once("$version/resources/css/ext-all")."\n".
		css_once("$version/resources/css/xtheme-gray")."\n".
		js_once("$version/adapter/ext/ext-base")."\n".
		js_once("$version/ext-all")."\n".
//		js_once("ext/ext-all-debug").
		js_once("$version/build/locale/ext-lang-sp")."\n";
}

function make_link_resources() {
	$dir = $_SERVER["DOCUMENT_ROOT"].basename("/")."/resources";
	if(!is_link($dir))
		symlink(dirname(__FILE__).'/resources/', $dir);
}



function create_images_and_files_tables($database) {
	$database->exec("
						CREATE TABLE IF NOT EXISTS `files` (
						  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
						  `id` int(11) unsigned NOT NULL auto_increment,
						  `tipo` varchar(35) NOT NULL default '',
						  `nombre` varchar(255) NOT NULL default '',
						  `iditem` int(11) unsigned NOT NULL default '0',
						  `descripcion` varchar(100) default '',
						  `module` varchar(25) NOT NULL default '',
						  `field` varchar(25) NOT NULL default '',
						  `orden` smallint(6) default '0',
						  `extension` varchar(5) default '',
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
	");
	$database->exec("
						CREATE TABLE IF NOT EXISTS `images` (
						  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
						  `id` int(11) unsigned NOT NULL auto_increment,
						  `tipo` varchar(35) NOT NULL default '',
						  `nombre` varchar(255) NOT NULL default '',
						  `iditem` int(11) unsigned NOT NULL default '0',
						  `descripcion` varchar(100) default '',
						  `module` varchar(25) NOT NULL default '',
						  `field` varchar(25) NOT NULL default '',
						  `orden` smallint(6) default '0',
						  `extension` varchar(5) default '',
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
	");
	$database->exec("
						CREATE TABLE `l10n` (
						  `id` int(11) NOT NULL auto_increment,
						  `lang` varchar(6)  NOT NULL default '',
						  `model` varchar(125),
						  `field` varchar(255)  NOT NULL default '',
						  `row` int(11),
						  `data` text NOT NULL,
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
						");
	$database->exec("
				CREATE TABLE `users` (
				  `id` int(9) unsigned NOT NULL auto_increment,
				  `name` varchar(50) NOT NULL default '',
				  `user` varchar(25) NOT NULL default '',
				  `password` varchar(15) NOT NULL default '',
				  `roles` varchar(25) default '',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `id` (`id`)
				) TYPE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
//							UNIQUE(`lang`,`model`,`field`,`row`)
//						  KEY `new_index` (`lang`,`model`,`field`,`row`)
}

function convert_to_url($url) {
	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_', 'ñ' => 'n', 'Ñ' => 'n');
	$str=   str_replace(' ', '-', strtr(strtolower($url), $arr));
	return implode("/", array_map("urlencode", explode("/", $str)));
}

function convert_from_url($url) {
	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u');
	return str_replace('_', ' ', str_replace('-', ' ', strtr(strtolower($url), $arr)));
}

function url_to_sql($url) {	// for using with regexp
	$arr = array("_", "-");
	$url =  preg_replace("/(_|\-)/", '[ _\-]', strtolower($url));

	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_', 'ñ' => 'n', 'Ñ' => 'n');
	$url = strtr($url, $arr);

	$arr = array('n' => '[nñ]', 'a' => '[aá]', 'e' => '[eé]', 'i' => '[ií]', 'o' => '[oó]', 'u' => '[uú]');
	return str_replace(array_keys($arr), array_values($arr), $url);
}

function notildes($txt) {
	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U');
	return strtr($txt, $arr);

}

function query_string($no = array()) {
	if(!is_array($no)) $no = split(",", $no);
	 $variables = $request ? $request : $_REQUEST;
	$postString  ="";
	while(list($key, $val) = each($variables)) {
		$key = stripslashes($key); $val = stripslashes($val);
        $key = urlencode($key); $val = urlencode($val);
        if(isset($no) && !in_array($key, $no)) $postString .= $prevar."$key=$val&amp;";
	}
	return substr($postString, 0,strlen($postString) - 1);
}

function isIE() {
	$browser = strtolower($_SERVER['HTTP_USER_AGENT']);
	return(strpos($browser, "msie") > 0);
}

function isBOT() {
	return (((eregi("bot", $_SERVER["HTTP_USER_AGENT"]))
		 || (ereg("Google", $_SERVER["HTTP_USER_AGENT"]))
		 || (ereg("Slurp", $_SERVER["HTTP_USER_AGENT"]))
		 || (ereg("Scooter", $_SERVER["HTTP_USER_AGENT"]))
		 || (eregi("Spider",  $_SERVER["HTTP_USER_AGENT"]))
		 || (eregi("Infoseek", $_SERVER["HTTP_USER_AGENT"]))));
}

#

function split_csv($string) {
		if(preg_match_all("{(?:^|,)(?:'([^']+)'|([^,]*))}i", $string, $regs))	{
			$values = ($regs[1][0] == '')  ? $regs[2] : $regs[1];
		}
		return $values;
}

function tripoli() {
	return '
<link href="/resources/css/tripoli.simple.css" type="text/css" rel="stylesheet">
<!--[if IE]><link rel="stylesheet" type="text/css" href="/resources/css/tripoli.simple.ie.css"><![endif]-->';
}

function sanitize($input) {
	return htmlentities(strip_tags( $input ));
}

function date_to_sql($date) {
	list($day, $month, $year) = split('[/.-]', $date);
	return "$year-$month-$day";
}


function checkFileSafety($file) {
		$safeExtensions = array(
		  'html',
		  'htm',
		  'gif',
		  'jpg',
		  'jpeg',
		  'png',
		  'txt',
		  'avi',
		  'mp3',
		  'wav',
		  'pdf',
		  'doc',
		  'exe',
		  'zip',
		  'rar',
		  'ppt',
		  'pps',
		  'docx',
		  'xlsx',
		  'pptx',
		  'ogg',
		  'flv'
		);

		$path_parts = pathinfo($file['name']);
		$extension = $path_parts['extension'];

		if(!in_array(strtolower($extension), $safeExtensions)) {
			unlink($file['tmp_name']);
			return false;
		}
		return true;
}
?>
