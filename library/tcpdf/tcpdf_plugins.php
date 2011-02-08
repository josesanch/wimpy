<?php

/*

TCPDF --> PDF_Draw       --> WriteTag_PDF --> PDF_WriteTag2         --> PDF_Label --> PDF_Label2
         [SetLineStyle]      [tags]           [NbLinesAndParagraph]     [label]       [border,direction]
         [+Line]                              [HeightText]              [direction]   [+Add_PDF_Label]
         [+Rect]                              [+WriteTag(align,                       [-_Get_Height_Chars]
         [methods draw]                         valign,padding,                       [-Set_Font_Size]
         [NbLines]                              leading)]                             [-Set_Font_Name]
                                              [+EndParagraph]
                                                    |
                                                    V
                                                (PDF_Table)

*/

//include_once("tcpdf_1_53_0_TC030_php4/tcpdf.php");

/**
 * My extension for draws in TCPDF.
 *
 * Draws lines, rectagules, curves, polygons and more figures with complex line
 * style.
 * @package TCPDF
 * @author David Hernandez Sanz
 * @license Freeware
 */
class PDF_Draw extends TCPDF {
  /******************/
  /* Public methods */
  /******************/

  /**
   * Set line style.
   *
   * @param array $style Line style. Array with keys among the following:
   * <ul>
   *   <li>width (float): Width of the line in user units.</li>
   *   <li>cap (string): Type of cap to put on the line. Possible values are:
   * butt, round, square. The difference between "square" and "butt" is that
   * "square" projects a flat end past the end of the line.</li>
   *   <li>join (string): Type of join. Possible values are: miter, round,
   * bevel.</li>
   *   <li>dash (mixed): Dash pattern. Is 0 (without dash) or string with
   * series of length values, which are the lengths of the on and off dashes.
   * For example: "2" represents 2 on, 2 off, 2 on, 2 off, ...; "2,1" is 2 on,
   * 1 off, 2 on, 1 off, ...</li>
   *   <li>phase (integer): Modifier on the dash pattern which is used to shift
   * the point at which the pattern starts.</li>
   *   <li>color (array): Draw color. Format: array(red, green, blue).</li>
   * </ul>
   * @access public
   */
  function SetLineStyle($style) {
    extract($style);
    if (isset($width)) {
      $width_prev = $this->LineWidth;
      $this->SetLineWidth($width);
      $this->LineWidth = $width_prev;
    }
    if (isset($cap)) {
      $ca = array("butt" => 0, "round"=> 1, "square" => 2);
      if (isset($ca[$cap])) {
        $this->_out($ca[$cap] . " J");
      }
    }
    if (isset($join)) {
      $ja = array("miter" => 0, "round" => 1, "bevel" => 2);
      if (isset($ja[$join])) {
        $this->_out($ja[$join] . " j");
      }
    }
    if (isset($dash)) {
      $dash_string = "";
      if ($dash) {
        if (ereg("^.+,", $dash)) {
          $tab = explode(",", $dash);
        } else {
          $tab = array($dash);
        }
        $dash_string = "";
        foreach ($tab as $i => $v) {
          if ($i) {
            $dash_string .= " ";
          }
          $dash_string .= sprintf("%.2f", $v);
        }
      }
      if (!isset($phase) || !$dash) {
        $phase = 0;
      }
      $this->_out(sprintf("[%s] %.2f d", $dash_string, $phase));
    }
    if (isset($color)) {
      list($r, $g, $b) = $color;
      $this->SetDrawColor($r, $g, $b);
    }
  }

  /**
   * Draws a line between two points.
   *
   * Redefine from TCPDF. Adds line style.
   * @param float $x1 Abscissa of first point.
   * @param float $y1 Ordinate of first point.
   * @param float $x2 Abscissa of second point.
   * @param float $y2 Ordinate of second point.
   * @param array $style Line style. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default line style
   * (empty array).
   * @access public
   */
  function Line($x1, $y1, $x2, $y2, $style = array()) {
    if ($style) {
      $this->SetLineStyle($style);
    }
    parent::Line($x1, $y1, $x2, $y2);
  }

  /**
   * Draws a rectangle.
   *
   * Redefine from TCPDF. Adds border style and fill color.
   * @param float $x Abscissa of upper-left corner.
   * @param float $y Ordinate of upper-left corner.
   * @param float $w Width.
   * @param float $h Height.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $border_style Border style of rectangle. Array with keys
   * among the following:
   * <ul>
   *   <li>all: Line style of all borders. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   *   <li>L, T, R, B or combinations: Line style of left, top, right or bottom
   * border. Array like for {@link SetLineStyle SetLineStyle}.</li>
   * </ul>
   * If a key is not present or is null, not draws the border. Default value:
   * default line style (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @access public
   */
  function Rect($x, $y, $w, $h, $style = "", $border_style = array(),
                $fill_color = array()) {
    if (!(false === strpos($style, "F")) && $fill_color) {
      list($r, $g, $b) = $fill_color;
      $this->SetFillColor($r, $g, $b);
    }
    switch ($style) {
      case "F":
        $border_style = array();
        parent::Rect($x, $y, $w, $h, $style);
        break;
      case "DF": case "FD":
        if (!$border_style || isset($border_style["all"])) {
          if (isset($border_style["all"])) {
            $this->SetLineStyle($border_style["all"]);
            $border_style = array();
          }
        } else {
          $style = "F";
        }
        parent::Rect($x, $y, $w, $h, $style);
        break;
      default:
        if (!$border_style || isset($border_style["all"])) {
          if (isset($border_style["all"]) && $border_style["all"]) {
            $this->SetLineStyle($border_style["all"]);
            $border_style = array();
          }
          parent::Rect($x, $y, $w, $h, $style);
        }
        break;
    }
    if ($border_style) {
      $border_style2 = array();
      foreach ($border_style as $line => $value) {
        $lenght = strlen($line);
        for ($i = 0; $i < $lenght; $i++) {
          $border_style2[$line[$i]] = $value;
        }
      }
      $border_style = $border_style2;
      if (isset($border_style["L"]) && $border_style["L"]) {
        $this->Line($x, $y, $x, $y + $h, $border_style["L"]);
      }
      if (isset($border_style["T"]) && $border_style["T"]) {
        $this->Line($x, $y, $x + $w, $y, $border_style["T"]);
      }
      if (isset($border_style["R"]) && $border_style["R"]) {
        $this->Line($x + $w, $y, $x + $w, $y + $h, $border_style["R"]);
      }
      if (isset($border_style["B"]) && $border_style["B"]) {
        $this->Line($x, $y + $h, $x + $w, $y + $h, $border_style["B"]);
      }
    }
  }

  /**
   * Draws a Bezier curve.
   *
   * The Bezier curve is a tangent to the line between the control points at
   * either end of the curve.
   * @param float $x0 Abscissa of start point.
   * @param float $y0 Ordinate of start point.
   * @param float $x1 Abscissa of control point 1.
   * @param float $y1 Ordinate of control point 1.
   * @param float $x2 Abscissa of control point 2.
   * @param float $y2 Ordinate of control point 2.
   * @param float $x3 Abscissa of end point.
   * @param float $y3 Ordinate of end point.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $line_style Line style of curve. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default line style
   * (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @access public
   */
  function Curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style = "",
                 $line_style = array(), $fill_color = array()) {
    if (!(false === strpos($style, "F")) && $fill_color) {
      list($r, $g, $b) = $fill_color;
      $this->SetFillColor($r, $g, $b);
    }
    switch ($style) {
      case "F":
        $op = "f";
        $line_style = array();
        break;
      case "FD": case "DF":
        $op = "B";
        break;
      default:
        $op = "S";
        break;
    }
    if ($line_style) {
      $this->SetLineStyle($line_style);
    }

    $this->_Point($x0, $y0);
    $this->_Curve($x1, $y1, $x2, $y2, $x3, $y3);
    $this->_out($op);
  }

  /**
   * Draws an ellipse.
   *
   * An ellipse is formed from n Bezier curves.
   * @param float $x0 Abscissa of center point.
   * @param float $y0 Ordinate of center point.
   * @param float $rx Horizontal radius.
   * @param float $ry Vertical radius (if ry = 0 then is a circle, see
   * {@link Circle Circle}). Default value: 0.
   * @param float $angle: Angle oriented (anti-clockwise). Default value: 0.
   * @param float $astart: Angle start of draw line. Default value: 0.
   * @param float $afinish: Angle finish of draw line. Default value: 360.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   *   <li>C: Draw close.</li>
   * </ul>
   * @param array $line_style Line style of ellipse. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default line style
   * (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @param integer $nc Number of curves used in ellipse. Default value: 8.
   * @access public
   */
  function Ellipse($x0, $y0, $rx, $ry = 0, $angle = 0, $astart = 0,
                   $afinish = 360, $style = "", $line_style = array(),
                   $fill_color = array(), $nc = 8) {
    if ($rx) {
      if (!(false === strpos($style, "F")) && $fill_color) {
        list($r, $g, $b) = $fill_color;
        $this->SetFillColor($r, $g, $b);
      }
      switch ($style) {
        case "F":
          $op = "f";
          $line_style = array();
          break;
        case "FD": case "DF":
          $op = "B";
          break;
        case "C":
          $op = "s"; // Small "s" signifies closing the path as well
          break;
        default:
          $op = "S";
          break;
      }
      if ($line_style) {
        $this->SetLineStyle($line_style);
      }
      if (!$ry) {
        $ry = $rx;
      }
      $rx *= $this->k;
      $ry *= $this->k;
      if ($nc < 2){
        $nc = 2;
      }

      $astart = deg2rad((float) $astart);
      $afinish = deg2rad((float) $afinish);
      $total_angle = $afinish - $astart;

      $dt = $total_angle / $nc;
      $dtm = $dt/3;

      $x0 *= $this->k;
      $y0 = ($this->h - $y0) * $this->k;
      if ($angle) {
        $a = -deg2rad((float) $angle);
        $this->_out(sprintf("q %.2f %.2f %.2f %.2f %.2f %.2f cm", cos($a),
                            -1 * sin($a), sin($a), cos($a), $x0, $y0));
        $x0 = 0;
        $y0 = 0;
      }

      $t1 = $astart;
      $a0 = $x0 + ($rx * cos($t1));
      $b0 = $y0 + ($ry * sin($t1));
      $c0 = -$rx * sin($t1);
      $d0 = $ry * cos($t1);
      $this->_Point($a0 / $this->k, $this->h - ($b0 / $this->k));
      for ($i = 1; $i <= $nc; $i++) {
        // Draw this bit of the total curve
        $t1 = ($i * $dt) + $astart;
        $a1 = $x0 + ($rx * cos($t1));
        $b1 = $y0 + ($ry * sin($t1));
        $c1 = -$rx * sin($t1);
        $d1 = $ry * cos($t1);
        $this->_Curve(($a0 + ($c0 * $dtm)) / $this->k,
                      $this->h - (($b0 + ($d0 * $dtm)) / $this->k),
                      ($a1 - ($c1 * $dtm)) / $this->k,
                      $this->h - (($b1 - ($d1 * $dtm)) / $this->k),
                      $a1 / $this->k,
                      $this->h - ($b1 / $this->k));
        $a0 = $a1;
        $b0 = $b1;
        $c0 = $c1;
        $d0 = $d1;
      }
      $this->_out($op);
      if ($angle) {
        $this->_out("Q");
      }
    }
  }

  /**
   * Draws a circle.
   *
   * A circle is formed from n Bezier curves.
   * @param float $x0 Abscissa of center point.
   * @param float $y0 Ordinate of center point.
   * @param float $r Radius.
   * @param float $astart: Angle start of draw line. Default value: 0.
   * @param float $afinish: Angle finish of draw line. Default value: 360.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   *   <li>C: Draw close.</li>
   * </ul>
   * @param array $line_style Line style of circle. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default line style
   * (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @param integer $nc Number of curves used in circle. Default value: 8.
   * @access public
   */
  function Circle($x0, $y0, $r, $astart = 0, $afinish = 360, $style = "",
                  $line_style = array(), $fill_color = array(), $nc = 8) {
    $this->Ellipse($x0, $y0, $r, 0, 0, $astart, $afinish, $style, $line_style,
                   $fill_color, $nc);
  }

  /**
   * Draws a polygon.
   *
   * @param array $p Points 0 to ($np - 1). Array with values (x0, y0, x1,
   * y1,..., x(np-1), y(np - 1))
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $line_style Line style of polygon. Array with keys among the
   * following:
   * <ul>
   *   <li>all: Line style of all lines. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   *   <li>0 to ($np - 1): Line style of each line. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   * </ul>
   * If a key is not present or is null, not draws the line. Default value is
   * default line style (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @access public
   */
  function Polygon($p, $style = "", $line_style = array(),
                   $fill_color = array()) {
    $np = count($p) / 2;
    if (!(false === strpos($style, "F")) && $fill_color) {
      list($r, $g, $b) = $fill_color;
      $this->SetFillColor($r, $g, $b);
    }
    switch ($style) {
      case "F":
        $line_style = array();
        $op = "f";
        break;
      case "FD": case "DF":
        $op = "B";
        break;
      default:
        $op = "S";
        break;
    }
    $draw = true;
    if ($line_style) {
      if (isset($line_style["all"])) {
        $this->SetLineStyle($line_style["all"]);
      }
      else { // 0 .. (np - 1), op = {B, S}
        $draw = false;
        if ("B" == $op) {
          $op = "f";
          $this->_Point($p[0], $p[1]);
          for ($i = 2; $i < ($np * 2); $i = $i + 2) {
            $this->_Line($p[$i], $p[$i + 1]);
          }
          $this->_Line($p[0], $p[1]);
          $this->_out($op);
        }
        $p[$np * 2] = $p[0];
        $p[($np * 2) + 1] = $p[1];
        for ($i = 0; $i < $np; $i++) {
          if ($line_style[$i]) {
            $this->Line($p[$i * 2], $p[($i * 2) + 1], $p[($i * 2) + 2],
                        $p[($i * 2) + 3], $line_style[$i]);
          }
        }
      }
    }

    if ($draw) {
      $this->_Point($p[0], $p[1]);
      for ($i = 2; $i < ($np * 2); $i = $i + 2) {
        $this->_Line($p[$i], $p[$i + 1]);
      }
      $this->_Line($p[0], $p[1]);
      $this->_out($op);
    }
  }

  /**
   * Draws a regular polygon.
   *
   * @param float $x0 Abscissa of center point.
   * @param float $y0 Ordinate of center point.
   * @param float $r: Radius of inscribed circle.
   * @param integer $ns Number of sides.
   * @param float $angle Angle oriented (anti-clockwise). Default value: 0.
   * @param boolean $draw_circle Draw inscribed circle or not. Default value:
   * false.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $line_style Line style of polygon sides. Array with keys
   * among the following:
   * <ul>
   *   <li>all: Line style of all sides. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   *   <li>0 to ($ns - 1): Line style of each side. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   * </ul>
   * If a key is not present or is null, not draws the side. Default value is
   * default line style (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @param string $circle_style Style of rendering of inscribed circle (if
   * draws). Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $circle_line_style Line style of inscribed circle (if draws).
   * Array like for {@link SetLineStyle SetLineStyle}. Default value: default
   * line style (empty array).
   * @param array $circle_fill_color Fill color of inscribed circle (if draws).
   * Format: array(red, green, blue). Default value: default color (empty
   * array).
   * @access public
   */
  function RegularPolygon($x0, $y0, $r, $ns, $angle = 0, $draw_circle = false,
                          $style = "", $line_style = array(),
                          $fill_color = array(), $circle_style = "",
                          $circle_line_style = array(),
                          $circle_fill_color = array()) {
    if (3 > $ns) {
      $ns = 3;
    }
    if ($draw_circle) {
      $this->Circle($x0, $y0, $r, 0, 360, $circle_style, $circle_line_style,
                    $circle_fill_color);
    }
    $p = array();
    for ($i = 0; $i < $ns; $i++) {
      $a = $angle + ($i * 360 / $ns);
      $a_rad = deg2rad((float) $a);
      $p[] = $x0 + ($r * sin($a_rad));
      $p[] = $y0 + ($r * cos($a_rad));
    }
    $this->Polygon($p, $style, $line_style, $fill_color);
  }

  /**
   * Draws a star polygon
   *
   * @param float $x0 Abscissa of center point.
   * @param float $y0 Ordinate of center point.
   * @param float $r Radius of inscribed circle.
   * @param integer $nv Number of vertices.
   * @param integer $ng Number of gap (if ($ng % $nv = 1) then is a regular
   * polygon).
   * @param float $angle: Angle oriented (anti-clockwise). Default value: 0.
   * @param boolean $draw_circle: Draw inscribed circle or not. Default value
   * is false.
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $line_style Line style of polygon sides. Array with keys
   * among the following:
   * <ul>
   *   <li>all: Line style of all sides. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   *   <li>0 to (n - 1): Line style of each side. Array like for
   * {@link SetLineStyle SetLineStyle}.</li>
   * </ul>
   * If a key is not present or is null, not draws the side. Default value is
   * default line style (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @param string $circle_style Style of rendering of inscribed circle (if
   * draws). Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $circle_line_style Line style of inscribed circle (if draws).
   * Array like for {@link SetLineStyle SetLineStyle}. Default value: default
   * line style (empty array).
   * @param array $circle_fill_color Fill color of inscribed circle (if draws).
   * Format: array(red, green, blue). Default value: default color (empty
   * array).
   * @access public
   */
  function StarPolygon($x0, $y0, $r, $nv, $ng, $angle = 0,
                       $draw_circle = false, $style = "",
                       $line_style = array(), $fill_color = array(),
                       $circle_style = "", $circle_line_style = array(),
                       $circle_fill_color = array()) {
    if (2 > $nv) {
      $nv = 2;
    }
    if ($draw_circle) {
      $this->Circle($x0, $y0, $r, 0, 360, $circle_style, $circle_line_style,
                    $circle_fill_color);
    }
    $p2 = array();
    $visited = array();
    for ($i = 0; $i < $nv; $i++) {
      $a = $angle + ($i * 360 / $nv);
      $a_rad = deg2rad((float) $a);
      $p2[] = $x0 + ($r * sin($a_rad));
      $p2[] = $y0 + ($r * cos($a_rad));
      $visited[] = false;
    }
    $p = array();
    $i = 0;
    do {
      $p[] = $p2[$i * 2];
      $p[] = $p2[($i * 2) + 1];
      $visited[$i] = true;
      $i += $ng;
      $i %= $nv;
    } while (!$visited[$i]);
    $this->Polygon($p, $style, $line_style, $fill_color);
  }

  /**
   * Draws a rounded rectangle.
   *
   * @param float $x Abscissa of upper-left corner.
   * @param float $y Ordinate of upper-left corner.
   * @param float $w Width.
   * @param float $h Height.
   * @param float $r Radius of the rounded corners.
   * @param string $round_corner Draws rounded corner or not. String with a 0
   * (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions
   * are, in order and begin to 0: top left, top right, bottom right and bottom
   * left. Default value: all rounded corner ("1111").
   * @param string $style Style of rendering. Possible values are:
   * <ul>
   *   <li>D or empty string: Draw (default).</li>
   *   <li>F: Fill.</li>
   *   <li>DF or FD: Draw and fill.</li>
   * </ul>
   * @param array $border_style Border style of rectangle. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default line style
   * (empty array).
   * @param array $fill_color Fill color. Format: array(red, green, blue).
   * Default value: default color (empty array).
   * @access public
   */
  function RoundedRect($x, $y, $w, $h, $r, $round_corner = "1111", $style = "",
                       $border_style = array(), $fill_color = array()) {
    if ("0000" == $round_corner) { // Not rounded
      $this->Rect($x, $y, $w, $h, $style, $border_style, $fill_color);
    } else { // Rounded
      if (!(false === strpos($style, "F")) && $fill_color) {
        list($red, $g, $b) = $fill_color;
        $this->SetFillColor($red, $g, $b);
      }
      switch ($style) {
        case "F":
          $border_style = array();
          $op = "f";
          break;
        case "FD": case "DF":
          $op = "B";
          break;
        default:
          $op = "S";
          break;
      }
      if ($border_style) {
        $this->SetLineStyle($border_style);
      }

      $MyArc = 4 / 3 * (sqrt(2) - 1);

      $this->_Point($x + $r, $y);
      $xc = $x + $w - $r;
      $yc = $y + $r;
      $this->_Line($xc, $y);
      if ($round_corner[0]) {
        $this->_Curve($xc + ($r * $MyArc), $yc - $r, $xc + $r,
                      $yc - ($r * $MyArc), $xc + $r, $yc);
      } else {
        $this->_Line($x + $w, $y);
      }

      $xc = $x + $w - $r;
      $yc = $y + $h - $r;
      $this->_Line($x + $w, $yc);

      if ($round_corner[1]) {
        $this->_Curve($xc + $r, $yc + ($r * $MyArc), $xc + ($r * $MyArc),
                      $yc + $r, $xc, $yc + $r);
      } else {
        $this->_Line($x + $w, $y + $h);
      }

      $xc = $x + $r;
      $yc = $y + $h - $r;
      $this->_Line($xc, $y + $h);
      if ($round_corner[2]) {
        $this->_Curve($xc - ($r * $MyArc), $yc + $r, $xc - $r,
                      $yc + ($r * $MyArc), $xc - $r, $yc);
      } else {
        $this->_Line($x, $y + $h);
      }

      $xc = $x + $r;
      $yc = $y + $r;
      $this->_Line($x, $yc);
      if ($round_corner[3]) {
        $this->_Curve($xc - $r, $yc - ($r * $MyArc), $xc - ($r * $MyArc),
                      $yc - $r, $xc, $yc - $r);
      } else {
        $this->_Line($x, $y);
        $this->_Line($x + $r, $y);
      }
      $this->_out($op);
    }
  }

  /********************/
  /* Privated methods */
  /********************/

  /*
   * Set a draw point.
   *
   * @param float $x Abscissa of point.
   * @param float $y Ordinate of point.
   * @access private
   */
  function _Point($x, $y) {
    $this->_out(sprintf("%.2f %.2f m", $x * $this->k,
                        ($this->h - $y) * $this->k));
  }

  /*
   * Draws a line from last draw point.
   *
   * @param float $x Abscissa of end point.
   * @param float $y Ordinate of end point.
   * @access private
   */
  function _Line($x, $y) {
    $this->_out(sprintf("%.2f %.2f l", $x * $this->k,
                        ($this->h - $y) * $this->k));
  }

  /*
   * Draws a Bezier curve from last draw point.
   *
   * The Bezier curve is a tangent to the line between the control points at
   * either end of the curve.
   * @param float $x1 Abscissa of control point 1.
   * @param float $y1 Ordinate of control point 1.
   * @param float $x2 Abscissa of control point 2.
   * @param float $y2 Ordinate of control point 2.
   * @param float $x3 Abscissa of end point.
   * @param float $y3 Ordinate of end point.
   * @access private
   */
  function _Curve($x1, $y1, $x2, $y2, $x3, $y3) {
    $this->_out(sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c", $x1 * $this->k,
                        ($this->h - $y1) * $this->k, $x2 * $this->k,
                        ($this->h - $y2) * $this->k, $x3 * $this->k,
                        ($this->h - $y3) * $this->k));
  }

}

