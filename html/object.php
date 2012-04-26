<?
/**
 * @desc Objeto html base.
 *
 * @Author José Sánchez Moreno
 * \ingroup html
 * @version 0.9
 * @date 1-1-03
 */

class html_object
{

    protected $attrs = array();
    protected $data;

    /**
    *	Construcctor del objeto.
    *	@param string $data los datos que se van a agregar al objeto html
    */
    public function __construct($data = "")
    {
        $this->setData($data);
    }


    /**
     * Pone atributos al objeto
     * @return void
     */
    function setAttrib($attr)
    {
        $arr = $this->__parseAttrib($attr);
        foreach($arr as $item => $value)
        {
            $this->__atributos[$item] = $value;
        }
    }


    function getAttrib($attr = null)
    {
        $attr = isset($attr) ? $attr : $this->__atributos;
        if (!is_array($attr)) return "";
        $strAttr = '';

        foreach($attr  as $item => $value)
            $strAttr .= isset($value) ? strtoupper($item)."=\"$value\" " : "$item ";

        return $strAttr;
    }


    protected function getAttributes($except = array())
    {
        $strAttr ="";
        if (!is_array($this->attrs)) return "";
        if(!array_key_exists("id", $this->attrs)) $this->attrs['id'] =  $this->attrs['name'];
        if(!is_array($except)) $except = preg_split("/\s*,\s*/", $except);
        foreach($this->attrs  as $item => $value) {
            if(!in_array($item, $except))
                $strAttr .= isset($value) ? strtolower($item)."=\"$value\" " : "$item ";
        }
        return $strAttr;
    }


    function setData($data)
    {
        $this->data = "";
        $this->add($data);
    }


    /**
    * @desc Añade objetos o cadenas al objeto.
     * @return void
     * @param string $data objeto o cadena
     */
    function add($data)
    {
        $args = func_get_args();
        foreach($args as $data)
        {
            $this->data .= $data;
        }
    }


    function toHtml()
    {
        return $this->data;
    }


    /** Muestra el objeto en pantalla */
    function display()
    {
        echo $this->toHtml();
    }

    private function __parseAttrib($attr)
    {
        $regs = array();
        if(is_array($attr)) return $attr;
        $arr = array();
        if(preg_match_all("{(['][^']*[']|[\"][^\"]*[\"]|[^,=\s]*)\s*(=\s*(['][^']*[']|[\"][^\"]*[\"]|[^,=\s]*))?}i", $attr, $regs))
        {
            for($i = 0; $i < count($regs[1]); $i++)
            {
                $value = preg_replace("{^(['\"])?(.*?)(['\"])?$}", "\\2", $regs[3][$i]);
                $value = substr($regs[2][$i], 0, 1) == "=" ? $value : null;
                $arr[trim($regs[1][$i])] = $value;
            }
        }
        return $arr;
    }

    function __toString()
    {
        return $this->toHtml();
    }

}
?>
