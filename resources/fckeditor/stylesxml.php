<?
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n\n";
$txt = fread(fopen($_SERVER["DOCUMENT_ROOT"].$_REQUEST["file"], "r"), filesize($_SERVER["DOCUMENT_ROOT"].$_REQUEST["file"]));
preg_match_all("/\.(\w+)\s*{/", $txt, $arr);
echo "<Styles>\n";
foreach($arr[1] as $style)
{
	echo "<Style name=\"$style\" element=\"span\">\n<Attribute name=\"class\" value=\"$style\" />\n</Style>\n";
	
}
echo "</Styles>\n";
?>