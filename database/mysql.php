<?php

class database_mysql extends database
{
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
        if($this->tableExists($table)) return false;
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

            $sql .= implode(", \n", $sqlLines)."\n";
            if ($primaryKeys) $sql .= ", PRIMARY KEY(".implode(", ", $primaryKeys).")";
            $sql .= ") DEFAULT CHARSET=utf8;";
        }
        $exec = $this->exec($sql);
        return $exec !== False;
    }

}