/*
Author: Pascal Morin
License: Free for non-commercial use
Description
This extension lets you display several paragraphs inside a frame. The use of tags allows to change the font, the style (bold, italic, underline), the size, and the color of characters.
*/
class WriteTag_PDF extends PDF_Draw
{
  // ################################# Initialization

  var $wLine; // Maximum width of the line
  var $hLine; // Height of the line
  var $Text; // Text to display
  var $border;
  var $align; // Justification of the text
  var $fill;
  var $Padding;
  var $lPadding;
  var $tPadding;
  var $bPadding;
  var $rPadding;
  var $TagStyle; // Style for each tag
  var $Indent;
  var $Space; // Minimum space between words
  var $PileStyle;
  var $Line2Print; // Line to display
  var $NextLineBegin; // Buffer between lines
  var $TagName;
  var $Delta; // Maximum width minus width
  var $StringLength;
  var $LineLength;
  var $wTextLine; // Width minus paddings
  var $nbSpace; // Number of spaces in the line
  var $Xini; // Initial position
  var $href; // Current URL
  var $TagHref; // URL for a cell

  // ################################# Public Functions

  function WriteTag($w,$h,$txt,$border=0,$align="J",$fill=0,$padding=0)
  {
    $this->wLine=$w;
    $this->hLine=$h;
    $this->Text=trim($txt);
    $this->Text=ereg_replace("\n|\r|\t","",$this->Text);
    $this->border=$border;
    $this->align=$align;
    $this->fill=$fill;
    $this->Padding=$padding;

    $this->Xini=$this->GetX();
    $this->href="";
    $this->PileStyle=array();
    $this->TagHref=array();
    $this->LastLine=false;

    $this->SetSpace();
    $this->Padding();
    $this->LineLength();
    $this->BorderTop();

    while($this->Text!="")
    {
      $this->MakeLine();
      $this->PrintLine();
    }

    $this->BorderBottom();
  }


  function SetStyle($tag,$family,$style,$size,$color,$indent=-1)
  {
     $tag=trim($tag);
     $this->TagStyle[$tag]['family']=trim($family);
     $this->TagStyle[$tag]['style']=trim($style);
     $this->TagStyle[$tag]['size']=trim($size);
     $this->TagStyle[$tag]['color']=trim($color);
     $this->TagStyle[$tag]['indent']=$indent;
  }


  // ############################ Private Functions

  function SetSpace() // Minimal space between words
  {
    $tag=$this->Parser($this->Text);
    $this->FindStyle($tag[2],0);
    $this->DoStyle(0);
    $this->Space=$this->GetStringWidth(" ");
  }


  function Padding()
  {
    if(ereg("^.+,",$this->Padding)) {
      $tab=explode(",",$this->Padding);
      $this->lPadding=$tab[0];
      $this->tPadding=$tab[1];
      if(isset($tab[2]))
        $this->bPadding=$tab[2];
      else
        $this->bPadding=$this->tPadding;
      if(isset($tab[3]))
        $this->rPadding=$tab[3];
      else
        $this->rPadding=$this->lPadding;
    }
    else
    {
      $this->lPadding=$this->Padding;
      $this->tPadding=$this->Padding;
      $this->bPadding=$this->Padding;
      $this->rPadding=$this->Padding;
    }
    if($this->tPadding<$this->LineWidth)
      $this->tPadding=$this->LineWidth;
  }


  function LineLength()
  {
    if($this->wLine==0)
      $this->wLine=$this->fw - $this->Xini - $this->rMargin;

    $this->wTextLine = $this->wLine - $this->lPadding - $this->rPadding;
  }


  function BorderTop()
  {
    $border=0;
    if($this->border==1)
      $border="TLR";
    $this->Cell($this->wLine,$this->tPadding,"",$border,0,'C',$this->fill);
    $y=$this->GetY()+$this->tPadding;
    $this->SetXY($this->Xini,$y);
  }


  function BorderBottom()
  {
    $border=0;
    if($this->border==1)
      $border="BLR";
    $this->Cell($this->wLine,$this->bPadding,"",$border,0,'C',$this->fill);
  }


  function DoStyle($tag) // Applies a style
  {
    $tag=trim($tag);
    $this->SetFont($this->TagStyle[$tag]['family'],
      $this->TagStyle[$tag]['style'],
      $this->TagStyle[$tag]['size']);

    $tab=explode(",",$this->TagStyle[$tag]['color']);
    if(count($tab)==1)
      $this->SetTextColor($tab[0]);
    else
      $this->SetTextColor($tab[0],$tab[1],$tab[2]);
  }


  function FindStyle($tag,$ind) // Inheritance from parent elements
  {
    $tag=trim($tag);

    // Family
    if($this->TagStyle[$tag]['family']!="")
      $family=$this->TagStyle[$tag]['family'];
    else
    {
      reset($this->PileStyle);
      while(list($k,$val)=each($this->PileStyle))
      {
        $val=trim($val);
        if($this->TagStyle[$val]['family']!="") {
          $family=$this->TagStyle[$val]['family'];
          break;
        }
      }
    }

    // Style
    $style1=strtoupper($this->TagStyle[$tag]['style']);
    if($style1=="N")
      $style="";
    else
    {
      reset($this->PileStyle);
      while(list($k,$val)=each($this->PileStyle))
      {
        $val=trim($val);
        $style1=strtoupper($this->TagStyle[$val]['style']);
        if($style1=="N")
          break;
        else
        {
          if(ereg("B",$style1))
            $style['b']="B";
          if(ereg("I",$style1))
            $style['i']="I";
          if(ereg("U",$style1))
            $style['u']="U";
        }
      }
      $style=$style['b'].$style['i'].$style['u'];
    }

    // Size
    if($this->TagStyle[$tag]['size']!=0)
      $size=$this->TagStyle[$tag]['size'];
    else
    {
      reset($this->PileStyle);
      while(list($k,$val)=each($this->PileStyle))
      {
        $val=trim($val);
        if($this->TagStyle[$val]['size']!=0) {
          $size=$this->TagStyle[$val]['size'];
          break;
        }
      }
    }

    // Color
    if($this->TagStyle[$tag]['color']!="")
      $color=$this->TagStyle[$tag]['color'];
    else
    {
      reset($this->PileStyle);
      while(list($k,$val)=each($this->PileStyle))
      {
        $val=trim($val);
        if($this->TagStyle[$val]['color']!="") {
          $color=$this->TagStyle[$val]['color'];
          break;
        }
      }
    }

    // Result
    $this->TagStyle[$ind]['family']=$family;
    $this->TagStyle[$ind]['style']=$style;
    $this->TagStyle[$ind]['size']=$size;
    $this->TagStyle[$ind]['color']=$color;
    $this->TagStyle[$ind]['indent']=$this->TagStyle[$tag]['indent'];
  }


  function Parser($text)
  {
    $tab=array();
    // Closing tag
    if(ereg("^(</([^>]+)>).*",$text,$regs)) {
      $tab[1]="c";
      $tab[2]=trim($regs[2]);
    }
    // Opening tag
    else if(ereg("^(<([^>]+)>).*",$text,$regs)) {
      $regs[2]=ereg_replace("^a","a ",$regs[2]);
      $tab[1]="o";
      $tab[2]=trim($regs[2]);

      // Presence of attributes
      if(ereg("(.+) (.+)='(.+)' *",$regs[2])) {
        $tab1=split(" +",$regs[2]);
        $tab[2]=trim($tab1[0]);
        while(list($i,$couple)=each($tab1))
        {
          if($i>0) {
            $tab2=explode("=",$couple);
            $tab2[0]=trim($tab2[0]);
            $tab2[1]=trim($tab2[1]);
            $end=strlen($tab2[1])-2;
            $tab[$tab2[0]]=substr($tab2[1],1,$end);
          }
        }
      }
    }
     // Space
     else if(ereg("^( ).*",$text,$regs)) {
      $tab[1]="s";
      $tab[2]=$regs[1];
    }
    // Text
    else if(ereg("^([^< ]+).*",$text,$regs)) {
      $tab[1]="t";
      $tab[2]=trim($regs[1]);
    }
    // Pruning
    $begin=strlen($regs[1]);
     $end=strlen($text);
     $text=substr($text, $begin, $end);
    $tab[0]=$text;

    return $tab;
  }


