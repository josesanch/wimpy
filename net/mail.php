<?
/** \ingroup net */
/**
*  net::mail Clase
* @author   José Sánchez Moreno
* @version  v 0.0.3
* @package net
* @access   public
*/

class Net_Mail
{
    private $_mail;
    public function __construct($backend = "mail")
    {
        $mailComponent = "Net_Mail_".$backend;
        $this->_mail = new $mailComponent;
    }

    public function __get($item)
    {
        return $this->_mail->$item;
    }

    public function __set($item, $value)
    {
        return $this->_mail->$item = $value;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_mail, $method), $args);
    }
}

class Net_Mail_mail
{
    private $__headers = array();
    private $__cc = array();
    private $__bcc = array(); //añadido por daniel 17/12/12
    private $__to,  $__from, $msg;
    private $__parts = array();
    private $__boundary;
    private $__html;
    private $__text;
    private $xmailer;
    private $_additionalHeaders = array();
    private $_from;
    private $_to;

    public function __construct()
    {
        $this->xmailer = "Oxigenow eSolutions Net Library 1.1 - ".$_SERVER["HTTP_HOST"]."\n";
        $this->__boundary = rand();
    }

    public function setFrom($address, $name)
    {
        $this->from($name, $address);
    }

    public function setTo($address, $name)
    {
        $this->to($name, $address);
    }


    public function to($to = null, $email = null)	{
        if($to) {
            $this->__to = array($to, $email);
        } else {
            return $this->__to[1] ? '=?UTF-8?B?'.base64_encode($this->__to[0])."?= <".$this->__to[1].">" : $this->__to[0];
        }
    }


    public function from($from = null, $email = null)	{
        if($from) {
            $this->__from = array($from, $email);
        } else {
            return $this->__from[1] ? '=?UTF-8?B?'.base64_encode($this->__from[0])."?= <".$this->__from[1].">" : $this->__from[0];
        }
    }

    public function subject($subject = null)
    {
        if($subject) {
            $this->__subject = $subject;
        } else {
            return '=?UTF-8?B?'.base64_encode($this->__subject)."?=";
        }
    }

    public function message($subject, $msg)	{  $this->subject($subject); $this->msg($msg);	}
    public function addCC($to, $email) { $this->__cc[] = "$to <$email>"; }
    public function addBCC($to,$email) { $this->__bcc[] = "$to <$email>";}//añadido daniel 17/12/12

    /**
     * @desc Envia un correo electrónico
     * @param from Quién envia el correo.
     * @param to Dirección a quien envia el correo
     * @param subject Asunto del mensaje
     * @param msg Contenido del mensaje
     * @return bool
     */

    //public function send($from = null, $to = null,$bcc = null, $subject = null, $msg = null)
    public function send($from = null, $to = null, $subject = null, $msg = null)
    {
        if($from) $this->from($from);
        if($to) $this->to($to);
        //        if($bcc) $this->bcc($bcc);//18/12/12
        if($subject) $this->subject($subject);
        if(isset($msg)) $this->msg($msg);

        $content = $this->getContent();
        return mail($this->to(), $this->subject(), $content, $this->__getHeaders());
    }

    function msg($msg)
    {
        $this->__msg = $msg;
        $this->addHtml($this->__msg);
        $this->addText($this->__parseHtml($this->__msg));
    }

    function addAttachment($file, $filename = null)
    {
        if(!$filename) $filename = basename($file);
        $contenttype = "application/octet-stream";
        $content = base64_encode(fread(fopen($file,"r"),filesize($file)));
        $headers = "Content-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"$filename\"\nContent-Type: $contenttype; name=$filename\n\n";
        $this->__addPart($headers, $content);
    }

    function addHtml($text)
    {
        $this->__html = str_replace("=", "=3D", $text);
        //$this->__html = $text;
    }

    function addText($text)
    {
        $this->__text = $text;
    }


