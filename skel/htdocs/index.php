<?
include_once("wimpy/web.php");
$web = new web(array("mysql:host=localhost;dbname=", "root"));
$web->run();
?>
