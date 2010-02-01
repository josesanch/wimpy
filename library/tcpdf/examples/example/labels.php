<?php

define("K_TCPDF_EXTERNAL_CONFIG", true);
define("K_PATH_MAIN", $_SERVER["DOCUMENT_ROOT"] . "/path/tcpdf/tcpdf_1_53_0_TC030_php4/");
define("K_PATH_URL", "./tcpdf_1_53_0_TC030_php4/");
define("FPDF_FONTPATH", K_PATH_MAIN."fonts/");
define("K_PATH_CACHE", K_PATH_MAIN."cache/");
define("K_PATH_URL_CACHE", K_PATH_URL."cache/");
define("K_PATH_IMAGES", K_PATH_MAIN."images/");
define("K_BLANK_IMAGE", K_PATH_IMAGES."_blank.png");
define("K_CELL_HEIGHT_RATIO", 1.25);
define("K_SMALL_RATIO", 2/3);

require_once("tcpdf.php");

$fonts = array("sans_serif" => "FreeSans", "serif" => "FreeSerif", "monospace" => "FreeMono");
$format = array("name" => "ppppppp", "paper-size" => "A4", "paper_width" => 210, "paper_height" => 270, "marginLeft" => 5, "marginTop" => 5, "NX" => 2, "NY" => 6, "SpaceX" => 3, "SpaceY" => 3, "width" => 90, "height" => 40, "metric"=> "mm", "font-size" => 14);
$styleBorder = array("width" => 0.2, "cap" => "round", "join" => "round", "dash" => "2,8", "phase" => 0, "color" => array(0, 127, 127));
$pdf = new PDF_Label2($format, "mm", 2, 1, true, $styleBorder, "H");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont("FreeSans", null, 10);

// Stylesheet
$pdf->SetStyle("p", $fonts["sans_serif"], "N", 8, "0,0,0", 0);
$pdf->SetStyle("p2", $fonts["sans_serif"], null, 8, null, 15);
$pdf->SetStyle("h1", $fonts["serif"], "B", 20, "100,100,0", 0);
$pdf->SetStyle("h2", $fonts["sans_serif"], "B", 12, null, 0);
$pdf->SetStyle("b", null, "B", null, null);
$pdf->SetStyle("i", null, "I", null, null);
$pdf->SetStyle("a",$fonts["serif"],"BU",7,"0,0,255");
$pdf->SetStyle("city",$fonts["monospace"],null,15,null);
$pdf->SetStyle("country",$fonts["monospace"],"BIU",5,null);

// Labels
for($i = 1; $i < 10; $i++) {
	$pdf->Add_PDF_Label("<h1>Code_very_very_very_very_large $i</h1>", "C", "M");
	$pdf->Add_PDF_Label("<h2>Laurent Laurent Laurent Laurent Laurent $i</h2><p><i>Immeuble Titi</i></p><p><i>Street</i>: av. fragonard</p><p2>06000, <city>NICE</city>, <country>FRANCE</country></p2><p>E-mail: <a href='mailto:laurent_$i@fpdf.net'>laurent_$i@fpdf.net</a></p>",
	                    "R", "B", "10,2,2,10", 1.20, 0.00);
	$pdf->Add_PDF_Label("<h2>Laurent Laurent Laurent Laurent Laurent $i</h2><p><i>Immeuble Titi</i></p><p><i>Street</i>: av. fragonard</p><p2>06000, NICE, FRANCE</p2><p>E-mail: <a href='mailto:laurent_$i@fpdf.net'>laurent_$i@fpdf.net</a></p>",
	                    "L", "T", "10,2,2,10", 1.20, 0.00);
}

// Create PDF
$pdf->Output();

?>