    function getContent()
    {
        // Si hay html y texto a la vez, hay que ponerlo dentro de otro multipart/alternative
        if($this->__html != "" && $this->__text)
        {
            $boundary = rand();
            $parts = array();
            if($this->__text != "") $parts[] = $this->__genPart("Content-Transfer-Encoding: quoted-printable\nContent-Type: text/plain; CHARSET=UTF-8\n\n", $this->__text, $boundary);
            if($this->__html != "") $parts[] = $this->__genPart("Content-Transfer-Encoding: quoted-printable\nContent-Type: text/html; CHARSET=UTF-8\n\n", $this->__html, $boundary);

            $this->__addPart("Content-Type: multipart/alternative; boundary=\"$boundary\"\n\n", $this->join_parts($parts, $boundary));
        } else {
            if($this->__html != "") $this->__addPart("Content-Transfer-Encoding: quoted-printable\nContent-Type: text/html; CHARSET=UTF-8\n\n", $this->__html);
            if($this->__text != "") $this->__addPart("Content-Transfer-Encoding: quoted-printable\nContent-Type: text/plain; CHARSET=UTF-8\n\n", $this->__text);
        }

        return $this->join_parts($this->__parts, $this->__boundary);
    }


    private function join_parts($parts, $boundary)
    {
        return join("\n\n", $parts)."--".$boundary."--\n";
    }

    private function __genPart($headers, $content, $boundary = null)
    {
        $boundary = isset($boundary) ? $boundary : $this->__boundary;
        $str =  "--".$boundary."\n";
        $str .= $headers;
        $str .= $content."\n";
        return $str;
    }

    private function __addPart($headers, $content, $boundary = null)
    {
        $this->__parts[] = $this->__genPart($headers, $content, $boundary = null);

    }

    private function __getHeaders()
    {
        $headers = "MIME-Version: 1.0\n";
//		Content-Type: multipart/mixed; boundary="46138324"
//		$headers .= "Content-Type: multipart/mixed; boundary=\"$this->__boundary\"\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$this->__boundary\"\n";
//		$headers .="Content-Type: text/html; \"charset=UTF-8\"\r\n";
        $headers .="From: ".$this->from()."\n";
        $headers .="Reply-To: ".$this->from()."\n";


        if(count($this->__cc) > 0)
        {
            $headers .= "CC: ".join($this->__cc, ",");
        }
        //daniel 14/12/12 revisar esto.
        if(count($this->__bcc) > 0){
            $headers .= "BCC: ".join($this->__bcc, ",");
        }

        $headers .= "X-Mailer: ".$this->xmailer;
        foreach ($this->_additionalHeaders as $item => $value) {
            $headers .= "$item: $value\n";
        }

        return $headers;
    }

    private function __parseHtml($text)
    {
        return  strip_tags($text);
    }

    public function setXMailer($xmailer) {
        $this->xmailer = $xmailer;
    }

    public function addHeader($item, $value)
    {
        $this->_additionalHeaders[$item] = $value;
    }

}

class Net_Mail_phpmailer
{
    private $_mail;
    public function __construct()
    {
        include_once("PHPMailer/class.phpmailer.php");
        $this->_mail = new PHPMailer(true);
        $this->_mail->CharSet = "utf-8";
    }

    public function subject($subject = null)
    {
        $this->_mail->Subject = $subject;
    }
   /* //18/12/12
	public function bcc($bcc)
	{
		  $this->_mail->bcc = $bcc;
	}
	*/
    public function addHtml($text)
    {
        $this->_mail->MsgHTML($text);
    }

    public function addText($text)
    {
        $this->_mail->AltBody = $text;
    }

    public function addAttachment($file, $name= "")
    {
        $this->_mail->AddAttachment($file, $name);
    }

    public function to($name, $address)
    {
        $this->_mail->addAddress($address, $name);
    }
    public function setTo($address, $name)
    {
        $this->to($name, $address);
    }
    public function from($name, $address)
    {
        $this->_mail->FromName = $name;
        $this->_mail->From = $address;
    }
    public function setFrom($address, $name)
    {
        $this->from($name, $address);
    }

    public function message($subject, $msg)	{  $this->subject($subject); $this->msg($msg);	}
    public function msg($msg)
    {
        $this->addHtml($msg);
        $this->addText($this->__parseHtml($msg));
    }

    public function send($from = null, $to = null, $subject = null, $msg = null)
    //public function send($from = null, $to = null, $bcc = null, $subject = null, $msg = null) //18-12-12
    {
        if($from) $this->from($from);
        if($to) $this->to($to);
        //if($bcc) $this->bcc($bcc);//18/12/12
        if($subject) $this->subject($subject);
        if($msg) $this->msg($msg);

        return $this->_mail->send();
    }

    public function addHeader($item, $value)
    {
        $this->_mail->addCustomHeader("$item: $value");
    }

    public function __get($item)
    {
        return $this->_mail->$item;
    }

    public function __set($item, $value)
    {
        return $this->_mail->$item = $value;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_mail, $method), $args);
    }
}