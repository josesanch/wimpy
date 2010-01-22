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
$pdf = new PDF_WriteTag2("P", "mm", "A4");
$pdf->SetMargins(30,15,25);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont("FreeSans", null, 10);
$pdf->AddPage();

// ######################## Stylesheet
$pdf->SetStyle("p",$fonts["monospace"],"N",10,"10,100,250",0);
$pdf->SetStyle("h1",$fonts["serif"],"N",15,"102,0,102",0);
$pdf->SetStyle("a",$fonts["serif"],"BU",9,"0,0,255");
$pdf->SetStyle("pers",$fonts["serif"],"I",0,"255,0,0");
$pdf->SetStyle("place",$fonts["sans_serif"],"U",0,"153,0,0");
$pdf->SetStyle("vb",$fonts["serif"],"B",20,"102,153,153");

$pdf->SetStyle("sub",null,null,6,"0,0,0");

$pdf->SetStyle("div",null,null,null,null);

// ######################### Title
$x = $pdf->GetX();
$y = $pdf->GetY();
$txt="<h1>Le petit chaperon rouge</h1>";
$border = array("all" => array("width" => 0.5, "cap" => "butt", "join" => "miter", "dash" => "10,20", "phase" => 10, "color" => array(102, 0, 102)));
$pdf->WriteTag(60, 0, $txt, $border, "C", 1, array(255, 255, 204), "T", 5);

$pdf->SetXY($x + 70, $y);
$txt="<h1>Le petit chaperon rouge</h1>";
$border = array("all" => array("width" => 0.5, "cap" => "butt", "join" => "miter", "dash" => "10,20", "phase" => 10, "color" => array(102, 0, 102)));
$pdf->WriteTag(20, 0, $txt, $border, "C", 1, array(255, 255, 204), "T", 0);

$pdf->SetXY($x + 100, $y);
$txt="<h1>Le petit chaperon rouge</h1>";
$border = array("all" => array("width" => 0.5, "cap" => "butt", "join" => "miter", "dash" => "10,20", "phase" => 10, "color" => array(102, 0, 102)));
$pdf->WriteTag(17, 0, $txt, $border, "C", 1, array(255, 255, 204), "T", 0);

$pdf->Ln(15);

// ######################### Text
$txt="<p>Il <vb>était</vb> une fois <pers>une petite fille</pers> de <place>village</place>, la plus jolie qu'on <vb>eût su voir</vb>:<div offset='5'><vb>(Note 1)</vb></div> <pers>sa mère</pers> en <vb>était</vb> folle, et <pers>sa mère<div offset='-2'>(Note 2)</div> grand</pers> plus folle encore. Cette <pers>bonne femme</pers> lui <vb>fit faire</vb> un petit chaperon rouge, qui lui <vb>seyait</vb> si bien que par tout on <vb>l'appelait</vb> <pers>le petit Chaperon rouge</pers>.</p>
<p>Un jour <pers>sa mère</pers> <vb>ayant cuit</vb> et <vb>fait</vb> des galettes, <vb>lui dit</vb>:</p>
<p><div align='C'>« <vb>Va voir</vb> comment <vb>se porte</vb> <pers>la mère-grand</pers>; car on <vb>m'a dit</vb> qu'elle <vb>était</vb> malade: <vb>porte-lui</vb> une galette et ce petit pot de beurre. »</div></p>
<p><pers>Le petit Chaperon rouge</pers> <vb>partit</vb> aussitôt pour <vb>aller</vb> chez <pers>sa mère-grand</pers>, qui <vb>demeurait</vb> dans <place>un autre village</place>. En passant dans <place>un bois</place>, elle <vb>rencontra</vb> compère <pers>le Loup</pers>, qui <vb>eut bien envie</vb> de <vb>la manger</vb>; mais il <vb>n'osa</vb> à cause de quelques <pers>bûcherons</pers> qui <vb>étaient</vb> dans <place>la forêt</place>.</p>";
$border = array("L" => 0,
               "T" => array("width" => 0.1, "cap" => "butt", "join" => "miter", "dash" => "20,10", "phase" => 10, "color" => array(255,0,0)),
               "R" => array("width" => 0.2, "cap" => "round", "join" => "miter", "dash" => 0, "color" => array(0,255,0)),
               "B" => array("width" => 0.3, "cap" => "square", "join" => "miter", "dash" => "30,10,5,10"));
$pdf->WriteTag(0, 170, $txt, $border, "J", 0, null, "M", 7, 1.20, 1.20);
$pdf->Ln(5);

// ############################# Signature
$txt="<a href='http://www.pascal-morin.net'>Done by Pascal MORIN</a>";
$pdf->WriteTag(0, 0, $txt, 0, "R", 0, "T");

$pdf->Output();

?>
