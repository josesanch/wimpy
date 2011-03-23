<?php
// Valid coding standars
class Database extends PDO
{
    public $error;

    private $_xdebug = false;
    public $uri;

    public function __construct($database)
    {
        $this->_xdebug = extension_loaded("xdebug");
        $this->uri = $database[0];
        try {
            parent::__construct(
                $database[0],
                isset($database[1]) ? $database[1] : null,
                isset($database[2]) ? $database[2] : null,
                isset($database[3]) ? $database[3] : null
            );
        } catch(Exception $e) {
            echo "<h4 style='color: red'>Error conectar con $database[0]</h4>";
            exit;
        }

    }


    public function exec($sql)
    {
        if($this->_xdebug)
            web::debug(
                $sql,
                __METHOD__."(".xdebug_call_function()."(".
                xdebug_call_function()." - ".xdebug_call_line().")",
                __LINE__
            );
        else
            web::debug($sql, __METHOD__, __LINE__);

        $args =  func_get_args();
           $return =  call_user_func_array(array('pdo', 'exec'), $args);

           if ($this->errorCode() != 0) {
            if ($this->_xdebug)
                web::error(
                    $sql,
                    __METHOD__."(".xdebug_call_function()."(".
                    xdebug_call_function()." - ".xdebug_call_line().
                    ")<br/>".implode(": ", $this->errorInfo()),
                    __LINE__,
                    web::NOTIFY_BY_EMAIL
                );
            else
                web::error($sql, __METHOD__, __LINE__, web::NOTIFY_BY_EMAIL);
           }
           return $return;

    }


    public function query($sql)
    {
        if($this->_xdebug)
            web::debug(
                $sql,
                __METHOD__."(".xdebug_call_function()."(".
                xdebug_call_function()." - ".xdebug_call_line().")",
                __LINE__
            );
        else
            web::debug($sql, __METHOD__, __LINE__);

        $args =  func_get_args();
        $return =  call_user_func_array(array('pdo', 'query'), $args);

        if ($this->errorCode() != 0) {
            if ($this->_xdebug)
                web::error(
                    $sql,
                    __METHOD__.": ".
                    xdebug_call_function()."(".xdebug_call_line().")<br/>".
                    implode(": ", $this->errorInfo()),
                    __LINE__,
                    web::NOTIFY_BY_EMAIL
                );
            else
                web::error($sql, __METHOD__, __LINE__, web::NOTIFY_BY_EMAIL);
           }
           return $return;
    }
}