  function MakeLine() // Makes a line
  {
    $this->Text.=" ";
    $this->LineLength=array();
    $this->TagHref=array();
    $Length=0;
    $this->nbSpace=0;

    $i=$this->BeginLine();
    $this->TagName=array();

    if($i==0) {
      $Length=$this->StringLength[0];
      $this->TagName[0]=1;
      $this->TagHref[0]=$this->href;
    }

    while($Length<$this->wTextLine)
    {
      $tab=$this->Parser($this->Text);
      $this->Text=$tab[0];
      if($this->Text=="") {
        $this->LastLine=true;
        break;
      }

      if($tab[1]=="o") {
        array_unshift($this->PileStyle,$tab[2]);
        $this->FindStyle($this->PileStyle[0],$i+1);

        $this->DoStyle($i+1);
        $this->TagName[$i+1]=1;
        if($this->TagStyle[$tab[2]]['indent']!=-1) {
          $Length+=$this->TagStyle[$tab[2]]['indent'];
          $this->Indent=$this->TagStyle[$tab[2]]['indent'];
        }
        if($tab[2]=="a")
          $this->href=$tab['href'];
      }

      if($tab[1]=="c") {
        array_shift($this->PileStyle);
        $this->FindStyle($this->PileStyle[0],$i+1);
        $this->DoStyle($i+1);
        $this->TagName[$i+1]=1;
        if($this->TagStyle[$tab[2]]['indent']!=-1) {
          $this->LastLine=true;
          $this->Text=trim($this->Text);
          break;
        }
        if($tab[2]=="a")
          $this->href="";
      }

      if($tab[1]=="s") {
        $i++;
        $Length+=$this->Space;
        $this->Line2Print[$i]="";
        if($this->href!="")
          $this->TagHref[$i]=$this->href;
      }

      if($tab[1]=="t") {
        $i++;
        $this->StringLength[$i]=$this->GetStringWidth($tab[2]);
        $Length+=$this->StringLength[$i];
        $this->LineLength[$i]=$Length;
        $this->Line2Print[$i]=$tab[2];
        if($this->href!="")
          $this->TagHref[$i]=$this->href;
       }

    }

    trim($this->Text);
    if($Length>$this->wTextLine || $this->LastLine==true)
      $this->EndLine();
  }


  function BeginLine()
  {
    $this->Line2Print=array();
    $this->StringLength=array();

    $this->FindStyle($this->PileStyle[0],0);
    $this->DoStyle(0);

    if(count($this->NextLineBegin)>0) {
      $this->Line2Print[0]=$this->NextLineBegin['text'];
      $this->StringLength[0]=$this->NextLineBegin['length'];
      $this->NextLineBegin=array();
      $i=0;
    }
    else {
      ereg("^(( *(<([^>]+)>)* *)*)(.*)",$this->Text,$regs);
      $regs[1]=ereg_replace(" ", "", $regs[1]);
      $this->Text=$regs[1].$regs[5];
      $i=-1;
    }

    return $i;
  }


  function EndLine()
  {
    if(end($this->Line2Print)!="" && $this->LastLine==false) {
      $this->NextLineBegin['text']=array_pop($this->Line2Print);
      $this->NextLineBegin['length']=end($this->StringLength);
      array_pop($this->LineLength);
    }

    while(end($this->Line2Print)=="")
      array_pop($this->Line2Print);

    $this->Delta=$this->wTextLine-end($this->LineLength);

    $this->nbSpace=0;
    for($i=0; $i<count($this->Line2Print); $i++) {
      if($this->Line2Print[$i]==="")
        $this->nbSpace++;
    }
  }


  function PrintLine()
  {
    $border=0;
    if($this->border==1)
      $border="LR";
    $this->Cell($this->wLine,$this->hLine,"",$border,0,'C',$this->fill);
    $y=$this->GetY();
    $this->SetXY($this->Xini+$this->lPadding,$y);

    if($this->Indent!=-1) {
      if($this->Indent!=0)
        $this->Cell($this->Indent,$this->hLine,"",0,0,'C',0);
      $this->Indent=-1;
    }

    $space=$this->LineAlign();
    $this->DoStyle(0);
    for($i=0; $i<count($this->Line2Print); $i++)
    {
      if($this->TagName[$i]==1)
        $this->DoStyle($i);
      if($this->Line2Print[$i]=="")
        $this->Cell($space,$this->hLine,"         ",0,0,'C',0,$this->TagHref[$i]);
      else
        $this->Cell($this->StringLength[$i],$this->hLine,$this->Line2Print[$i],0,0,'C',0,$this->TagHref[$i]);
    }

    $this->LineBreak();
    if($this->LastLine && $this->Text!="")
      $this->EndParagraph();
    $this->LastLine=false;
  }


  function LineAlign()
  {
    $space=$this->Space;
    if($this->align=="J") {
      if($this->nbSpace!=0)
        $space=$this->Space + ($this->Delta/$this->nbSpace);
      if($this->LastLine)
        $space=$this->Space;
    }

    if($this->align=="R")
      $this->Cell($this->Delta,$this->hLine,"",0,0,'C',0);

    if($this->align=="C")
      $this->Cell($this->Delta/2,$this->hLine,"",0,0,'C',0);

    return $space;
  }


  function LineBreak()
  {
    $x=$this->Xini;
    $y=$this->GetY()+$this->hLine;
    $this->SetXY($x,$y);
  }


  function EndParagraph() // Interline between paragraphs
  {
    $border=0;
    if($this->border==1)
      $border="LR";
    $this->Cell($this->wLine,$this->hLine/2,"",$border,0,'C',$this->fill);
    $x=$this->Xini;
    $y=$this->GetY()+$this->hLine/2;
    $this->SetXY($x,$y);
  }


}

/**
 * My extension for WriteTag_PDF.
 *
 * Add align (horizontal and vertical) of text, leading (for text and
 * paragraph) and a tag for align paragraph.
 * @package TCPDF
 * @author David Hernandez Sanz
 * @license Freeware
 */
class PDF_WriteTag2 extends WriteTag_PDF {
  /***********************/
  /* Privated properties */
  /***********************/

  /**
   * Vertical align of text.
   *
   * Possible values are:
   * <ul>
   *   <li>T: Top.</li>
   *   <li>M: Middle.</li>
   *   <li>B: Bottom.</li>
   * </ul>
   * @var string
   * @access private
   */
  var $valign;

  /**
   * Leading for text.
   *
   * @var float
   * @access private
   */
  var $leading;

  /**
   * Leading for paragraph.
   *
   * @var float
   * @access private
   */
  var $leadingParagraph;

  /**
   * Actual line is first or not.
   *
   * @var boolean
   * @access private
   */
  var $isFirstLine;

  /**
   * Height of strings.
   *
   * @var float
   * @access private
   */
  var $stringHeight;

  /**
   * Current align for a tag.
   *
   * Possible values are:
   * <ul>
   *   <li>L or empty string: Left align.</li>
   *   <li>C: Center.</li>
   *   <li>R: Right align.</li>
   *   <li>J: Justification.</li>
   * </ul>
   * @var string
   * @access private
   */
  var $currentTagAlign;

  /**
   * Align for a tag.
   *
   * Possible values are:
   * <ul>
   *   <li>L or empty string: Left align.</li>
   *   <li>C: Center.</li>
   *   <li>R: Right align.</li>
   *   <li>J: Justification.</li>
   * </ul>
   * @var string
   * @access private
   */
  var $tagAlign;

  /**
   * FontBBox font property.
   *
   * The FontBBox font property is in AFM files. It is used in order to
   * computes the height of a text. Format:
   * array(font1 => font_BBox1, font2 => font_BBox2,...).
   * @var array
   * @access private
   */
  var $font_FontBBox = array("courier" => array(-23, -250, 715, 805),
                             "courierB" => array(-113, -250, 749, 801),
                             "courierI" => array(-27, -250, 849, 805),
                             "courierBI" => array(-57, -250, 869, 801),
                             "helvetica" => array(-166, -225, 1000, 931),
                             "helveticaB" => array(-170, -228, 1003, 962),
                             "helveticaI" => array(-170, -225, 1116, 931),
                             "helveticaBI" => array(-174, -228, 1114, 962),
                             "symbol" => array(-180, -293, 1090, 1010),
                             "times" => array(-168, -218, 1000, 898),
                             "timesB" => array(-168, -218, 1000, 935),
                             "timesI" => array(-169, -217, 1010, 883),
                             "timesBI" => array(-200, -218, 996, 921),
                             "zapfdingbats" => array(-1, -143, 981, 820),

                             "dejavusans" => array(-166, -225, 1000, 931),
                             "dejavusansB" => array(-170, -228, 1003, 962),
                             "dejavusansI" => array(-170, -225, 1116, 931),
                             "dejavusansBI" => array(-174, -228, 1114, 962),
                             "dejavusanscondensed" => array(-166, -225, 1000, 931),
                             "dejavusanscondensedB" => array(-170, -228, 1003, 962),
                             "dejavusanscondensedI" => array(-170, -225, 1116, 931),
                             "dejavusanscondensedBI" => array(-174, -228, 1114, 962),
                             "dejavusans-extralight" => array(-166, -225, 1000, 931),
                             "dejavusans-extralightB" => array(-170, -228, 1003, 962),
                             "dejavusans-extralightI" => array(-170, -225, 1116, 931),
                             "dejavusans-extralightBI" => array(-174, -228, 1114, 962),
                             "dejavusansmono" => array(-23, -250, 715, 805),
                             "dejavusansmonoB" => array(-113, -250, 749, 801),
                             "dejavusansmonoI" => array(-27, -250, 849, 805),
                             "dejavusansmonoBI" => array(-57, -250, 869, 801),
                             "dejavuserif" => array(-168, -218, 1000, 898),
                             "dejavuserifB" => array(-168, -218, 1000, 935),
                             "dejavuserifI" => array(-169, -217, 1010, 883),
                             "dejavuserifBI" => array(-200, -218, 996, 921),
                             "dejavuserifcondensed" => array(-168, -218, 1000, 898),
                             "dejavuserifcondensedB" => array(-168, -218, 1000, 935),
                             "dejavuserifcondensedI" => array(-169, -217, 1010, 883),
                             "dejavuserifcondensedBI" => array(-200, -218, 996, 921),
                             "freesans" => array(-166, -225, 1000, 931),
                             "freesansB" => array(-170, -228, 1003, 962),
                             "freesansI" => array(-170, -225, 1116, 931),
                             "freesansBI" => array(-174, -228, 1114, 962),
                             "freeserif" => array(-168, -218, 1000, 898),
                             "freeserifB" => array(-168, -218, 1000, 935),
                             "freeserifI" => array(-169, -217, 1010, 883),
                             "freeserifBI" => array(-200, -218, 996, 921),
                             "freemono" => array(-23, -250, 715, 805),
                             "freemonoB" => array(-113, -250, 749, 801),
                             "freemonoI" => array(-27, -250, 849, 805),
                             "freemonoBI" => array(-57, -250, 869, 801),
                             "vera" => array(-166, -225, 1000, 931),
                             "veraB" => array(-170, -228, 1003, 962),
                             "veraI" => array(-170, -225, 1116, 931),
                             "veraBI" => array(-174, -228, 1114, 962),
                             "verase" => array(-168, -218, 1000, 898),
                             "veraseB" => array(-168, -218, 1000, 935),
                             "veraseI" => array(-169, -217, 1010, 883),
                             "veraseBI" => array(-200, -218, 996, 921),
                             "veramo" => array(-23, -250, 715, 805),
                             "veramoB" => array(-113, -250, 749, 801),
                             "veramoI" => array(-27, -250, 849, 805),
                             "veramoBI" => array(-57, -250, 869, 801),
                             );

  /******************/
  /* Public methods */
  /******************/

  /**
   * This is the class constructor.
   * It allows to set up the page format, the orientation and
   * the measure unit used in all the methods (except for the font sizes).
   *
   * Updated method
   * @since 1.0
   * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li></ul>
   * @param string $unit User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul><br />A point equals 1/72 of inch, that is to say about 0.35 mm (an inch being 2.54 cm). This is a very common unit in typography; font sizes are expressed in that unit.
   * @param mixed $format The format used for pages. It can be either one of the following values (case insensitive) or a custom format in the form of a two-element array containing the width and the height (expressed in the unit given by unit).<ul><li>4A0</li><li>2A0</li><li>A0</li><li>A1</li><li>A2</li><li>A3</li><li>A4 (default)</li><li>A5</li><li>A6</li><li>A7</li><li>A8</li><li>A9</li><li>A10</li><li>B0</li><li>B1</li><li>B2</li><li>B3</li><li>B4</li><li>B5</li><li>B6</li><li>B7</li><li>B8</li><li>B9</li><li>B10</li><li>C0</li><li>C1</li><li>C2</li><li>C3</li><li>C4</li><li>C5</li><li>C6</li><li>C7</li><li>C8</li><li>C9</li><li>C10</li><li>RA0</li><li>RA1</li><li>RA2</li><li>RA3</li><li>RA4</li><li>SRA0</li><li>SRA1</li><li>SRA2</li><li>SRA3</li><li>SRA4</li><li>LETTER</li><li>LEGAL</li><li>EXECUTIVE</li><li>FOLIO</li></ul>
   * @param boolean $unicode TRUE means that the input text is unicode (default = true)
   * @param String $encoding charset encoding; default is UTF-8
   */
  function PDF_WriteTag2($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding="UTF-8") {
    parent::__construct($orientation, $unit, $format, $unicode, $encoding);
    $this->fontlist = ($unicode)
                          ? array("dejavusans", "dejavusanscondensed", "dejavusans-extralight", "dejavusansmono", "dejavuserif", "dejavuserifcondensed", "freesans", "freeserif", "freemono", "vera", "verase", "veramo")
                          : array("arial", "times", "courier", "helvetica", "symbol", "zapfdingbats");
  }

