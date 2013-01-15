<?php
DEFINE(APPLICATION_PATH, $_SERVER["DOCUMENT_ROOT"]."/../app/");
spl_autoload_register("__wimpyIntegrationSf");

function __wimpyIntegrationSf($class)
{
    $file = str_replace("_", "/", $class).".php";
    $lowerFile =  strtolower($file);
    $lowerClass = strtolower($class);


    $wimpy_path = dirname(__FILE__)."/../";
    $dirs = array(
        APPLICATION_PATH."/models/$class.php",
        APPLICATION_PATH."/models/$lowerClass.php",
        APPLICATION_PATH."/controllers/$class.php",
        APPLICATION_PATH."/controllers/$lowerClass.php",
        APPLICATION_PATH."/components/$class.php",
        APPLICATION_PATH."/components/$lowerClass.php",
        $wimpy_path.$lowerFile,
        $wimpy_path.$file,
        $wimpy_path."components/".$file,
        $wimpy_path."components/".$lowerFile,
        $wimpy_path."library/".$file,
        $wimpy_path."library/".$lowerFile,
    );

    foreach($dirs as $file)	{
        if(file_exists($file))	{
            include_once($file);
            return;
        }
    }
}


include_once("wimpy/web.php");