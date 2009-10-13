<?php
// Valid coding standars
class Database extends PDO
{
    public $error;

    private $_xdebug = false;

    public function __construct($database)
    {
        $this->_xdebug = extension_loaded("xdebug");
        try {
            parent::__construct(
                $database[0],
                isset($database[1]) ? $database[1] : null,
                isset($database[2]) ? $database[2] : null,
                isset($database[3]) ? $database[3] : null
            );
        } catch(Exception $e) {
            web::debug($e->getMessage());
        }

    }

    /**
    *    Return true if the table passed by param exists otherwise returns false
    *
    *    @param $table Name of the table to check.
    *     @returns true if exists the table in the selected database.
    */
    public function tableExists($table)
    {
        $rs = $this->query("show tables like '$table'");
        if(!$rs) return False;
        $value = $rs->fetch();
        if ($value) return True;
        return False;

    }

    public function createTable($table, $fields)
    {
        if($this->tableExists($table)) return;
        $primaryKeys = array();
        $sqlLines = array();

        if ($this->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
            $sql = "CREATE TABLE `$table` (";

            foreach ($fields as $name => $attrs) {

                $sqlLine = "`$name` ".$attrs["type"];
                if(array_key_exists("size", $attrs) && $attrs["size"])
                    $sqlLine .= " (".$attrs["size"].")";

                if(array_key_exists("values", $attrs) && $attrs["values"])
                    $sqlLine .= " ('".join("','", $attrs["values"])."')";

                if(array_key_exists("primary_key", $attrs))
                    $primaryKeys[]= $name;

                if(array_key_exists("not_null", $attrs) || array_key_exists("not null", $attrs))
                    $sqlLine .= " NOT NULL ";

                if(array_key_exists("autoincrement", $attrs))
                    $sqlLine .= " auto_increment ";

                if(array_key_exists("default_value", $attrs))
                    $sqlLine .= " default '".$attrs["default_value"]."' ";

                $sqlLines[]= $sqlLine;
            }

            $sql .= implode(", \n", $sqlLines).", \n";
            $sql .= "PRIMARY KEY(".implode(", ", $primaryKeys)."))
                        DEFAULT CHARSET=utf8;";
        }
        $exec = $this->exec($sql);
        return $exec;
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
                    __LINE__
                );
            else
                web::error($sql, __METHOD__, __LINE__);
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
                    __METHOD__."(".xdebug_call_function()."(".
                    xdebug_call_function()." - ".xdebug_call_line().")<br/>".
                    implode(": ", $this->errorInfo()),
                    __LINE__
                );
            else
                web::error($sql, __METHOD__, __LINE__);
           }
           return $return;
    }
}