  /**
   * Write a text with tags.
   *
   * Redefine from WriteTag_PDF. Adds border style, fill color, vertical align
   * and leading.
   * @param float $w Width of text box.
   * @param float $height Height of text box (if 0 then height of text box is
   * as height of text).
   * @param string $txt Text.
   * @param mixed $border Border of text box. Possible values are:
   * <ul>
   *   <li>0 (integer): No border (default).</li>
   *   <li>1 (integer): Default border.</li>
   *   <li>Array like for $border_style in {@link Rect Rect}.</li>
   * </ul>
   * @param string $align Text align. Possible values are:
   * <ul>
   *   <li>L or empty string: Left align.</li>
   *   <li>C: Center.</li>
   *   <li>R: Right align.</li>
   *   <li>J: Justification (default).</li>
   * </ul>
   * @param integer $fill Indicates if the cell background must be painted (1)
   * or transparent (0). Default value: 0.
   * @param array $fill_color Fill color (if fill). Format:
   * array(red, green, blue). Default value: default color (empty array).
   * @param string $valign Text vertical align. Possible values are:
   * <ul>
   *   <li>T: Top.</li>
   *   <li>M: Middle (default).</li>
   *   <li>B: Bottom.</li>
   * </ul>
   * @param mixed $padding Padding for text. Possible values are:
   * <ul>
   *   <li>A float: Left, right, top and bottom padding.</li>
   *   <li>A string with of float values:
   *       <ul>
   *         <li>2 values: Format: "left/right,top/bottom".</li>
   *         <li>3 values: Format: "left/right,top,bottom".</li>
   *         <li>4 values: Format: "left,top,bottom,right".</li>
   *       </ul></li>
   * </ul>
   * Default value: 0.
   * @param float $leading Leading for text. Default value: 1.20.
   * @param float $leadingParagraph Leading for paragraph. Default value: 0.60.
   * @access public
   */
  function WriteTag($w, $height, $txt, $border = 0, $align = "J", $fill = 0,
                    $fill_color = array(), $valign = "M", $padding = 0,
                    $leading = 1.20, $leadingParagraph = 0.60) {
    $x = $this->GetX();
    $y = $this->GetY();

    // Width for text
    if (!$w) {
      $w = $this->fw - $x - $this->rMargin;
    }
    $this->wLine = $w;

    // Padding and leading
    $this->Padding = $padding;
    $this->Padding();
    $this->leading = $leading;
    $this->leadingParagraph = $leadingParagraph;

    // Height for text
    $this->Xini = $x;
    $this->LineLength();
    $txt = $this->_BreakLongWord($this->wTextLine, $txt);
    $height_text = $this->HeightText($w, $txt, $padding, $leading,
                                     $leadingParagraph);
    if (!$height) {
      $height = $height_text;
    }

    $this->border = 0;
    $this->align = $align;
    $this->fill = 0;
    $this->valign = $valign;

    // Draw the border and fill
    $style = "";
    $border_style = array();
    if ($fill) {
      $style .= "F";
    }
    if ($border) {
      $style .= "D";
      if (is_array($border)) {
        $border_style = $border;
      }
    }
    if ($style) {
      $this->Rect($x, $y, $w, $height, $style, $border_style, $fill_color);
    }

    // Vertical align for text
    switch ($this->valign) {
      case "T": default:
        $dy = 0;
        break;
      case "M":
        $dy = ($height - $height_text) / 2;
        break;
      case "B":
        $dy = $height - $height_text;
        break;
    }

    // Write text
    $this->SetXY($x, $y + $dy);

    $this->Text = trim($txt);
    $this->Text = ereg_replace("\n|\r|\t", "", $this->Text);

    $this->href = "";
    $this->currentTagAlign = "";
    $this->PileStyle = array();
    $this->TagHref = array();
    $this->tagAlign = null;
    $this->LastLine = false;

    $this->SetSpace();
    $this->BorderTop();

    $this->isFirstLine = true;
    while ("" != $this->Text) {
      $this->MakeLine();
      $this->hLine = max($this->stringHeight) * $this->leading;
      $this->PrintLine();
      $this->LastLine = false;
      $this->isFirstLine = false;
    }
    $this->BorderBottom();

    $this->SetY($y + $height);
  }

  /**
   * Computes the height of a text.
   *
   * @param float $w Width of text box.
   * @param string $txt Text.
   * @param mixed $padding Padding for text. Possible values are:
   * <ul>
   *   <li>A float: Left, right, top and bottom padding.</li>
   *   <li>A string with of float values:
   *       <ul>
   *         <li>2 values: Format: "left/right,top/bottom".</li>
   *         <li>3 values: Format: "left/right,top,bottom".</li>
   *         <li>4 values: Format: "left,top,bottom,right".</li>
   *       </ul></li>
   * </ul>
   * Default value: 0.
   * @param float $leading Leading for text. Default value: 1.20.
   * @param float $leadingParagraph Leading for paragraph. Default value: 0.60.
   * @return Height of text.
   * @access public
   */
  function HeightText($w, $txt, $padding = 0, $leading = 1.20,
                      $leadingParagraph = 0.60) {
    $x = $this->GetX();
    $y = $this->GetY();

    // Width for text
    if (!$w)
      $w = $this->fw - $x - $this->rMargin;
    $this->wLine=$w;

    $this->Padding = $padding;
    $this->Padding();

    // Write text
    $this->Text = trim($txt);
    $this->Text = ereg_replace("\n|\r|\t", "", $this->Text);

    $this->Xini=$x;
    $this->PileStyle=array();
    $this->LastLine=false;

    $this->SetSpace();
    $this->LineLength();

    $height = 0;
    $this->isFirstLine = true;
    $TagStyle = $this->TagStyle;
    while ("" != $this->Text) {
      $this->_SetHeightLine();
      $this->hLine = max($this->stringHeight) * $leading;
      $height += $this->hLine;
      if ($this->LastLine && ("" != $this->Text)) {
        $height += $this->hLine * $leadingParagraph / $leading;
      }
      $this->LastLine = false;
      $this->isFirstLine = false;
    }
    $height += $this->tPadding + $this->bPadding -
               ($this->hLine * ($leading - 1));
    $this->TagStyle = $TagStyle;

    // Round 3 decimals for precision error
    return (float) round(1000 * $height) / 1000;
  }

  /**
   * Performs a return.
   *
   * The current abscissa goes back to the left margin and the ordinate
   * increases/decreases one line (in current or parameter style).
   * @param string $tag Tag style. Default value: current style (empty
   * string).
   * @param float $ln Number of lines. Default value: 1.
   * @param boolean $increase Indicates if lines must be increases or
   * decreases. Default value: true.
   * @access public
   */
  function HardReturn($tag = "", $ln = 1.0, $increase = true) {
    if ($tag) {
      $style = ("N" == $this->TagStyle[$tag]["style"])
                   ? "" :
                   $this->TagStyle[$tag]["style"];
      $this->SetFont($this->TagStyle[$tag]["family"], $style,
                     $this->TagStyle[$tag]["size"]);
    }
    $inc = $this->_GetFontHeight() * $ln;
    if (!$increase) {
      $inc *= -1;
    }
    $this->SetY($this->GetY() + $inc);
  }

  /********************/
  /* Privated methods */
  /********************/

  /**
   * Sets the padding for text in block.
   *
   * Redefine from WriteTag_PDF.
   * @access private
   */
  function Padding() {
    if (ereg("^.+,", $this->Padding)) {
      $tab = explode(",", $this->Padding);
      $this->lPadding = $tab[0];
      $this->tPadding = $tab[1];
      $this->bPadding = (isset($tab[2])) ? $tab[2] : $this->tPadding;
      $this->rPadding = (isset($tab[3])) ? $tab[3] : $this->lPadding;
    } else {
      $this->lPadding = $this->Padding;
      $this->tPadding = $this->Padding;
      $this->bPadding = $this->Padding;
      $this->rPadding = $this->Padding;
    }
  }

  /**
   * Applies a style.
   *
   * Redefine from WriteTag_PDF. Not applies color in all call.
   * @param string $tag Tag style.
   * @param boolean $all Applies all style or only set font style. Default
   * value: true.
   * @access private
   */
  function DoStyle($tag, $all = true) {
    $tag = trim($tag);

    $this->SetFont($this->TagStyle[$tag]['family'],
                   $this->TagStyle[$tag]['style'],
                   $this->TagStyle[$tag]['size']);
    if ($all) {
      $tab = explode(",", $this->TagStyle[$tag]['color']);
      if (1 == count($tab)) {
        $this->SetTextColor($tab[0]);
      } else {
        $this->SetTextColor($tab[0], $tab[1], $tab[2]);
      }
    }
  }

  /**
   * Parser text.
   *
   * Redefine from WriteTag_PDF. Adds the tag div for align.
   * @param string $text: Text.
   * @return array Text parser.
   * @access private
   */
  function Parser($text) {
    $tab = array();
    if (ereg("^(</([^>]+)>).*",$text,$regs)) {
      // Closing tag
      $tab[1] = "c";
      $tab[2] = trim($regs[2]);
      $tab[3] = trim($regs[1]);
    } elseif (ereg("^(<([^>]+)>).*", $text, $regs)) {
      // Opening tag
      $regs[2] = ereg_replace("^a", "a ", $regs[2]);
      $regs[2] = ereg_replace("^div", "div ", $regs[2]);
      $tab[1] = "o";
      $tab[2] = trim($regs[2]);
      $tab[3] = trim($regs[1]);

      // Presence of attributes
      if (ereg("(.+) (.+)='(.+)' *", $regs[2])) {
        $tab1 = split(" +", $regs[2]);
        $tab[2] = trim($tab1[0]);
        while (list($i, $couple) = each($tab1)) {
          if(0 < $i) {
            $tab2 = explode("=", $couple);
            $tab2[0] = trim($tab2[0]);
            $tab2[1] = trim($tab2[1]);
            $end = strlen($tab2[1]) - 2;
            $tab[$tab2[0]] = substr($tab2[1], 1, $end);
          }
        }
      }
    } elseif (ereg("^( ).*", $text, $regs)) {
      // Space
      $tab[1] = "s";
      $tab[2] = $regs[1];
    } elseif (ereg("^([^< ]+).*", $text, $regs)) {
      // Text
      $tab[1] = "t";
      $tab[2] = trim($regs[1]);
    }

    // Pruning
    $begin = strlen($regs[1]);
    $end = strlen($text);
    $text = substr($text, $begin, $end);
    $tab[0] = $text;

    return $tab;
  }

  /**
   * Makes a line.
   *
   * Redefine from WriteTag_PDF.
   * @access private
   */
  function MakeLine() {
    $this->Text .= " ";
    $this->LineLength = array();
    $this->TagHref = array();
    $this->tagAlign = null;
    $Length = 0;
    $this->nbSpace = 0;

    $i = $this->BeginLine(true);
    $this->TagName = array();
    if (!$i) {
      $Length = $this->StringLength[0];
      $this->TagName[0] = 1;
      $this->TagHref[0] = $this->href;
      $this->tagAlign = $this->currentTagAlign;
    }
    while ($Length < $this->wTextLine) {
      $tab = $this->Parser($this->Text);
      $this->Text = $tab[0];
      if ("" == $this->Text) {
        $this->LastLine = true;
        break; // Exit the while
      }
      switch ($tab[1]) {
        case "o": // Opening tag
          array_unshift($this->PileStyle, $tab[2]);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, true);
          $this->TagName[$i + 1] = 1;
          if (-1 != $this->TagStyle[$tab[2]]["indent"]) {
            $Length += $this->TagStyle[$tab[2]]["indent"];
            $this->Indent = $this->TagStyle[$tab[2]]["indent"];
          }
          switch ($tab[2]) {
            case "a":
              $this->href = $tab["href"];
              break;
            case "div":
              $this->currentTagAlign = $tab["align"];
              break;
          }
          break;
        case "c": // Closing tag
          array_shift($this->PileStyle);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, true);
          $this->TagName[$i + 1] = 1;
          if (-1 != $this->TagStyle[$tab[2]]["indent"]) {
            $this->LastLine = true;
            $this->Text = trim($this->Text);
            break 2; // Exit the switch and the while
          }
          switch ($tab[2]) {
            case "a":
              $this->href = "";
              break;
            case "div":
              $this->currentTagAlign = null;
              break;
          }
          break;

        case "s": // Space
          $i++;
          $Length += $this->Space;
          $this->Line2Print[$i] = "";
          $this->stringHeight[$i] = $this->_GetFontHeight();
          if ($this->href) {
            $this->TagHref[$i] = $this->href;
          }
          if ($this->currentTagAlign) {
            $this->tagAlign = $this->currentTagAlign;
          }
          break;
        case "t": // Text
          $i++;
          $this->StringLength[$i] = $this->GetStringWidth($tab[2]);
          $Length += $this->StringLength[$i];
          $this->LineLength[$i] = $Length;
          $this->Line2Print[$i] = $tab[2];
          $this->stringHeight[$i] = $this->_GetFontHeight();
          if ($this->href) {
            $this->TagHref[$i] = $this->href;
          }
          if ($this->currentTagAlign) {
            $this->tagAlign = $this->currentTagAlign;
          }
          break;
      }
    }

    trim($this->Text);
    if (($Length > $this->wTextLine) || $this->LastLine) {
      $this->EndLine();
    }
  }

  /**
   * Begin line.
   *
   * Redefine from WriteTag_PDF.
   * @param boolean $write: Indicates if write in PDF or only set attributes.
   * Default value: true.
   * @return integer Have previous line (-1) or not (0).
   * @access private
   */
  function BeginLine($write = true) {
    $this->Line2Print = array();
    $this->StringLength = array();
    $this->stringHeight = array();

    $this->FindStyle($this->PileStyle[0], 0);
    $this->DoStyle(0, $write);

    if (count($this->NextLineBegin)) {
      $this->Line2Print[0] = $this->NextLineBegin["text"];
      $this->StringLength[0] = $this->NextLineBegin["length"];
      $this->stringHeight[0] = $this->NextLineBegin["height"];
      $this->NextLineBegin = array();
      $i = 0;
    } else {
      ereg("^(( *(<([^>]+)>)* *)*)(.*)", $this->Text, $regs);
      $regs[1] = ereg_replace(" ", "", $regs[1]);
      $this->Text = $regs[1] . $regs[5];
      $i = -1;
    }

    return $i;
  }

  /**
   * End line.
   *
   * Redefine from WriteTag_PDF.
   * @access private
   */
  function EndLine() {
    if(("" != end($this->Line2Print)) && !$this->LastLine) {
      $this->NextLineBegin["text"] = array_pop($this->Line2Print);
      $this->NextLineBegin["length"] = end($this->StringLength);
      $this->NextLineBegin["height"] = end($this->stringHeight);
      array_pop($this->LineLength);
      array_pop($this->stringHeight);
    }

    while ("" == end($this->Line2Print)) {
      array_pop($this->Line2Print);
    }

    $this->Delta= $this->wTextLine - end($this->LineLength);

    $this->nbSpace = 0;
    $dim_i = count($this->Line2Print);
    for ($i = 0; $i < $dim_i; $i++) {
      if ("" === $this->Line2Print[$i]) {
        $this->nbSpace++;
      }
    }
  }

  /**
   * Print a line.
   *
   * Redefine from WriteTag_PDF.
   * @access private
   */
  function PrintLine() {
    $border = 0;
    if (1 == $this->border) {
      $border = "LR";
    }
    $this->Cell($this->wLine, $this->hLine, "", $border, 0, "C", $this->fill);
    $y = $this->GetY();
    $this->SetXY($this->Xini + $this->lPadding, $y);

    if (-1 != $this->Indent) {
      if ($this->Indent) {
        $this->Cell($this->Indent, $this->hLine, "", 0, 0, "C", 0);
      }
      $this->Indent = -1;
    }

    $space = $this->LineAlign();
    $this->DoStyle(0, true);
    $dim_i = count($this->Line2Print);
    for ($i = 0; $i < $dim_i; $i++) {
      if (1 == $this->TagName[$i]) {
        $this->DoStyle($i, true);
      }
      $this->SetXY($this->GetX(),
                   $y + ($this->hLine / $this->leading) -
                   $this->stringHeight[$i]);
      if("" == $this->Line2Print[$i]) {
        $this->Cell($space, $this->stringHeight[$i], "         ", 0, 0, "C", 0,
                    $this->TagHref[$i]);
      } else {
        $x = $this->GetX();
        if (1 == $dim_i) {
          switch ($this->align) {
            case "C":
              if (($this->Xini + ($this->wLine / 2)) == $x) {
                $this->SetX($x - ($this->StringLength[$i] / 2));
              }
              break;
            case "R":
              if (($this->Xini + $this->wLine - $this->rPadding) == $x) {
                $this->SetX($x - $this->StringLength[$i]);
              }
              break;
          }
        }
        $this->Cell($this->StringLength[$i], $this->stringHeight[$i],
                    $this->Line2Print[$i], 0, 0, "C", 0, $this->TagHref[$i]);
      }
      $this->SetXY($this->GetX(), $y);
    }

    $this->LineBreak();
    if($this->LastLine && ("" != $this->Text)) {
      $this->EndParagraph();
    }
    $this->LastLine = false;
  }

  /**
   * Align the line.
   *
   * Redefine from WriteTag_PDF. Adds the tag align.
   * @return float Space for align the line.
   * @access private
   */
  function LineAlign() {
    $align = null;
    if ($this->tagAlign) {
      $align = $this->align;
      $this->align = $this->tagAlign;
    }
    $space = parent::LineAlign();
    if ($align) {
      $this->align = $align;
    }
    return $space;
  }

  /**
   * Interline between paragraphs.
   *
   * Redefine from WriteTag_PDF. Adds leading.
   * @access private
   */
  function EndParagraph() {
    $border = 0;
    if (1 == $this->border) {
      $border = "LR";
    }
    $height = $this->hLine * $this->leadingParagraph / $this->leading;
    $this->Cell($this->wLine, $height, "", $border, 0, "C", $this->fill);
    $x = $this->Xini;
    $y = $this->GetY() + $height;
    $this->SetXY($x, $y);
  }

  /**
   * Get the height of the current font (style and size) in user units.
   *
   * @return float Height of the current font.
   * @access private
   */
  function _GetFontHeight() {
    $font = strtolower("$this->FontFamily$this->FontStyle");
    if (!isset($this->font_FontBBox[$font])) {
      $font = strtolower("$this->FontFamily");
      if (!isset($this->font_FontBBox[$font])) {
        $font = ($this->unicode) ? "helvetica" : "freesans";
      }
    }
    $h = $this->font_FontBBox[$font][3] - $this->font_FontBBox[$font][1];
    return $this->FontSize * $h / 1000;
  }

  /**
   * Break long words of text if are longer than width text box.
   *
   * @param float $w Width of text box.
   * @param string $txt Text.
   * @return string Text break.
   * @access private
   */
  function _BreakLongWord($w, $txt) {
    $TagStyle = $this->TagStyle;

    $this->PileStyle = array();
    $tab[0] = $txt;
    $txt2 = "";
    $i = 0;
    while ($tab[0]) {
      $tab = $this->Parser($tab[0]);
      switch ($tab[1]) {
        case "o": // Opening tag
          $txt2 .= $tab[3];
          array_unshift($this->PileStyle, $tab[2]);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, false);
          break;
        case "c": // Closing tag
          $txt2 .= $tab[3];
          array_shift($this->PileStyle);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, false);
          break;
        case "s": // Space
          $i++;
          $txt2 .= $tab[2];
          break;
        case "t": // Text
          $i++;
          $txt3 = $tab[2];
          while ($this->GetStringWidth($txt3) >= $w) {
            for ($j = 1, $txt4 = ""; $this->GetStringWidth($txt4) < $w; $j++) {
              $txt4 = substr($txt3, 0, $j);
            }
            $j -=2;
            if (1 > $j) {
              $j = 1;
            }
            $txt2 .= substr($txt3, 0, $j) . " ";
            $txt3 = substr($txt3, $j);
          }
          $txt2 .= $txt3;
          break;
      }
    }
    $this->TagStyle = $TagStyle;

    // Set font because this function unset font
    $this->SetFont(($this->unicode) ? "helvetica" : "freesans", null, 10);

    return $txt2;
  }

  /**
   * Sets the height of line.
   *
   * @access private
   */
  function _SetHeightLine() {
    $this->Text .= " ";
    $this->LineLength = array();
    $Length = 0;

    $i = $this->BeginLine(false);
    $this->TagName = array();
    if (!$i) {
      $Length = $this->StringLength[0];
      $this->TagName[0] = 1;
    }

    while ($Length < $this->wTextLine) {
      $tab = $this->Parser($this->Text);
      $this->Text = $tab[0];
      if ("" == $this->Text) {
        $this->LastLine = true;
        break; // Exit the while
      }
      switch ($tab[1]) {
        case "o": // Opening tag
          array_unshift($this->PileStyle, $tab[2]);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, false);
          $this->TagName[$i + 1] = 1;
          if (-1 != $this->TagStyle[$tab[2]]["indent"]) {
            $Length += $this->TagStyle[$tab[2]]["indent"];
            $this->Indent = $this->TagStyle[$tab[2]]["indent"];
          }
          break;
        case "c": // Closing tag
          array_shift($this->PileStyle);
          $this->FindStyle($this->PileStyle[0], $i + 1);
          $this->DoStyle($i + 1, false);
          $this->TagName[$i + 1] = 1;
          if (-1 != $this->TagStyle[$tab[2]]["indent"]) {
            $this->LastLine = true;
            $this->Text = trim($this->Text);
            break 2; // Exit the switch and the while
          }
          break;
        case "s": // Space
          $i++;
          $Length += $this->Space;
          $this->Line2Print[$i] = "";
          $this->stringHeight[$i] = $this->_GetFontHeight();
          break;
        case "t": // Text
          $i++;
          $this->StringLength[$i] = $this->GetStringWidth($tab[2]);
          $Length += $this->StringLength[$i];
          $this->LineLength[$i] = $Length;
          $this->Line2Print[$i] = $tab[2];
          $this->stringHeight[$i] = $this->_GetFontHeight();
          break;
      }
    }

    trim($this->Text);
    if (($Length > $this->wTextLine) || $this->LastLine) {
      $this->EndLine();
    }
  }

}

