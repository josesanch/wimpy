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
$pdf=new PDF_Table("P", "mm", "A4");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont("FreeSans", null, 10);

// Stylesheet
$pdf->SetStyle("p",  $fonts["sans_serif"], "N",  8, "0,0,0", 0);
$pdf->SetStyle("h1", $fonts["serif"], "B", 20, "255,255,255", 0);
$pdf->SetStyle("h2", $fonts["serif"], "B", 15, "0,0,0", 0);
$pdf->SetStyle("h3", $fonts["serif"], "B", 10, "0,0,0", 0);
$pdf->SetStyle("big", null, null, 20, "127,0,127");
$pdf->SetStyle("b", null, "B", null, null);
$pdf->SetStyle("i", null, "I", null, null);
$pdf->SetStyle("neg", null, "I", null, "255,0,0");
$pdf->SetStyle("a", $fonts["sans_serif"], "BU", null, "0,0,255");

$pdf->SetMargins(10, 10);

$pdf->AddPage();

$tbSyle = array("tb_align" => "C",
                "column_width" => array(40, 30, 30, 30, 40),
                "align" => "L",
                "valign" => "M",
                "padding" => 0,
                "leading" => 1.00,
                "leadingParagraph" => 0.00,
                "bg_color" => array(255, 255, 255));
$border_table = array("width" => 0.75, "dash" => 0, "color" => array(0, 0, 0));
$border_column = array("width" => 0.50, "dash" => 0, "color" => array(0, 0, 0));
$tbBorderStyle = array("table" => array("all" => $border_table),
                       "head_body" => $border_table,
                       "column_header" => array($border_table, $border_column, $border_column, $border_column),
                       "column_body" => array($border_table, $border_column, $border_column, $border_column),
                       "row_header" => array(array("width" => 0.50, "dash" => 0, "color" => array(0, 0, 0)),
                                             array("width" => 0.25, "dash" => 0, "color" => array(0, 0, 0))),
                       "row_body" => array(array("width" => 0.5, "dash" => "3,3", "color" => array(255, 0, 255)),
                                           array("width" => 0.5, "dash" => 0, "color" => array(255, 0, 255))));
$tbBodyData = null;
for ($i = 1; $i < 20; $i++) {
  $tbBodyData[] = array(array("text" => "<h3><i>Category</i> $i</h2>",
                              "rowspan" => 3),
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}a</p>",
                              "rowspan" => 2),
                        array("text" => "<p><b>Title</b> {$i}aa</p>"),
                        array("text" => "<p>Code {$i}aa</p>"),
                        array("text" => "<p>" . sprintf("%.2f", $i * 10) . "</p>"));
  $tbBodyData[] = array(null, null,
                        array("text" => "<p><b>Title</b> {$i}ab</p>"),
                        array("text" => "<p>Code {$i}ab</p>"),
                        array("text" => "<p>20.00</p>"));
  $tbBodyData[] = array(null,
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}b</p>"),
                        array("text" => "<p><b>Title</b> {$i}," . ($i + 1) . "," . ($i + 2) . "b</p>",
                              "rowspan" => 3),
                        array("text" => "<p>Code {$i}b</p>",
                              "bg_color" => array(255, 120, 120)),
                        array("text" => "<p><neg>-" . sprintf("%.2f", $i * 20) . "</neg></p>"));
  $i++;
  $tbBodyData[] = array(array("text" => "<h3><i>Category</i> {$i}</h2>"),
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}</p>"),
                        null,
                        array("text" => "<p>Code {$i}</p>"),
                        array("text" => "<p>" . sprintf("%.2f", $i * 20) . "</p>",
                              "rowspan" => 2));
  $i++;
  $tbBodyData[] = array(array("text" => "<h3><i>Category</i> {$i}</h2>",
                              "rowspan" => 5),
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}a</p>"),
                        null,
                        array("text" => "<p>Code {$i}a</p>",
                              "border" => array("T" => array("width" => 2, "dash" => "6,12", "color" => array(177, 0, 255)))),
                        null);
  $tbBodyData[] = array(null,
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}b</p>"),
                        array("text" => "<p><b>Title</b> and Code {$i}bc, with special border</p>",
                              "colspan" => 2, "rowspan" => 2,
                              "border" => array("all" => array("width" => 3, "dash" => "12,32", "color" => array(255, 177, 0)))),
                        null,
                        array("text" => "<p>" . sprintf("%.2f", $i * 20) . "</p>",
                              "padding" => 10));
  $tbBodyData[] = array(null,
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}c</p>"),
                        null, null ,
                        array("text" => "<p>" . sprintf("%.2f", $i * 30) . "</p>",
                              "height_min" => 5));
  $tbBodyData[] = array(null,
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}d</p>",
                              "align" => "C", "valign" => "B"),
                        array("text" => "<p><b>Title</b> {$i}d. <big>Wordlong</big></p>",
                              "align" => "C"),
                        array("text" => "<p>Code {$i}d</p>"),
                        array("text" => "<p><neg>-" . sprintf("%.2f", $i * 40) . "<neg></p>"));
  $tbBodyData[] = array(null,
                        array("text" => "<p><a href='http://www.foo.com/'>Special</a> {$i}e</p>"),
                        array("text" => "<p><b>Title</b> {$i}e.</p>"),
                        array("text" => "<p>Code {$i}e</p>"),
                        array("text" => "<p>" . sprintf("%.2f", $i * 40) . "</p>"));
}
$tbHeaderData = array(array(array("text" => "<h1>Category</h1>",
                                  "rowspan" => 3),
                            array("text" => "<h1>Data</h1>",
                                  "colspan" => 4),
                            null, null, null),
                      array(null,
                            array("text" => "<h2>Special</h2>",
                                  "rowspan" => 2),
                            array("text" => "<h2>Others</h2>",
                                  "colspan" => 3, "align" => "C"),
                            null, null),
                      array(null, null,
                            array("text" => "<h3>Title</h3>"),
                            array("text" => "<h3>Code</h3>"),
                            array("text" => "<h3>Quantity</h3>")));
$tbColumnBodyStyle = array(array("align"=> "C", "valign" => "M", "bg_color" => array(200, 200, 200)),
                           array("align"=> "L", "valign" => "M"),
                           array("align"=> "L", "valign" => "M"),
                           array("align"=> "C", "valign" => "M"),
                           array("align"=> "R", "valign" => "M", "bg_color" => array(255, 255, 150)));
$tbRowBodyStyle = array(array("bg_color" => array(255, 255, 255), "height_min" => 10),
                        array("bg_color" => array(220, 255, 220)),
                        array("bg_color" => array(180, 255, 180)),
                        array("bg_color" => array(220, 255, 220)));
$tbColumnHeaderStyle = array(array("align"=> "C", "valign" => "B"),
                             array("align"=> "C", "valign" => "M"),
                             array("align"=> "L", "valign" => "M"),
                             array("align"=> "C", "valign" => "M"),
                             array("align"=> "R", "valign" => "M"));
$tbRowHeaderStyle = array(array("bg_color" => array(100, 100, 100)),
                          array("align"=> "C", "bg_color" => array(150, 150, 150)),
                          array("bg_color" => array(200, 200, 200)));
$pdf->Table($tbSyle, $tbBorderStyle, $tbBodyData, $tbColumnBodyStyle, $tbRowBodyStyle, $tbHeaderData, $tbColumnHeaderStyle, $tbRowHeaderStyle);

$pdf->Output();

?>
