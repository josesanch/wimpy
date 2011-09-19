<?php
spl_autoload_register("__wimpyAutoload");

function __wimpyAutoload($class)
{
	$file = str_replace("_", "/", $class).".php";
    $lowerFile =  strtolower($file);
    $lowerClass = strtolower($class);
//	$file = strtolower($class).".php";
//

//	$application_path = web::instance()->getApplicationPath();
	$application_path = $_SERVER["DOCUMENT_ROOT"]."/../application/";
	$dirs = array(
		$application_path."models/$class.php",
        $application_path."models/$lowerClass.php",
		$application_path."controllers/$class.php",
        $application_path."controllers/$lowerClass.php",
		$application_path."components/$class.php",
        $application_path."components/$lowerClass.php",
//		$_SERVER["DOCUMENT_ROOT"]."/inc/$class.php",
		dirname(__FILE__)."/".$file,
        dirname(__FILE__)."/".$lowerFile,
		dirname(__FILE__)."/components/".$file,
        dirname(__FILE__)."/components/".$lowerFile,
		dirname(__FILE__)."/library/".$file,
        dirname(__FILE__)."/library/".$lowerFile,
	);

	foreach($dirs as $file)	{
		if(file_exists($file))	{
			include_once($file);
//			echo("* Autoloading: $file\n");

			break;
		}
	}
}

function setIncludePathForZend()
{
    set_include_path(dirname(__FILE__)."/library/" . PATH_SEPARATOR . get_include_path());
}

function js($module)
{
	$file = str_replace("::", "/", $module).".js";
	$directories = array("/js/", "/resources/js/");

	switch ($module) {
		case "jquery/ui":
            return "<script src=\"/resources/js/jquery/jquery-ui-1.8.12.custom.min.js\" type=\"text/javascript\"></script><link href=\"/resources/js/jquery/ui/smoothness/jquery-ui-1.8.12.custom.css\" rel=\"stylesheet\" type=\"text/css\" />";
	}


    foreach($directories as $dir)
		if(file_exists($_SERVER["DOCUMENT_ROOT"].basename("/").$dir.$file) || file_exists(dirname(__file__)."/".$dir.$file) )
			return  "<script src=\"$dir$file\" type=\"text/javascript\"></script>";


    switch ($module) {
    case "jquery":
        return "<script src=\"/resources/js/jquery-1.5.2.min.js\" type=\"text/javascript\"></script>";
    }

}

function js_once($module)
{
	static $js_archivos = array();
	if(in_array($module, $js_archivos)) return;
	$js_archivos[]= $module;
	return js($module);
}

function css($module)
{
	$file = str_replace("::", "/", $module).".css";
	$directories = array("/css/", "/resources/css/", "/resources/js/", "/resources/");
	foreach($directories as $dir) {
		if(file_exists($_SERVER["DOCUMENT_ROOT"].basename("/").$dir.$file) || file_exists(dirname(__file__)."/".$dir.$file)) {
//			return "<link href=\"$dir$file\" type=\"text/css\" />";
            return "<link rel=\"stylesheet\" type=\"text/css\" href=\"$dir$file\"/>";

            // rel=\"stylesheet\"
		}
	}
}

function css_once($module)
{
	static $css_archivos = array();
	if(in_array($module, $css_archivos)) return;
	$css_archivos[]= $module;
	return css($module);
}

function _t($str)
{
	return l10n::instance()->get($str);
}

function __($str)
{
	return l10n::instance()->get($str);
}

function init_extjs($version = "ext")
{
	return css_once("$version/resources/css/ext-all")."\n".
		css_once("$version/resources/css/xtheme-gray")."\n".
		js_once("$version/adapter/ext/ext-base")."\n".
		js_once("$version/ext-all")."\n".
//		js_once("ext/ext-all-debug").
		js_once("$version/build/locale/ext-lang-sp")."\n";
}

function make_link_resources()
{
	$dir = $_SERVER["DOCUMENT_ROOT"].basename("/")."/resources";
	if(!is_link($dir))
		symlink(dirname(__FILE__).'/resources/', $dir);
}



function create_images_and_files_tables($database)
{
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

function convert_to_url($url)
{

	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_', 'ñ' => 'n', 'Ñ' => 'n');
	$str =   str_replace(' ', '-', strtr(strtolower($url), $arr));
	return implode("/", array_map("rawurlencode", explode("/", $str)));
}

function convert_from_url($url)
{
	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u');
	$url = implode("/", array_map("rawurldecode", explode("/", $url)));
	return str_replace('_', ' ', str_replace('-', ' ', strtr(strtolower($url), $arr)));
}

function url_to_sql($url) {	// for using with regexp
	$url = strtolower($url);

	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_', 'ñ' => 'n', 'Ñ' => 'n');
	$url = strtr($url, $arr);

	$arr = array('n' => '[nñ]', 'a' => '[aá]', 'e' => '[eé]', 'i' => '[ií]', 'o' => '[oó]', 'u' => '[uú]');
	$url = str_replace(array_keys($arr), array_values($arr), $url);
	return  preg_replace("/(_|\-)/", '([ _\-]|[[.solidus.]])', $url);
}

function notildes($txt) {
	$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U');
	return strtr($txt, $arr);

}

function query_string($no = array()) {
	if(!is_array($no)) $no = explode(",", $no);
	 $variables = $request ? $request : $_REQUEST;
	$postString  ="";
	while(list($key, $val) = each($variables)) {
		$key = stripslashes($key); $val = stripslashes($val);
        $key = urlencode($key); $val = urlencode($val);
        if(isset($no) && !in_array($key, $no)) $postString .= $prevar."$key=$val&amp;";
	}
	return substr($postString, 0,strlen($postString) - 1);
}

function isIE($version = null) {
	$browser = strtolower($_SERVER['HTTP_USER_AGENT']);
	if(preg_match("/msie\s*(\d+)/", $browser, $regs)) {
		if ($version) return $version == $regs[1];
		return true;
	}
	return false;
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

function date_to_sql($date, $lang = "es") {
    if ($lang == "es")
        list($day, $month, $year) = preg_split('/[\/.-]/', $date);
    else
        list($month, $day, $year) = preg_split('/[\/.-]/', $date);

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
        'xls',
        'pptx',
        'ogg',
        "webm",
        'flv',
        "ogv",
        "m4v",
        "csv"
    );

    $path_parts = pathinfo($file['name']);
    $extension = $path_parts['extension'];

    if(!in_array(strtolower($extension), $safeExtensions)) {
        error_log("Upload file:".$file['tmp_name']." not allowed");
        unlink($file['tmp_name']);
        return false;
    }

    return true;
}

function tlink($url)
{
	if(l10n::instance()->isNotDefault())
	    return "/".l10n::instance()->getSelectedLang().$url;
    else
        return $url;

}

function includePdfPlugins()
{
	include_once(dirname(__FILE__)."/library/tcpdf.php");
	include_once(dirname(__FILE__)."/library/tcpdf/tcpdf_plugins.php");
}

function getMimeType($file)
{
	if ($mime_type = mime_content_type($file)) return $mime_type;

	$mime_type = @shell_exec("file -i -b $file");
	$mime_type = array_shift(explode(";",$mime_type));
	return $mime_type;
}