////////////////////////////////////////////////////
// PDF_Label
//
// Class to print labels in Avery or custom formats
//
//
// Copyright (C) 2003 Laurent PASSEBECQ (LPA)
// Based on code by Steve Dillon : steved@mad.scientist.com
//
//-------------------------------------------------------------------
// VERSIONS :
// 1.0  : Initial release
// 1.1  : + : Added unit in the constructor
//        + : Now Positions start @ (1,1).. then the first image @top-left of a page is (1,1)
//        + : Added in the description of a label :
//                font-size    : defaut char size (can be changed by calling Set_Char_Size(xx);
//                paper-size    : Size of the paper for this sheet (thanx to Al Canton)
//                metric        : type of unit used in this description
//                              You can define your label properties in inches by setting metric to "in"
//                              and printing in millimiter by setting unit to "mm" in constructor.
//              Added some labels :
//                5160, 5161, 5162, 5163,5164 : thanx to Al Canton : acanton@adams-blake.com
//                8600                         : thanx to Kunal Walia : kunal@u.washington.edu
//        + : Added 3mm to the position of labels to avoid errors
// 1.2  : + : Added Set_Font_Name method
//        = : Bug of positioning
//        = : Set_Font_Size modified -> Now, just modify the size of the font
//        = : Set_Char_Size renamed to Set_Font_Size
////////////////////////////////////////////////////

/**
* PDF_Label - PDF label editing
* @package PDF_Label
* @author Laurent PASSEBECQ <lpasseb@numericable.fr>
* @copyright 2003 Laurent PASSEBECQ
**/
class PDF_Label extends PDF_WriteTag2{

    // Private properties
    var $_Avery_Name    = "";                // Name of format
    var $_Margin_Left    = 0;                // Left margin of labels
    var $_Margin_Top    = 0;                // Top margin of labels
    var $_X_Space         = 0;                // Horizontal space between 2 labels
    var $_Y_Space         = 0;                // Vertical space between 2 labels
    var $_X_Number         = 0;                // Number of labels horizontally
    var $_Y_Number         = 0;                // Number of labels vertically
    var $_Width         = 0;                // Width of label
    var $_Height         = 0;                // Height of label
    var $_Char_Size        = 10;                // Character size
    var $_Line_Height    = 10;                // Default line height
    var $_Metric         = "mm";                // Type of metric for labels.. Will help to calculate good values
    var $_Metric_Doc     = "mm";                // Type of metric for the document
    var $_Font_Name        = "Arial";            // Name of the font

    var $_COUNTX = 1;
    var $_COUNTY = 1;


    // Listing of labels size
    var $_Avery_Labels = array (
        "5160"=>array("name"=>"5160",    "paper-size"=>"letter",    "metric"=>"mm",    "marginLeft"=>1.762,    "marginTop"=>10.7,        "NX"=>3,    "NY"=>10,    "SpaceX"=>3.175,    "SpaceY"=>0,    "width"=>66.675,    "height"=>25.4,        "font-size"=>8),
        "5161"=>array("name"=>"5161",    "paper-size"=>"letter",    "metric"=>"mm",    "marginLeft"=>0.967,    "marginTop"=>10.7,        "NX"=>2,    "NY"=>10,    "SpaceX"=>3.967,    "SpaceY"=>0,    "width"=>101.6,        "height"=>25.4,        "font-size"=>8),
        "5162"=>array("name"=>"5162",    "paper-size"=>"letter",    "metric"=>"mm",    "marginLeft"=>0.97,        "marginTop"=>20.224,    "NX"=>2,    "NY"=>7,    "SpaceX"=>4.762,    "SpaceY"=>0,    "width"=>100.807,    "height"=>35.72,    "font-size"=>8),
        "5163"=>array("name"=>"5163",    "paper-size"=>"letter",    "metric"=>"mm",    "marginLeft"=>1.762,    "marginTop"=>10.7,         "NX"=>2,    "NY"=>5,    "SpaceX"=>3.175,    "SpaceY"=>0,    "width"=>101.6,        "height"=>50.8,        "font-size"=>8),
        "5164"=>array("name"=>"5164",    "paper-size"=>"letter",    "metric"=>"in",    "marginLeft"=>0.148,    "marginTop"=>0.5,         "NX"=>2,    "NY"=>3,    "SpaceX"=>0.2031,    "SpaceY"=>0,    "width"=>4.0,        "height"=>3.33,        "font-size"=>12),
        "8600"=>array("name"=>"8600",    "paper-size"=>"letter",    "metric"=>"mm",    "marginLeft"=>7.1,         "marginTop"=>19,         "NX"=>3,     "NY"=>10,     "SpaceX"=>9.5,         "SpaceY"=>3.1,     "width"=>66.6,         "height"=>25.4,        "font-size"=>8),
        "L7163"=>array("name"=>"L7163",    "paper-size"=>"A4",        "metric"=>"mm",    "marginLeft"=>5,        "marginTop"=>15,         "NX"=>2,    "NY"=>7,    "SpaceX"=>25,        "SpaceY"=>0,    "width"=>99.1,        "height"=>38.1,        "font-size"=>9)
    );

    // convert units (in to mm, mm to in)
    // $src and $dest must be "in" or "mm"
    function _Convert_Metric ($value, $src, $dest) {
        if ($src != $dest) {
            $tab["in"] = 39.37008;
            $tab["mm"] = 1000;
            return $value * $tab[$dest] / $tab[$src];
        } else {
            return $value;
        }
    }

    // Give the height for a char size given.
    function _Get_Height_Chars($pt) {
        // Array matching character sizes and line heights
        $_Table_Hauteur_Chars = array(6=>2, 7=>2.5, 8=>3, 9=>4, 10=>5, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
        if (in_array($pt, array_keys($_Table_Hauteur_Chars))) {
            return $_Table_Hauteur_Chars[$pt];
        } else {
            return 100; // There is a prob..
        }
    }

    function _Set_Format($format) {
        $this->_Metric         = $format["metric"];
        $this->_Avery_Name     = $format["name"];
        $this->_Margin_Left    = $this->_Convert_Metric ($format["marginLeft"], $this->_Metric, $this->_Metric_Doc);
        $this->_Margin_Top    = $this->_Convert_Metric ($format["marginTop"], $this->_Metric, $this->_Metric_Doc);
        $this->_X_Space     = $this->_Convert_Metric ($format["SpaceX"], $this->_Metric, $this->_Metric_Doc);
        $this->_Y_Space     = $this->_Convert_Metric ($format["SpaceY"], $this->_Metric, $this->_Metric_Doc);
        $this->_X_Number     = $format["NX"];
        $this->_Y_Number     = $format["NY"];
        $this->_Width         = $this->_Convert_Metric ($format["width"], $this->_Metric, $this->_Metric_Doc);
        $this->_Height         = $this->_Convert_Metric ($format["height"], $this->_Metric, $this->_Metric_Doc);
        $this->Set_Font_Size($format["font-size"]);
    }

    // Constructor
    function PDF_Label ($format, $unit="mm", $posX=1, $posY=1) {
        if (is_array($format)) {
            // Custom format
            $Tformat = $format;
        } else {
            // Avery format
            $Tformat = $this->_Avery_Labels[$format];
        }

        parent::PDF_WriteTag2("P", $Tformat["metric"], $Tformat["paper-size"]);
        $this->_Set_Format($Tformat);
        $this->Set_Font_Name("Arial");
        $this->SetMargins(0,0);
        $this->SetAutoPageBreak(false);

        $this->_Metric_Doc = $unit;
        // Start at the given label position
        if ($posX > 1) $posX--; else $posX=0;
        if ($posY > 1) $posY--; else $posY=0;
        if ($posX >=  $this->_X_Number) $posX =  $this->_X_Number-1;
        if ($posY >=  $this->_Y_Number) $posY =  $this->_Y_Number-1;
        $this->_COUNTX = $posX;
        $this->_COUNTY = $posY;
    }

    // Sets the character size
    // This changes the line height too
    function Set_Font_Size($pt) {
        if ($pt > 3) {
            $this->_Char_Size = $pt;
            $this->_Line_Height = $this->_Get_Height_Chars($pt);
            $this->SetFontSize($this->_Char_Size);
        }
    }

    // Method to change font name
    function Set_Font_Name($fontname) {
        if ($fontname != "") {
            $this->_Font_Name = $fontname;
            $this->SetFont($this->_Font_Name);
        }
    }

    // Print a label
    function Add_PDF_Label($texte) {
        // We are in a new page, then we must add a page
        if (($this->_COUNTX ==0) and ($this->_COUNTY==0)) {
            $this->AddPage();
        }

        $_PosX = $this->_Margin_Left+($this->_COUNTX*($this->_Width+$this->_X_Space));
        $_PosY = $this->_Margin_Top+($this->_COUNTY*($this->_Height+$this->_Y_Space));
        $this->SetXY($_PosX+3, $_PosY+3);
        $this->MultiCell($this->_Width, $this->_Line_Height, $texte);
        $this->_COUNTY++;

        if ($this->_COUNTY == $this->_Y_Number) {
            // End of column reached, we start a new one
            $this->_COUNTX++;
            $this->_COUNTY=0;
        }

        if ($this->_COUNTX == $this->_X_Number) {
            // Page full, we start a new one
            $this->_COUNTX=0;
            $this->_COUNTY=0;
        }
    }

}

/**
 * My extension for PDF_Label.
 *
 * Add label border and tags.
 * @package TCPDF
 * @author David Hernandez Sanz
 * @license Freeware
 */
class PDF_Label2 extends PDF_Label {
  /***********************/
  /* Privated properties */
  /***********************/

  /**
   * Indicates if draws border of label.
   *
   * @var boolean
   * @access private
   */
  var $labelBorder;

  /**
   * Style border of label.
   *
   * Array like for {@link SetLineStyle SetLineStyle}.
   * @var array
   * @access private
   */
  var $styleBorder;

  /**
   * Print label direction.
   *
   * Possible values are:
   * <ul>
   *   <li>H: Horizontal.</li>
   *   <li>V: Vertical.</li>
   * </ul>
   * @var string
   * @access private
   */
  var $printDirection;

  /**
   * Indicates if add first page.
   *
   * Add first page if (posX ini, posY ini) > (1, 1).
   * @var boolean
   * @access private
   */
  var $addFirstPage;

  // Destroy from PDF_Label
  function _Get_Height_Chars($pt) { }
  function Set_Font_Size($pt) { }
  function Set_Font_Name($fontname) { }

