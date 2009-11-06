<?php

class net_sms
{
    public $errorCode;
    public $errorMessage;

    private $_numbers = array();
    private $_text;
    private $_provider = "bulksms";
    private $_url = "http://bulksms.com.es:5567";
    protected $_params = array
    (
        'username'        => '',
        'password'        => '',
        'routing_group'   => '2',
        'sender'          => "O2W",
        "source_id"       => "O2W",
    );

    public function addNumber($number)
    {
        $this->_numbers[] = $number;
    }

    public function send($txt)
    {
        $this->_text = $txt;
        $value =  $this->makeRequestToProvider();
        $this->errorCode    = $value[0];
        $this->errorMessage = $value[1];
        return ($value[0] == 0);
    }

    private function makeRequestToProvider()
    {
        $url = "/eapi/submission/send_sms/2/2.0";
        $items = $this->_params;

        $items["message"] = urlencode($this->eliminarTildes($this->_text));
        $items["msisdn"] = implode(",", $this->_numbers);

        $value = explode("|", $this->_post($this->_url.$url, $items));
        return $value;

    }

    private function _post($url, $post)
    {
        $context = array();

        if (is_array($post)) {
            ksort($post);

            $context['http'] = array(
                'method' => 'POST',
                'content' => http_build_query($post, '', '&'),
            );
        }

        return file_get_contents($url, false, stream_context_create($context));
    }

    private function eliminarTildes($s)
    {
        $s = ereg_replace("[����]", "a", $s);
        $s = ereg_replace("[����]", "A", $s);
        $s = ereg_replace("[���]", "I", $s);
        $s = ereg_replace("[���]", "i", $s);
        $s = ereg_replace("[���]", "e", $s);
        $s = ereg_replace("[���]", "E", $s);
        $s = ereg_replace("[�����]", "o", $s);
        $s = ereg_replace("[����]","O", $s);
        $s = ereg_replace("[���]","u", $s);
        $s = ereg_replace("[���]","U", $s);
        $s = str_replace("�", "c", $s);
        $s = str_replace("�", "C", $s);
        $s = str_replace("[�]", "n", $s);
        $s = str_replace("[�]", "N", $s);
        return $s;
    }


    public function __call($method, $args)
    {
        if (empty($args))
            return $this->_params[$method];
        else
            $this->_params[$method] = $args[0];
        return $this;
    }


}
