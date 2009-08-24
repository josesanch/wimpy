<?php

class captcha {

	public $charset = 'ABCDEFGHKLMNPRSTUVWYZ23456789';
	private $code, $image;

	public function __construct($len = 5) {
		$this->code = $this->generateCode($len);
	}


	public function display() {
		$this->drawCaptcha();
		header("Expires: Sun, 1 Jan 2000 12:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
        header("Content-Type: image/png");

		echo $this->image;


	}

	public function check($string) {

	}

	private function generateCode($len) {
	    $code = '';
	    for($i = 1, $cslen = strlen($this->charset); $i <= $len; ++$i)
	    	$code .= strtoupper( $this->charset{rand(0, $cslen - 1)} );

    	return $code;

	}

	private function drawCaptcha() {
		/* Create new object */
		$im = new Imagick();

		/* Create new checkerboard pattern */
		$im->newPseudoImage(100, 100, "pattern:checkerboard");

		/* Set the image format to png */
		$im->setImageFormat('png');

		/* Fill new visible areas with transparent */
		$im->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
		$im->setImageMatte(true);

		$draw = new ImagickDraw();

		$pixel = new ImagickPixel( 'black' );
		/* Black text */
		$pixel->setColor('black');

		/* Font properties */
		$draw->setFont('Bookman-DemiItalic');
		$draw->setFontSize( 10 );

		/* Create text */
		$im->annotateImage($draw, 10, 45, 0, $this->code);


		/* Control points for the distortion */
		$controlPoints = array( 10, 10,
				                10, 5,

				                10, $im->getImageHeight() - 20,
				                10, $im->getImageHeight() - 5,

				                $im->getImageWidth() - 10, 10,
				                $im->getImageWidth() - 10, 20,

				                $im->getImageWidth() - 10, $im->getImageHeight() - 10,
				                $im->getImageWidth() - 10, $im->getImageHeight() - 30);

		/* Perform the distortion */
		$im->distortImage(Imagick::DISTORTION_PERSPECTIVE, $controlPoints, true);

		$this->image = $im;
	}


}


?>