  /******************/
  /* Public methods */
  /******************/

  /**
   * Constructor.
   *
   * Redefine from PDF_Label.
   * @param mixed $format Format of labels. Possible values are:
   * <ul>
   *   <li>A string: Name of predefinite format.</li>
   *   <li>An array with this keys:
   *       <ul>
   *         <li>name (string): Name of new format.</li>
   *         <li>paper-size (string): Paper size.</li>
   *         <li>metric (string): Metric for the labels.</li>
   *         <li>marginLeft, marginTop (float): Margin left and top of
   * page.</li>
   *         <li>NX, NY (integer): Number of label in axis x and y.</li>
   *         <li>SpaceX, SpaceY (float): X and y space between labels.</li>
   *         <li>width, height (float): Width and height of label.</li>
   *         <li>font-size (float): Default font size.</li>
   *         <li>paper_width, paper_height (float): Paper size (optional,
   * instead of paper-size).</li>
   *       </ul></li>
   * </ul>
   * @param string $unit Metric for the document. Default value: mm.
   * @param integer $posX Start x position of labels. Default value: 1.
   * @param integer $posY Start y position of labels. Default value: 1.
   * @param boolean $labelBorder Indicates if draw border of labels. Default
   * value: true.
   * @param array $styleBorder Style border of labels. Array like for
   * {@link SetLineStyle SetLineStyle}. Default value: default border style
   * (empty array).
   * @param string $printDirection Print labels direction. Possible values are:
   * <ul>
   *   <li>H: Horizontal (default).</li>
   *   <li>V: Vertical.</li>
   * </ul>
   * @access public
   */
  function PDF_Label2($format, $unit = "mm", $posX = 1, $posY = 1,
                      $labelBorder = true, $styleBorder = array(),
                      $printDirection = "H") {
    if (!is_array($format)) {
      $format = $this->_Avery_Labels[$format];
    }
    if ($format["paper_width"] && $format["paper_height"]) {
      parent::PDF_WriteTag2("P", $unit, array($format["paper_width"],
                   $format["paper_height"]));
    } else {
      parent::PDF_WriteTag2("P", $unit, $format["paper-size"]);
    }
    $this->_Metric_Doc = $unit;
    $this->_Set_Format($format);

    $this->SetMargins(0, 0);
    $this->SetAutoPageBreak(false);

    $this->setPrintHeader(false);
    $this->setPrintFooter(false);

    // Start at the given label position
    if (1 < $posX) {
      $posX--;
    } else {
      $posX = 0;
    }
    if (1 < $posY) {
      $posY--;
    } else {
      $posY = 0;
    }
    if ($posX >= $this->_X_Number) {
      $posX = $this->_X_Number - 1;
    }
    if ($posY >= $this->_Y_Number) {
      $posY = $this->_Y_Number - 1;
    }
    $this->_COUNTX = $posX;
    $this->_COUNTY = $posY;

    $this->labelBorder = ($labelBorder) ? 1 : 0;
    if (!$styleBorder) {
      $styleBorder = array("width" => 0.20,    "cap"  => "round",
                           "join"  => "round", "dash" => "2,8",   "phase" => 0,
                           "color" => array(127, 127, 127));
    }
    $this->styleBorder = $styleBorder;
    $this->printDirection = $printDirection;

    // We are in middle of first page, then we must add a page
    $this->addFirstPage = $this->_COUNTX || $this->_COUNTY;
  }

  /**
   * Add label.
   *
   * Redefine from PDF_Label.
   * @param string $texte Text.
   * @param string $align Text align. Possible values are:
   * <ul>
   *   <li>L or empty string: Left align.</li>
   *   <li>C: Center.</li>
   *   <li>R: Right align.</li>
   *   <li>J: Justification (default).</li>
   * </ul>
   * @param string $valign Text vertical align. Possible values are:
   * <ul>
   *   <li>T: Top.</li>
   *   <li>M: Middle (default).</li>
   *   <li>B: Bottom.</li>
   * </ul>
   * @param mixed $padding Padding for text. Possible values are:
   * <ul>
   *   <li>A float: Left, right, top and bottom padding.</li>
   *   <li>A string with of float values:
   *       <ul>
   *         <li>2 values: Format: "left/right,top/bottom".</li>
   *         <li>3 values: Format: "left/right,top,bottom".</li>
   *         <li>4 values: Format: "left,top,bottom,right".</li>
   *       </ul></li>
   * </ul>
   * Default value: 0.
   * @param float $leading Leading for text. Default value: 1.20.
   * @param float $leadingParagraph Leading for paragraph. Default value: 0.60.
   * @return array (X, Y) position of label. Format:
   * array(x => x_value, y => y_value).
   * @access public
   */
  function Add_PDF_Label($texte, $align = "J", $valign = "M", $padding = 0,
                         $leading = 1.20, $leadingParagraph = 0.60) {
    // We are in a new page, then we must add a page
    if ($this->addFirstPage || (!$this->_COUNTX && !$this->_COUNTY)) {
      $this->AddPage();
      $this->SetLineStyle($this->styleBorder);
      $this->addFirstPage = false;
    }

    $x = $this->_Margin_Left +
         ($this->_COUNTX * ($this->_Width + $this->_X_Space));
    $y = $this->_Margin_Top +
         ($this->_COUNTY * ($this->_Height + $this->_Y_Space));

    $this->SetXY($x, $y);
    $this->WriteTag($this->_Width, $this->_Height, $texte, $this->labelBorder,
                    $align, 0, array(), $valign, $padding, $leading,
                    $leadingParagraph);

    if ("V" == $this->printDirection) {
      $this->_COUNTY++;

      if ($this->_COUNTY == $this->_Y_Number) {
        // End of column reached, we start a new one
        $this->_COUNTX++;
        $this->_COUNTY = 0;
      }

      if ($this->_COUNTX == $this->_X_Number) {
        // Page full, we start a new one
        $this->_COUNTX = 0;
        $this->_COUNTY = 0;
      }
    } else { // H
      $this->_COUNTX++;

      if ($this->_COUNTX == $this->_X_Number) {
        // End of row reached, we start a new one
        $this->_COUNTY++;
        $this->_COUNTX = 0;
      }

      if ($this->_COUNTY == $this->_Y_Number) {
        // Page full, we start a new one
        $this->_COUNTX = 0;
        $this->_COUNTY = 0;
      }
    }
    return compact("x", "y");
  }

  /********************/
  /* Privated methods */
  /********************/

  /**
   * Set format of labels.
   *
   * Redefine from PDF_Label.
   * @param array $format Format of labels. Array with this keys:
   * <ul>
   *   <li>name (string): Name of new format.</li>
   *   <li>metric (string): Metric for the labels.</li>
   *   <li>marginLeft, marginTop (float): Margin left and top of page.</li>
   *   <li>NX, NY (integer): Number of label in axis x and y.</li>
   *   <li>SpaceX, SpaceY (float): X and y space between labels.</li>
   *   <li>width, height (float): Width and height of label.</li>
   *   <li>font-size (float): Default font size.</li>
   * </ul>
   * @access private
   */
  function _Set_Format($format) {
    $this->_Metric = $format["metric"];
    $this->_Avery_Name = $format["name"];
    $this->_Margin_Left = $this->_Convert_Metric($format["marginLeft"],
                                                 $this->_Metric,
                                                 $this->_Metric_Doc);
    $this->_Margin_Top = $this->_Convert_Metric($format["marginTop"],
                                                $this->_Metric,
                                                $this->_Metric_Doc);
    $this->_X_Space = $this->_Convert_Metric($format["SpaceX"], $this->_Metric,
                                             $this->_Metric_Doc);
    $this->_Y_Space = $this->_Convert_Metric($format["SpaceY"], $this->_Metric,
                                             $this->_Metric_Doc);
    $this->_X_Number = $format["NX"];
    $this->_Y_Number = $format["NY"];
    $this->_Width = $this->_Convert_Metric($format["width"], $this->_Metric,
                                           $this->_Metric_Doc);
    $this->_Height = $this->_Convert_Metric($format["height"], $this->_Metric,
                                            $this->_Metric_Doc);
    $this->SetFontSize($format["font-size"]);
  }

}

/**
 * My extension for PDF_WriteTag2.
 *
 * Add table.
 * @package TCPDF
 * @author David Hernandez Sanz
 * @license Freeware
 */
class PDF_Table extends PDF_WriteTag2 {
  /***********************/
  /* Privated properties */
  /***********************/

  /**
   * Style of table.
   *
   * Array with keys among the following:
   * <ul>
   *   <li>tb_align (mixed): Table align. Possible values are:
   *       <ul>
   *         <li>A string:
   *             <ul>
   *               <li>L (string) or empty string: Left align.</li>
   *               <li>C (string): Center.</li>
   *               <li>R (string): Right align.</li>
   *             </ul></li>
   *         <li>A float: Relative position to left margin.</li>
   *       </ul></li>
   *   <li>column_width (array): Width of each column. Format:
   * array(width1, width2, ..., width(num_col - 1)).</li>
   *   <li>align (string): Text align. Possible values are:
   *       <ul>
   *         <li>L or empty string: Left align.</li>
   *         <li>C: Center.</li>
   *         <li>R: Right align.</li>
   *         <li>J: Justification.</li>
   *       </ul></li>
   *   <li>valign (string): Text vertical align. Possible values are:
   *       <ul>
   *         <li>T: Top.</li>
   *         <li>M: Middle.</li>
   *         <li>B: Bottom.</li>
   *       </ul></li>
   *   <li>padding (mixed): Padding for text. Possible values are:
   *       <ul>
   *         <li>A float: Left, right, top and bottom padding.</li>
   *         <li>A string with of float values:
   *             <ul>
   *               <li>2 values: Format: "left/right,top/bottom".</li>
   *               <li>3 values: Format: "left/right,top,bottom".</li>
   *               <li>4 values: Format: "left,top,bottom,right".</li>
   *             </ul></li>
   *       </ul></li>
   *   <li>leading (float): Leading for text.</li>
   *   <li>leadingParagraph (float): Leading for paragraph.</li>
   *   <li>bg_color (array): Background color. Format:
   * array(red, green, blue).</li>
   * </ul>
   * @var array
   * @access private
   */
  var $tbStyle;

  /**
   * Style of table border.
   *
   * Array with this keys:
   * <ul>
   *   <li>table (mixed): Table border. Possible values are:
   *       <ul>
   *         <li>0 (integer): No border (default).</li>
   *         <li>1 (integer): Default border.</li>
   *         <li>An array like for $border_style in {@link Rect Rect}.</li>
   *       </ul></li>
   *   <li>head_body (mixed): Border between head and body. Possible values
   * are:
   *       <ul>
   *         <li>0 (integer): No border (default).</li>
   *         <li>1 (integer): Default border.</li>
   *         <li>An array like for {@link SetLineStyle SetLineStyle}.</li>
   *       </ul></li>
   *   <li>column_header (array): Border between each header column. Array of
   * (num_col - 1) items. Possible values for each item are:
   *       <ul>
   *         <li>0 (integer): No border (default).</li>
   *         <li>1 (integer): Default border.</li>
   *         <li>An array like for {@link SetLineStyle SetLineStyle}.</li>
   *       </ul></li>
   *   <li>column_body (array): Border between each body column. Array of
   * (num_col - 1) items like column_header key.</li>
   *   <li>row_header (array): Border between header rows. Array of k items.
   * Possible values for each item are:
   *       <ul>
   *         <li>0 (integer): No border (default).</li>
   *         <li>1 (integer): Default border.</li>
   *         <li>An array like for {@link SetLineStyle SetLineStyle}.</li>
   *       </ul></li>
   *   <li>row_body (array): Border between body rows. Array of k items like
   * row_header key.</li>
   * </ul>
   * @var array
   * @access private
   */
  var $tbBorderStyle;

  /**
   * Data of body.
   *
   * Array 2 dimension for body data, with this keys:
   * <ul>
   *   <li>text (string): Text of cell.</li>
   *   <li>colspan (integer): Number columns span.</li>
   *   <li>rowspan (integer): Number rows span.</li>
   *   <li>height_min (float): Height minimum.</li>
   *   <li>align (string): Text align. Possible values are:
   *       <ul>
   *         <li>L: Left align.</li>
   *         <li>C: Center.</li>
   *         <li>R: Right align.</li>
   *         <li>J: Justification.</li>
   *       </ul></li>
   *   <li>valign (string): Text vertical align. Possible values are:
   *       <ul>
   *         <li>T: Top.</li>
   *         <li>M: Middle.</li>
   *         <li>B: Bottom.</li>
   *       </ul></li>
   *   <li>padding (mixed): Padding for text. Possible values are:
   *       <ul>
   *         <li>A float: Left, right, top and bottom padding.</li>
   *         <li>A string with of float values:
   *             <ul>
   *               <li>2 values: Format: "left/right,top/bottom".</li>
   *               <li>3 values: Format: "left/right,top,bottom".</li>
   *               <li>4 values: Format: "left,top,bottom,right".</li>
   *             </ul></li>
   *       </ul></li>
   *   <li>leading (float): Leading for text.</li>
   *   <li>leadingParagraph (float): Leading for paragraph.</li>
   *   <li>border (array): Border cell. Array like for $border_style in
   * {@link Rect Rect}.</li>
   *   <li>bg_color (array): Background color. Format:
   * array(red, green, blue).</li>
   * </ul>
   * If array not have a key, the default is the table style. If an item is
   * part of columns or rows span, value is null not array.
   * @var array
   * @access private
   */
  var $tbBodyData;

  /**
   * Style of each column body.
   *
   * Array of num_col items. Each item is an array with keys among the
   * following:
   * <ul>
   *   <li>align (string): Text align. Possible values are:
   *       <ul>
   *         <li>L: Left align.</li>
   *         <li>C: Center.</li>
   *         <li>R: Right align.</li>
   *         <li>J: Justification.</li>
   *       </ul></li>
   *   <li>valign (string): Text vertical align. Possible values are:
   *       <ul>
   *         <li>T: Top.</li>
   *         <li>M: Middle.</li>
   *         <li>B: Bottom.</li>
   *       </ul></li>
   *   <li>padding (mixed): Padding for text. Possible values are:
   *       <ul>
   *         <li>A float: Left, right, top and bottom padding.</li>
   *         <li>A string with of float values:
   *             <ul>
   *               <li>2 values: Format: "left/right,top/bottom".</li>
   *               <li>3 values: Format: "left/right,top,bottom".</li>
   *               <li>4 values: Format: "left,top,bottom,right".</li>
   *             </ul></li>
   *       </ul></li>
   *   <li>leading (float): Leading for text.</li>
   *   <li>leadingParagraph (float): Leading for paragraph.</li>
   *   <li>bg_color (array): Background color. Format:
   * array(red, green, blue).</li>
   * </ul>
   * If array not have a key, the default is the table style.
   * @var array
   * @access private
   */
  var $tbColumnBodyStyle;

  /**
   * Style of rows body.
   *
   * Array of k items. Each items is an array with keys among the following:
   * <ul>
   *   <li>height_min (float): Height minimum.</li>
   *   <li>align (string): Text align. Possible values are:
   *       <ul>
   *         <li>L: Left align.</li>
   *         <li>C: Center.</li>
   *         <li>R: Right align.</li>
   *         <li>J: Justification.</li>
   *       </ul></li>
   *   <li>valign (string): Text vertical align. Possible values are:
   *       <ul>
   *         <li>T: Top.</li>
   *         <li>M: Middle.</li>
   *         <li>B: Bottom.</li>
   *       </ul></li>
   *   <li>padding (mixed): Padding for text. Possible values are:
   *       <ul>
   *         <li>A float: Left, right, top and bottom padding.</li>
   *         <li>A string with of float values:
   *             <ul>
   *               <li>2 values: Format: "left/right,top/bottom".</li>
   *               <li>3 values: Format: "left/right,top,bottom".</li>
   *               <li>4 values: Format: "left,top,bottom,right".</li>
   *             </ul></li>
   *       </ul></li>
   *   <li>leading (float): Leading for text.</li>
   *   <li>leadingParagraph (float): Leading for paragraph.</li>
   *   <li>bg_color (array): Background color. Format:
   * array(red, green, blue).</li>
   * If array not have a key, the default is the table style.
   * @var array
   * @access private
   */
  var $tbRowBodyStyle;

  /**
   * Data of header.
   *
   * Array 2-dimension for header data. Array like $tbBodyData class attribute.
   * @var array
   * @access private
   */
  var $tbHeaderData;

  /**
   * Style of each column header.
   *
   * Array of num_col items like $tbColumnBodyStyle class attribute.
   * @var array
   * @access private
   */
  var $tbColumnHeaderStyle;

  /**
   * Style of rows header.
   *
   * Array of k items like $tbRowBodyStyle class attribute.
   * @var array
   * @access private
   */
  var $tbRowHeaderStyle;

  /**
   * The X position where the table starts.
   *
   * @var float
   * @access private
   */
  var $tbStartX;

  /**
   * The Y position where the table starts.
   *
   * @var float
   * @access private
   */
  var $tbStartY;

  /**
   * The Y position where the table header starts.
   *
   * @var float
   * @access private
   */
  var $tbHeaderStartY;

  /**
   * Height of each header row.
   *
   * Format: array(height1, height2,...).
   * @var array
   * @access private
   */
  var $tbHeaderHeightRow;

  /**
   * Max height of each header row.
   *
   * Format: array(height1, height2,...).
   * @var array
   * @access private
   */
  var $tbHeaderHeightRowMax;

  /**
   * Height of each body row.
   *
   * Format: array(height1, height2,...).
   * @var array
   * @access private
   */
  var $tbBodyHeightRow;

  /**
   * Max height of each body row.
   *
   * Format: array(height1, height2,...).
   * @var array
   * @access private
   */
  var $tbBodyHeightRowMax;

  /******************/
  /* Public methods */
  /******************/

  /**
   * Draw table.
   *
   * @param array $tbStyle Style of table. Format is like $tbStyle class
   * attribute. If not have a key, the default is the previous style.
   * @param array $tbBorderStyle Style of table border. Format is like
   * $tbBorderStyle class attribute. If a value is 1, the default is the
   * previous style.
   * @param array $tbBodyData Data of body. Format is like $tbBodyData class
   * attribute. If array not have a key, the default is the table style. If an
   * item is part of columns or rows span, value is null not array.
   * @param array $tbColumnBodyStyle Style of each column body. Format is like
   * $tbColumnBodyStyle class attribute. If array not have a key, the default
   * is the table style. Default value: empty array.
   * @param array $tbRowBodyStyle Style of rows body. Format is like
   * $tbRowBodyStyle class attribute. If array not have a key, the default is
   * the table style. Default value: empty array.
   * @param array $tbHeaderData Data of header. Format is like $tbHeaderData
   * class attribute. Default value: empty array.
   * @param array $tbColumnHeaderStyle Style of each column header. Format is
   * like $tbColumnHeaderStyle class attribute. Default value: empty array.
   * @param array $tbRowHeaderStyle Style of rows header. Format is like
   * $tbRowHeaderStyle class attribute. Default value: empty array.
   * @access public
   */
  function Table($tbStyle, $tbBorderStyle, $tbBodyData,
                 $tbColumnBodyStyle = array(), $tbRowBodyStyle = array(),
                 $tbHeaderData = array(), $tbColumnHeaderStyle = array(),
                 $tbRowHeaderStyle = array()) {
    $this->HeightTable($tbStyle, $tbBodyData, $tbColumnBodyStyle,
                       $tbRowBodyStyle, $tbHeaderData,
                       $tbColumnHeaderStyle, $tbRowHeaderStyle);
    if ($tbHeaderData || $tbBodyData) {
      // Set parameters
      $this->tbBorderStyle = $tbBorderStyle;

      // Draw table
      if ($this->tbHeaderData) {
        $height = array_sum($this->tbHeaderHeightRow);
        if ($this->tbBodyData) {
          $height += $this->tbBodyHeightRowMax[0];
        }
        if ($this->CheckPageBreak($height)) {
          $this->AddPage($this->CurOrientation);
        }
      }
      $this->DrawTableHeader();
      $this->DrawTableBody();
      $this->DrawTableBorder();
    }
  }

  /**
   * Calculate height table.
   *
   * @param array $tbStyle Style of table. Format is like $tbStyle class
   * attribute. If not have a key, the default is the previous style.
   * @param array $tbBodyData Data of body. Format is like $tbBodyData class
   * attribute. If array not have a key, the default is the table style. If an
   * item is part of columns or rows span, value is null not array.
   * @param array $tbColumnBodyStyle Style of each column body. Format is like
   * $tbColumnBodyStyle class attribute. If array not have a key, the default
   * is the table style. Default value: empty array.
   * @param array $tbRowBodyStyle Style of rows body. Format is like
   * $tbRowBodyStyle class attribute. If array not have a key, the default is
   * the table style. Default value: empty array.
   * @param array $tbHeaderData Data of header. Format is like $tbHeaderData
   * class attribute. Default value: empty array.
   * @param array $tbColumnHeaderStyle Style of each column header. Format is
   * like $tbColumnHeaderStyle class attribute. Default value: empty array.
   * @param array $tbRowHeaderStyle Style of rows header. Format is like
   * $tbRowHeaderStyle class attribute. Default value: empty array.
   * @return float Height of table.
   * @access public
   */
  function HeightTable($tbStyle, $tbBodyData, $tbColumnBodyStyle = array(),
                       $tbRowBodyStyle = array(), $tbHeaderData = array(),
                       $tbColumnHeaderStyle = array(),
                       $tbRowHeaderStyle = array()) {
    $height = 0;
    if ($tbHeaderData || $tbBodyData) {
      // Set parameters
      $this->tbStyle = $tbStyle;
      if (!isset($this->tbStyle["tb_align"]) ||
          !$this->tbStyle["tb_align"]) {
        $this->tbStyle["tb_align"] = "L";
      }
      $this->tbStyle["tb_width"] = 0;
      foreach ($this->tbStyle["column_width"] as $width) {
        $this->tbStyle["tb_width"] += $width;
      }
      $this->tbColumnHeaderStyle = $tbColumnHeaderStyle;
      $this->tbColumnBodyStyle = $tbColumnBodyStyle;
      $this->tbRowHeaderStyle = $tbRowHeaderStyle;
      $this->tbRowBodyStyle = $tbRowBodyStyle;
      $this->tbHeaderData = $tbHeaderData;
      $this->tbBodyData = $tbBodyData;

      // Set height of each row
      $this->SetTbHeightRow("header");
      $this->SetTbHeightRow("body");

      if ($this->tbHeaderHeightRow) {
        $height += array_sum($this->tbHeaderHeightRow);
      }
      if ($this->tbBodyHeightRow) {
        $height += array_sum($this->tbBodyHeightRow);
      }
    }
    return $height;
  }

  /********************/
  /* Privated methods */
  /********************/

  /**
   * Set height of each row.
   *
   * @param string $type Type of row. Possible values are:
   * <ul>
   *   <li>header: Header rows.</li>
   *   <li>body: Body rows.</li>
   * </ul>
   * @access private
   */
  function SetTbHeightRow($type) {
    switch ($type) {
      case "header":
        $tb_type_data = $this->tbHeaderData;
        $tb_column_type_style = $this->tbColumnHeaderStyle;
        $tb_row_type_style = $this->tbRowHeaderStyle;
        break;
      case "body":
        $tb_type_data = $this->tbBodyData;
        $tb_column_type_style = $this->tbColumnBodyStyle;
        $tb_row_type_style = $this->tbRowBodyStyle;
        break;
    }

    $tb_type_height_row = array();
    $tb_type_height_row_max = array();
    if ($tb_type_data) {
      $dim_j = count($tb_type_data);
      $dim_i = count($tb_type_data[0]);
      $dim_k = count($tb_row_type_style);

      // Computes the width of the cells
      $padding = $this->Padding;
      for ($j = 0; $j < $dim_j; $j++) {
        if ($tb_row_type_style) {
          $k = $j % $dim_k;
        }
        $i = 0;
        while ($i < $dim_i) {
          if ($tb_type_data[$j][$i]) {
            $colspan = (isset($tb_type_data[$j][$i]["colspan"]))
                           ? $tb_type_data[$j][$i]["colspan"]
                           : 1;
            $tb_type_data[$j][$i]["width"] =
                $this->tbStyle["column_width"][$i];
            if (1 < $colspan) {
              for ($m = $i + 1; $colspan > 1; $colspan--, $m++) {
                $tb_type_data[$j][$i]["width"] +=
                    $this->tbStyle["column_width"][$m];
              }
              $m--;
            } else {
              $m = $i;
            }

            $style = $this->tbStyle;
            if ($tb_row_type_style) {
              $style = array_merge($style, $tb_row_type_style[$k]);
            }
            if ($tb_column_type_style && $tb_column_type_style[$i]) {
              $style = array_merge($style, $tb_column_type_style[$i]);
            }
            $style = array_merge($style, $tb_type_data[$j][$i]);
            if (isset($style["padding"])) {
              $this->Padding = $style["padding"];
            }
            $this->Padding();
            if (isset($style["leading"])) {
              $this->leading = $style["leading"];
            }
            if (isset($style["leadingParagraph"])) {
              $this->leadingParagraph = $style["leadingParagraph"];
            }
            $height_min =
                (isset($style["height_min"])) ? $style["height_min"] : 0;
            $w = $tb_type_data[$j][$i]["width"] - $this->lPadding -
                 $this->rPadding;
            $tb_type_data[$j][$i]["text"] =
                $this->_BreakLongWord($w, $tb_type_data[$j][$i]["text"]);
            $tb_type_data[$j][$i]["height_text"] =
                max($height_min,
                    $this->HeightText($tb_type_data[$j][$i]["width"],
                                      $tb_type_data[$j][$i]["text"],
                                      $this->Padding, $this->leading,
                                      $this->leadingParagraph));
            $i = $m;
          }
          $i++;
        }
      }
      $this->Padding = $padding;
      $this->Padding();

      // Computes the temporal height of the rows and temporal height of the
      // cells
      $tb_type_height_row = array();
      for ($j = 0; $j < $dim_j; $j++) {
        $height_text = array();
        for ($i = 0; $i < $dim_i; $i++) {
          if ($tb_type_data[$j][$i] &&
              !isset($tb_type_data[$j][$i]["rowspan"])) {
            $height_text[] = $tb_type_data[$j][$i]["height_text"];
          }
        }
        $tb_type_height_row[$j] = max($height_text);
        for ($i = 0; $i < $dim_i; $i++) {
          if ($tb_type_data[$j][$i] &&
              !isset($tb_type_data[$j][$i]["rowspan"])) {
            $tb_type_data[$j][$i]["height"] = $tb_type_height_row[$j];
          }
        }
      }

      // Computes the height of the cells
      for ($i = 0; $i < $dim_i; $i++) {
        $j = 0;
        while ($j < $dim_j) {
          if ($tb_type_data[$j][$i] &&
              isset($tb_type_data[$j][$i]["rowspan"])) {
            $rowspan = $tb_type_data[$j][$i]["rowspan"];
            $tb_type_data[$j][$i]["height"] = 0;
            for ($n = $j; $rowspan > 0; $rowspan--, $n++) {
              $tb_type_data[$j][$i]["height"] += $tb_type_height_row[$n];
            }
            $j = $n;
          } else
            $j++;
        }
      }
      for ($i = 0; $i < $dim_i; $i++) {
        for ($j = 0; $j < $dim_j; $j++) {
          $cell = $tb_type_data[$j][$i];
          if ($cell && isset($cell["rowspan"]) &&
              ($cell["height"] < $cell["height_text"])) {
            $n = $j + $cell["rowspan"] - 1;
            $height_diff = $cell["height_text"] - $cell["height"];
            $tb_type_height_row[$n] += $height_diff;
            $tb_type_data[$j][$i]["height"] += $height_diff;
            for ($m = 0; $m < $dim_i; $m++) {
              if ($tb_type_data[$n][$m]) {
                $tb_type_data[$n][$m]["height"] += $height_diff;
              }
            }
          }
        }
      }
      $tb_type_height_row_max = array();
      for ($j = 0; $j < $dim_j; $j++) {
        $height_row = array();
        for ($i = 0; $i < $dim_i; $i++) {
          if ($tb_type_data[$j][$i]) {
            $height_row[] = $tb_type_data[$j][$i]["height"];
          }
        }
        $tb_type_height_row_max[$j] = max($height_row);
      }

      for ($j = ($dim_j - 1); $j >= 0; $j--){
        if ($tb_type_height_row[$j] < $tb_type_height_row_max[$j]) {
          $height_row = $tb_type_height_row[$j];
          $height_row_max = $tb_type_height_row_max[$j];
          $continue = true;
          for ($n = $j + 1; ($n < $dim_j) && $continue; $n++) {
            $height_row += $tb_type_height_row[$n];
            if ($height_row < $height_row_max) {
              if ($tb_type_height_row[$n] < $tb_type_height_row_max[$n]) {
                $height_row_max = max($height_row_max,
                                      $height_row - $tb_type_height_row[$n]
                                      + $tb_type_height_row_max[$n]);
              }
            } else {
              if ($tb_type_height_row[$n] < $tb_type_height_row_max[$n]) {
                $height_row_max += $tb_type_height_row_max[$n] -
                                   $tb_type_height_row[$n];
              } else {
                $continue = false;
              }
            }
          }
          $tb_type_height_row_max[$j] = $height_row_max;
        }
      }
    }

    switch ($type) {
      case "header":
        $this->tbHeaderHeightRow = $tb_type_height_row;
        $this->tbHeaderHeightRowMax = $tb_type_height_row_max;
        $this->tbHeaderData = $tb_type_data;
        break;
      case "body":
        $this->tbBodyHeightRow = $tb_type_height_row;
        $this->tbBodyHeightRowMax = $tb_type_height_row_max;
        $this->tbBodyData = $tb_type_data;
        break;
    }
  }

  /**
   * Set table style.
   *
   * @param array $tbStyle Style of table. Array of items. Each item is an
   * array with keys among the following:
   * <ul>
   *   <li>align (string): Text align. Possible values are:
   *       <ul>
   *         <li>L or empty string: Left align.</li>
   *         <li>C: Center.</li>
   *         <li>R: Right align.</li>
   *         <li>J: Justification.</li>
   *       </ul></li>
   *   <li>valign (string): Text vertical align. Possible values are:
   *       <ul>
   *         <li>T: Top.</li>
   *         <li>M: Middle.</li>
   *         <li>B: Bottom.</li>
   *       </ul></li>
   *   <li>padding (mixed): Padding for text. Possible values are:
   *       <ul>
   *         <li>A float: Left, right, top and bottom padding.</li>
   *         <li>A string with of float values:
   *             <ul>
   *               <li>2 values: Format: "left/right,top/bottom".</li>
   *               <li>3 values: Format: "left/right,top,bottom".</li>
   *               <li>4 values: Format: "left,top,bottom,right".</li>
   *             </ul></li>
   *       </ul></li>
   *   <li>leading (float): Leading for text.</li>
   *   <li>leadingParagraph (float): Leading for paragraph.</li>
   *   <li>bg_color (array): Background color. Format:
   * array(red, green, blue).</li>
   * </ul>
   * If not have a key, the default is the previous style or table style.
   * @param boolean &$fill Fill or not next cells.
   * @access private
   */
  function SetTableStyle($style, &$fill) {
    $style2 = $this->tbStyle;
    foreach ($style as $s) {
      $style2 = array_merge($style2, $s);
    }

    if (isset($style2["align"])) {
      $this->align = $style2["align"];
    }
    if (isset($style2["valign"])) {
      $this->valign = $style2["valign"];
    }
    if (isset($style2["padding"])) {
      $this->Padding = $style2["padding"];
    }
    $this->Padding();
    if (isset($style2["leading"])) {
      $this->leading = $style2["leading"];
    }
    if (isset($style2["leadingParagraph"])) {
      $this->leadingParagraph = $style2["leadingParagraph"];
    }
    $fill = (isset($style2["bg_color"])) ? 1 : 0;
    if (isset($style2["bg_color"])) {
      list($r, $g, $b) = $style2["bg_color"];
      $this->SetFillColor($r, $g, $b);
    }
  }

  /**
   * Align the table.
   *
   * @access private
   */
  function TableAlign() {
    $page_width = $this->w - $this->rMargin - $this->lMargin;
    if (is_numeric($this->tbStyle["tb_align"]))
      $dx = min($this->tbStyle["tb_align"],
                $page_width - $this->tbStyle["tb_width"]);
    else
      switch($this->tbStyle["tb_align"]) {
        case "L": default:
          $dx  = 0;
          break;
        case "C":
          $dx = ($page_width - $this->tbStyle["tb_width"]) / 2;
          break;
        case "R":
          $dx = $page_width - $this->tbStyle["tb_width"];
          break;
      }
    $this->SetX($this->lMargin + $dx);
  }

  /**
   * Draws the table header.
   *
   * @access private
   */
  function DrawTableHeader() {

    $this->TableAlign();

    $this->tbStartX = $this->GetX();
    $this->tbStartY = $this->GetY();

    if ($this->tbHeaderData) {
      if (!$this->tbColumnHeaderStyle && !$this->tbRowHeaderStyle) {
        $this->SetTableStyle(array(), $fill);
      }
      $this->DrawTableRow("header", $this->tbStartY, $fill);
      $this->tbHeaderStartY = $this->GetY();
    }
  }

  /**
   * Draws the table body.
   *
   * @access private
   */
  function DrawTableBody() {
    if ($this->tbBodyData) {
      if (($this->tbColumnHeaderStyle || $this->tbRowHeaderStyle) &&
          !$this->tbColumnBodyStyle && !$this->tbRowBodyStyle) {
        $this->SetTableStyle(array(), $fill);
      }
      $this->DrawTableRow("body", $this->GetY(), $fill);
    }
  }

  /**
   * Draws all table rows.
   *
   * @param string $type Type of rows. Possible values are:
   * <ul>
   *   <li>header: Header rows.</li>
   *   <li>body: Body rows.</li>
   * </ul>
   * @param float $start_y Start Y position of table head or body.
   * @param boolean $fill Fill or not.
   * @access private
   */
  function DrawTableRow($type, $start_y, $fill) {
    switch ($type) {
      case "header":
        $tb_type_height_row = $this->tbHeaderHeightRow;
        $tb_type_height_row_max = $this->tbHeaderHeightRowMax;
        $tb_type_data = $this->tbHeaderData;
        $tb_column_type_style = $this->tbColumnHeaderStyle;
        $tb_row_type_style = $this->tbRowHeaderStyle;
        break;
      case "body":
        $tb_type_height_row = $this->tbBodyHeightRow;
        $tb_type_height_row_max = $this->tbBodyHeightRowMax;
        $tb_type_data = $this->tbBodyData;
        $tb_column_type_style = $this->tbColumnBodyStyle;
        $tb_row_type_style = $this->tbRowBodyStyle;
        break;
    }

    $dim_j = count($tb_type_data);
    $dim_i = count($tb_type_data[0]);
    $dim_k = count($tb_row_type_style);
    $n = 0;
    $border_column = array();
    $border_row = array();
    $border_cell = array();
    $do_rowspan = array();
    foreach ($tb_type_data as $j => $row) {
      if ($tb_row_type_style) {
        $k = $j % $dim_k;
      }

      // If body rows, check if the table is bigger than a page then it jumps
      // to next page and draws the header
      if ("body" == $type) {
        if ($this->CheckPageBreak($tb_type_height_row_max[$j])) {
          $this->DrawBorderBetweenColum($type, $start_y, $border_column);
          $this->DrawBorderBetweenRow($type, $start_y, $border_row);
          $this->DrawBorderCell($type, $start_y, $border_cell);
          $this->DrawTableBorder();
          $this->AddPage($this->CurOrientation);
          $this->DrawTableHeader();
          $start_y = $this->GetY();
          if (($this->tbColumnHeaderStyle || $this->tbRowHeaderStyle) &&
              !$this->tbColumnBodyStyle && !$this->tbRowBodyStyle) {
            $this->SetTableStyle(array(), $fill);
          }
          $n = 0;
          $border_column = array();
          $border_row = array();
          $border_cell = array();
          $do_rowspan = array();
          $between_block = $j < ($dim_j - 1);
        } else {
          $between_block = $j < ($dim_j - 1);
          if ($between_block) {
            $height = $tb_type_height_row_max[$j];
            if ($tb_type_height_row[$j] == $tb_type_height_row_max[$j]) {
              $height += $tb_type_height_row_max[$j + 1];
            }
            $between_block = !$this->CheckPageBreak($height);
          }
        }
      } else { // header
        $between_block = $j < ($dim_j - 1);
      }

      $border_column[$n]["height"] = $tb_type_height_row[$j];
      if ($between_block) {
        $border_row[$n]["height"] = $tb_type_height_row[$j];
      }
      $border_cell[$n]["height"] = $tb_type_height_row[$j];

      // Draw the cells of the row
      for ($i = 0; $i < $dim_i; $i++) {
        $between_cell = $i < ($dim_i - 1);
        $x = $this->GetX();
        $y = $this->GetY();
        $cell = $tb_type_data[$j][$i];

        // Do colspan or rowspan
        if ($cell) {
          $do_colspan = isset($cell["colspan"]);
          $do_rowspan[$i] = isset($cell["rowspan"]);
        } else {
          if ($between_cell && !$tb_type_data[$j][$i + 1]) {
            if (!$do_colspan) {
              $m = $i;
              if ((0 < $m) && !$tb_type_data[$j][$m - 1]) {
                for ($m--; ($m >= 0) && !$tb_type_data[$j][$m]; $m--) {
                  // VOID
                }
                $m++;
              }
              for ($nn = $j - 1; !$tb_type_data[$nn][$m]; $nn--) {
                // VOID
              }
              $do_colspan = isset($tb_type_data[$nn][$m]["colspan"]);
            }
          } else {
            $do_colspan = false;
          }

          if ($between_block && !$tb_type_data[$j + 1][$i]) {
            if (!$do_rowspan[$i]) {
              $nn = $j;
              if ((0 < $nn) && !$tb_type_data[$nn - 1][$i]) {
                for ($nn--; ($nn >= 0) && !$tb_type_data[$nn][$j]; $nn--) {
                  // VOID
                }
                $nn++;
              }
              for ($m = $i - 1; !$tb_type_data[$nn][$m]; $m--) {
                // VOID
              }
              $do_rowspan[$i] = isset($tb_type_data[$nn][$m]["rowspan"]);
            }
          } else {
            $do_rowspan[$i] = false;
          }
        }

        if ($between_cell) {
          $border_column[$n]["row"][$i] = !$do_colspan;
        }
        if ($between_block) {
          $border_row[$n]["row"][$i] = !$do_rowspan[$i];
        }
        $border_cell[$n]["row"][$i] = array();

        // Print the text
        if ($cell) {
          $style = array();
          if ($tb_row_type_style) {
            $style[] = $tb_row_type_style[$k];
          }
          if ($tb_column_type_style && $tb_column_type_style[$i]) {
            $style[] = $tb_column_type_style[$i];
          }
          $style[] = $cell;
          if ($style) {
            $this->SetTableStyle($style, $fill);
          }

          if (isset($cell["border"])) {
            $border_cell[$n]["row"][$i]["width"] = $cell["width"];
            $border_cell[$n]["row"][$i]["height"] = $cell["height"];
            $border_cell[$n]["row"][$i]["border"] = $cell["border"];
          }
          $this->WriteTag($cell["width"], $cell["height"], $cell["text"], 0,
                          $this->align, $fill, array(), $this->valign,
                          $this->Padding, $this->leading,
                          $this->leadingParagraph);
        }

        // Put the position to the right of the cell
        $this->SetXY($x + $this->tbStyle["column_width"][$i], $y);
      }

      // Go to the next row
      $this->SetXY($this->tbStartX, $y + $tb_type_height_row[$j]);
      $n++;
    }
    $this->DrawBorderBetweenColum($type, $start_y, $border_column);
    $this->DrawBorderBetweenRow($type, $start_y, $border_row);
    $this->DrawBorderCell($type, $start_y, $border_cell);
  }

  /**
   * Draws all border between columns.
   *
   * @param string $type Type of rows. Possible values are:
   * <ul>
   *   <li>header: Header rows.</li>
   *   <li>body: Body rows.</li>
   * </ul>
   * @param float $start_y Start Y position of table head or body.
   * @param array $border_column Height and draw border of cells. Array of
   * items. Each item is an array with this keys:
   * <ul>
   *   <li>height (float): Height of cell.</li>
   *   <li>row (array): Array of (num_col - 1) boolean items. Each item
   * indicates if draws right border or not.</li>
   * </ul>
   * @access private
   */
  function DrawBorderBetweenColum($type, $start_y, $border_column) {
    $dim_i = count($this->tbStyle["column_width"]) - 1;
    $dim_j = count($border_column);
    $x = $this->tbStartX;
    for ($i = 0; $i < $dim_i; $i++) {
      $x += $this->tbStyle["column_width"][$i];
      $style = ($this->tbBorderStyle["column_$type"] && is_array($this->tbBorderStyle["column_$type"][$i]))
                   ? $this->tbBorderStyle["column_$type"][$i]
                   : array();
      if ($style) {
        $draw = true;
        $height = 0;
        for ($j = 0; ($j < $dim_j) && $draw; $j++) {
          $height += $border_column[$j]["height"];
          if (!$border_column[$j]["row"][$i]) {
            $draw = false;
          }
        }
        if ($draw) {
          $this->Line($x, $start_y, $x, $start_y + $height, $style);
        } else {
          for ($j = 0, $y = $start_y; $j < $dim_j; $j++, $y = $y_next) {
            $y_next = $y + $border_column[$j]["height"];
            if ($border_column[$j]["row"][$i]) {
              $this->Line($x, $y, $x, $y_next, $style);
            }
          }
        }
      }
    }
  }

  /**
   * Draws all border between rows.
   *
   * @param string $type Type of rows. Possible values are:
   * <ul>
   *   <li>header: Header rows.</li>
   *   <li>body: Body rows.</li>
   * </ul>
   * @param float $start_y Start Y position of table head or body.
   * @param array $border_row Height and draw border of cells. Array of items.
   * Each item is an array with this keys:
   * <ul>
   *   <li>height (float): Height of cell.</li>
   *   <li>row (array): Array of (num_col - 1) boolean items. Each item
   * indicates if draws buttom border or not.</li>
   * </ul>
   * @access private
   */
  function DrawBorderBetweenRow($type, $start_y, $border_row) {
    $dim_i = count($this->tbStyle["column_width"]);
    $dim_j = count($border_row);
    $dim_k = ($this->tbBorderStyle["row_$type"]) ? count($this->tbBorderStyle["row_$type"]) : 0;
    if ($dim_k) {
      $y = $start_y;
      $k = 0;
      for ($j = 0; $j < $dim_j; $j++) {
        $y += $border_row[$j]["height"];
        $draw = true;
        for ($i = 0; ($i < $dim_i) && $draw; $i++) {
          if (!$border_row[$j]["row"][$i]) {
            $draw = false;
          }
        }
        $style = is_array($this->tbBorderStyle["row_$type"][$k])
                     ? $this->tbBorderStyle["row_$type"][$k]
                     : array();
        if ($style) {
          if ($draw) {
            $this->Line($this->tbStartX, $y,
                        $this->tbStartX + $this->tbStyle["tb_width"], $y,
                        $style);
          }
          else {
            for ($i = 0, $x = $this->tbStartX; $i < $dim_i;
                 $i++, $x = $x_next) {
              $x_next = $x + $this->tbStyle["column_width"][$i];
              if ($border_row[$j]["row"][$i]) {
                $this->Line($x, $y, $x_next, $y, $style);
              }
            }
          }
          $k++;
          $k %= $dim_k;
        }
      }
    }
  }

  /**
   * Draws all border of individual cells.
   *
   * @param string $type Type of rows. Possible values are:
   * <ul>
   *   <li>header: Header rows.</li>
   *   <li>body: Body rows.</li>
   * </ul>
   * @param float $start_y Start Y position of table head or body.
   * @param array $border_cell Height and draw border of cells. Array of items.
   * Each item is an array with this keys:
   * <ul>
   *   <li>height (float): Height of row.</li>
   *   <li>row (array): Array of (num_col - 1) items. Each item is an array
   * with this keys:
   *       <ul>
   *         <li>width (float): Width of cell.</li>
   *         <li>height (float): Height of cell.</li>
   *         <li>border (array): Style of border cell. An array like for
   * $border_style in {@link Rect Rect}.</li>
   *       </ul></li>
   * </ul>
   * @access private
   */
  function DrawBorderCell($type, $start_y, $border_cell) {
    $dim_i = count($this->tbStyle["column_width"]);
    $dim_j = count($border_cell);
    for ($j = 0, $y = $start_y; $j < $dim_j; $j++) {
      for ($i = 0, $x = $this->tbStartX; $i < $dim_i; $i++, $x = $x_next) {
        $x_next = $x + $this->tbStyle["column_width"][$i];
        if ($border_cell[$j]["row"][$i]) {
          $this->Rect($x, $y, $border_cell[$j]["row"][$i]["width"],
                      $border_cell[$j]["row"][$i]["height"], "D",
                      $border_cell[$j]["row"][$i]["border"]);
        }
      }
      $y += $border_cell[$j]["height"];
    }
  }

  /**
   * Draws border between head and body and exterior table border.
   *
   * @access private
   */
  function DrawTableBorder() {
    // Border between head and body
    if (($this->tbHeaderData) && $this->tbBorderStyle["head_body"]) {
      $style = is_array($this->tbBorderStyle["head_body"])
                  ? $this->tbBorderStyle["head_body"]
                  : array();
      $this->Line($this->tbStartX, $this->tbHeaderStartY,
                  $this->tbStartX + $this->tbStyle["tb_width"],
                  $this->tbHeaderStartY, $style);
    }

    // Exterior table border
    if ($this->tbBorderStyle["table"]) {
      $border_style = (is_array($this->tbBorderStyle["table"]))
                          ? $this->tbBorderStyle["table"]
                          : array();
      $this->Rect($this->tbStartX, $this->tbStartY, $this->tbStyle["tb_width"],
                  $this->GetY() - $this->tbStartY, "D", $border_style);
    }
  }

  /**
   * Check if jump to next page.
   *
   * @param float $height Height of last line.
   * @return boolean Indicates if jump to next page.
   * @access private
   */
  function CheckPageBreak($height) {
    return ($this->GetY() + $height) > $this->PageBreakTrigger;
  }

}

?>
