<?php
/**
 *  @desc Clase para manipular plantillas html.
 *  @author José Sánchez Moreno
 *  @version 1.0.1
 *  \date 27/10/05
 *  \ingroup  html
 */
class html_template extends html_object
{
    protected $__file;  // File to apply the html_template
    protected $__vars = array();
    protected $__html_template;
    protected $__root;
    protected $__blocks = array();
    protected static $__blocks_elements = array("if", "block", "while", "foreach", "switch");
    protected static $__blocks_close_elements = array("/if", "/block", "/while", "/foreach", "/switch");
    protected static $__functions = array("\$", 'link:',"inc:", "trans:", "sql:", "global:", "eval:", "filter:", "if:", "block:", "while:", "foreach:", "pages:", "switch:", "t:", 'render:', 'form:', 'tnobr:');
    protected $data, $dataLoaded = false, $dataPrepared;
    protected $inputFilter, $outputFilter;
    protected $__regFuncs;
    protected $form_items = array();
    protected $_layout;

//"html_template_filter_stripcomments"
    function __construct($file= null, $assign = null, $inputFilter = null, $ouputFilter = null, $path = null)
    {
        $this->class = get_class($this);
        $this->__regFuncs = quotemeta(join("|", html_template::$__functions));
        if(isset($path))	{ $this->setTemplatePath($path);	}
        if(isset($assign))	{ $this->assign($assign);	}
        if(isset($inputFilter)) $this->inputFilter = is_object($inputFilter) ? $inputFilter : new $inputFilter();
        if(isset($outputFilter)) $this->outputFilter = is_object($outputFilter) ? $outputFilter : new $outputFilter();
        if(isset($file)) $this->loadFile($file);
        $this->assign("GLOBALS", $GLOBALS);

    }

    public function setTemplatePath($path) { $this->__root = "$path/"; }

    function assign($var, $val = null)
    {
        if(is_array($var)) {
            $this->__vars = array_merge($this->__vars, $var);
        } else {
            $this->__vars[$var] = $val;
        }
    }

    /**
    * @return void
    * @param $block unknown
    * @param $vars unknown
    * @desc Enter description here...
 */
    public function addBlock($block, $vars)
    {
        $this->__blocks[$block][] = $vars;
    }


    public function toHtml($file = null)
    {
        $this->loadFile($file);
        $data = $this->execute($this->dataPrepared);

        if ($this->_layout) {
            $this->_layout->content = $data;
            return $this->_layout->toHtml();
        }

        return $data;
    }

    public function display($file = null)
    {
        echo $this->toHtml($file);
    }

    protected function loadFile($file = null, $string = false)
    {
        if (isset($file) && $file != $this->__file) {
            $this->__file = $file;
            if(!file_exists($file)) web::warning("No existe el archivo $file", __FILE__, __LINE__);
            $fileData = file_get_contents($file);

            if ($this->_layout) {
                $templateData = file_get_contents($this->_layout);

                // Obtenemos los espacios.
                preg_match('/\n(.*?){\$content}/m', $templateData, $matches);
                $spaces = preg_replace('/([^\s])/', ' ', $matches[1]);
                $fileData = preg_replace('/(\n)/m', "\n$spaces", $fileData);
                $fileData = str_replace('{$content}', $spaces.$fileData, $templateData);
            }

            $this->loadData($fileData);
        }
    }

    public function loadData($data) {
            $this->data = $data;
            if(isset($this->inputFilter)) $this->data = $this->inputFilter->apply($this->data);
            $this->dataLoaded = true;
            $this->dataPrepared = $this->prepare($this->data);
    }

    /** Convierte el texto en array y lo pasa a __parseArr
    */
    private function __parse($html_template)
    {
        do
        {
            $temp_template = $html_template;
            $html_template =  $this->__parseArr($this->__breakStr($html_template));
        } while ($html_template != $temp_template);	// para que sea recursivo
        return $html_template;
    }

    /** Convierte el texto en array
    * @access private
    */
    private function __breakStr($html_template)
    {
        return preg_split('/({)|(})/s', $html_template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }


    /** Procesa un array
    * @access private
    */
    private function __getNextItem($arr, $option = null)
    {
        $pre_item = null;
        $processed = false;
        $openers = 0;
        $curr_item = array();
        $blocks = array();
        while(count($arr) > 0 && !$processed)	// Hasta que se llegue al final.
        {
            $item  = array_shift($arr);
            switch($item)
            {
                case "{":
                    $openers++;
                    $curr_item[] = $item;

                    // Si es un bloque a�adimos al bloque
                    list($command) = explode(":", $arr[0]);
                    if($this->isBlockCommand($command))  $blocks[] = $command;
                    break;

                case "}":
                    $openers--;
                    $curr_item[] = $item;
                    if($openers < 0)	{ return array($pre_item, null, null); //  $this->error("Unclosed Braceé");
                     }
                    if($openers == 0)	// Se ha cerrado la llave
                    {
                        if(count($blocks) == 0)		// Si no estamos dentro de ning�n bloque se ha completado el item.
                        {
                            return array($pre_item, $curr_item, $arr);
                        } else {
                            $command =  $curr_item[count($curr_item) - 2];
                            if($this->isBlockCommand($command, true) || substr($command, 0, 7) == "/block:")	// Chapuza
                            {
                                array_shift($blocks);
                                if(count($blocks) == 0)	// Se ha cerrado el bloque y ya no estamos dentro de ninguno.
                                    return array($pre_item, $curr_item, $arr);
                            }
                        }
                    }
                    break;

                default:
                    if($openers > 0 || count($blocks) > 0)
                    {
                        $curr_item[] = $item;
                    } else {
                        $pre_item = $item;
                    }
                    break;
            }
        }
        return array($pre_item, null, null);
    }


    private function parse($data)
    {
        return $this->execute($this->prepare($data));
    }


    protected function prepare($data)
    {
        if (!is_array($data)) {
            $arr = preg_split('/({)|(})/s', $data, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        } else {
            $arr = $data;
        }
//		var_dump($arr);
        $items = array();
        while (count($arr)) {
            list($pre, $item, $arr) = $this->__getNextItem($arr);
/*
            echo "<hr>PRE";
            var_dump($pre);
            echo "<BR>ITEM:";
            var_dump($item);
            echo "<BR>ARR:";
            var_dump($arr);
*/
            $items[] = $pre;
            $items[] = $item;
        }
        return $items;
    }

    protected function execute($data)
    {
        $txt = "";
        $preData = "";
        $strData = array();
        foreach ($data as $item) {
            if (is_array($item)) {
                $len = 0;
                if (substr($item[1], 0, 6) == "render") {
                    $predata = $strData[count($strData) - 1];
                    $linesPredata = explode("\n", $predata);
                    $len = strlen(array_pop($linesPredata));
                }
                $strData[]= $this->evalueBlock($item, null, $len);
            } else {
                $strData[]= $item;
            }
        }
        return implode("", $strData);
    }

    private function getFunction($block)
    {
        $arr = array();
        preg_match("/^($this->__regFuncs)([^}]*)/is", $block[1], $arr);
        return array_slice($arr, 1);
    }

    /** @access private */
    private function evalueBlock($block, $vars = null, $spaces = 0)
    {
        global $config;
        if(isset($vars)) { $vars = &$vars;} else { $vars = &$this->__vars; }
        $values = $this->getFunction($block);
        $function = array_shift($values);
        $expresion = array_shift($values);

        // Si no hay nada que procesar, solo son unas llaves pero no tienen funciones.
        if(!$function && $block[2] != "{" ) return join("", is_array($block) ? $block : array($block));

        //@TODO Optimizar esta parte
        if($function && $block[2] == "{")		// Hay una llave dentro de la actual y es una funcion v�lida.
        {
            $arr = $block;
            array_splice($arr, 0, 1);	// Le quitamos el {  del principio
            list($pre, $item, $arr) = $this->__getNextItem($arr);
            while($item)
            {
                $result = $this->evalueBlock($item);
                $pre = is_array($pre) ? $pre : array($pre);
                $arr = array_merge(array("{"), $pre, array($result), $arr);
                $arr = $this->__breakStr(join("", $arr));
                list($pre, $item, $arr) = $this->__getNextItem($arr);

            }
            return $result;

        }
        // Aqui no hace falta parsear lo de fuera solo lo de centro, no es una funcion valida
        // @TODO Error!!!!
        elseif(!$function && $block[2] == "{")
        {
            return implode("", array_slice($block, 0, 2)).$this->parse(array_slice($block, 2, -1))."}";
        }

        // Vemos si es un bloque o no
        list($command) = explode(":", $function);
        if($this->isBlockCommand($command))
        {
             $blockContent = array_slice($block, 3, count($block) - 6);
         } else {

            $blockContent = array_slice($block, 3);
         }

        switch ($function)
        {
            case "$":
                // Hay que ver si es un objeto y lo parseamos.
                return $this->__getVar($expresion, $vars);

            case "global:":
                    $obj= split("->", $expresion, 2);
                    if(count($obj) > 1)
                    {
                        return eval("return \$GLOBALS['$obj[0]']->$obj[1];");
                    } else {
                        return eval("return \$GLOBALS['$expresion'];");
                    }

            case "inc:":
//				$t = new html_template();
//				$t->__vars = &$vars;
//				$t->__blocks =  $this->__blocks;

                $items = explode(":", $expresion);
                $file = $items[0];
                if(isset($items[1])) $params = $items[1];


                $t = new html_template($file, null, null, null, $this->__root);
                $t->__vars = &$vars;
//				$t->__vars["params"] = array_filter(split("[ ]?,[ ]?", $params, create_function('$a', "return \$a != '';")));
                if(isset($params)) $t->__vars["params"] = split("[ ]?,[ ]?", $params);
                $t->__blocks =  $this->__blocks;
                return $t->toHtml();


            case "tnobr:":
                $nobr = true;
            case "t:":
            case "trans:":
                if(isset($_REQUEST["inplace"]) &&  $_REQUEST["inplace"] == "edit" && $function == "t:") {
                    $translated = __($expresion);
                    $size = strlen($translated) > 50 ? 50 :  strlen($translated);
                    $t = new html_template_text(js_once("prototype").js_once("scriptaculous/scriptaculous")."<span id=\"$expresion\">$translated</span><script>new Ajax.InPlaceEditor('$expresion', '/admin/index.php?mode=ajax&function=inplaceeditor&class=modules_translate&id=$expresion', {'savingText' : 'Guardando', 'clickToEditText' : 'Pulse para editar', 'cancelText' : 'Cancelar', 'cols' : '$size'});</script>", null, null, null, $this->__root);
                } else {
                    $t = new html_template_text(l10n::instance()->get($expresion), null, null, null, $this->__root);
                }

                $t->__vars = &$vars;
                $t->__blocks =  $this->__blocks;
                return $nobr ? $t->toHtml() : nl2br($t->toHtml());


            case "eval:":
                return $this->__evalVar($expresion, $vars);

            case "exec:":	// Por implementar

                break;

            case "while:":
                $t = new html_template();
                $t->__vars = &$vars;
                $t->__blocks = & $this->__blocks;

                $str = "";
                while($this->__condition($expresion, $vars))
                {
                    $str.= $t->parse($blockContent);
                }
                return $str;

            case "if:":	// Tenemos que tratar los else.
                $t = new html_template(null, null, null, null, $this->__root);
                $t->__vars = &$vars;
                $t->__blocks = &$this->__blocks;
                list($if, $else) = $this->__searchElse($blockContent);

                if($this->__condition($expresion, $vars))
                {	// Si tiene else o no
                     return $t->parse($if);
                } else {
                    return $else  ? $t->parse($else) : "";
                }

            case "sql:":
                list($var, $sql) = explode(":", $expresion, 2);
                $var = preg_replace("/\\\$([^_]\w*)/", "\\\$vars['\\1']", $var); // Si $var tiene $ lo quitamos
                eval("$var = \$GLOBALS['config']->db->sql(\"$sql\");");
                return "";

            case "pages:":
                #list($var, $sql, $pageSize) = explode(":", $expresion);
                $arr  = explode(":", $expresion);
                $var = array_shift($arr);
                $pageSize = array_pop($arr);
                $sql = join(":", $arr);
                $var = preg_replace("/\\\$([^_]\w*)/", "\\\$vars['\\1']", $var); // Si $var tiene $ lo quitamos
                eval("$var = new html_query(\$GLOBALS['config']->db->sql(\"$sql\"));");
                eval("${var}->setPageSize($pageSize);");
                return "";

            case "filter:":
                $filters = str_replace("&&#$#&&", "::", explode(":", str_replace("::", "&&#$#&&", $expresion)));
                $var = array_pop($filters);
                if(substr($var, 0, 1) == "$") $var = $this->parse('{'.$var.'}');
                return $this->__applyFilters($filters, $var);

            case "foreach:":
                $t = new html_template();
                $t->__vars = &$vars;
                $t->__blocks = &$this->__blocks;
                list($array, $item) = explode(" as ", $expresion);
                $vals = explode(" => ", $item);
//				$array = preg_replace("/^\\$/", "", $array);
                $key = preg_replace("/^\\$/", "", $vals[0]);
                if(isset($vals[1])) $val = preg_replace("/^\\$/", "", $vals[1]);
                $txt = "";
                $str = "";
                if(isset($val))	// Si es asociativo
                {
                    foreach($this->__evalVar("return $array", $vars) as $k => $v)
                    {
                        $vars[$key] = $k; $vars[$val] = $v;
                        $str .= $t->parse($blockContent);
                    }
                } else {
                    foreach($this->__evalVar("return $array", $vars) as $v)
                    {
                        $vars[$key] = $v;
                        $str.= $t->parse($blockContent);
                    }
                }
                return $str;

            case "switch:":
                $t = new html_template(null, null, null, null, $this->__root);  $t->__vars = &$vars; $t->__blocks = &$this->__blocks;
                list($cases, $default) = $this->search_switch_cases($blockContent);
                foreach($cases as $case => $block) {
                    if($this->__evalVar("return ".str_replace("case:", $expresion."==", $case), $vars)) {
                        return $t->parse($block);
                    }
                }
                if($default) { return $t->parse($default); }
                return;
            case "block:":
                $block = $expresion;
                $t = new html_template();
                $str = "";

                if(isset($this->__blocks[$block]))
                {
                    foreach($this->__blocks[$block] as $item)	{
                        $t->__vars = $item;
                        $t->__blocks = $this->__blocks;
                        $str.= $t->parse($blockContent);
                    }
                }
                return $str;

            case 'render:':
                $web = clone web::instance();
//				$t = new html_template($file, null, null, null, $this->__root);

                $t = new view_renderer_template();
                $t  ->setDirectory(web::instance()->getViewsDirectory())
                    ->setData($vars);

                $txt = $web->run($expresion, $t);

                if ($spaces) {
                    $tabSpaces = str_repeat("\t", $spaces);
                    $txt = preg_replace("/\n/", "\n".$tabSpaces, $txt);
                }

                return $txt;
                break;

            case 'link:':
                if(l10n::instance()->isNotDefault()) return "/".l10n::instance()->getSelectedLang().$expresion;
                return $expresion;
                break;

            case 'form:':
                $this->form_items[] = $expresion;
                return "<div id='$expresion'></div>";

            default:
                return join("", $block);
        }
    }

    public function getFormItems() {
        return $this->form_items;
    }
    /** Busca las partes else e if en los if anidados.
    * @access private
    */
    private function __searchElse($block)
    {
        $openers = 0;
        $pos = -1;
        while($pos = array_ereg("/^(if:*|else|\/if)/", $block, $pos + 1))
        {
            $var = $block[$pos-1].$block[$pos].$block[$pos+1];
            switch($var)
            {
                case "{else}":
                    if($openers == 0) return array(array_slice($block, 0, $pos - 1), array_slice($block, $pos + 2));
                    break;
                case "{/if}":
                    $openers--;
                    break;
                default: // if:
                    if(substr($var, 0, 4) == "{if:") $openers++;
                    break;
            }
        }
        return array($block, null);
    }

    private function search_switch_cases($block)
    {
        $openers = 0;
        $pos = -1;
        $cases = array();
        $result = array();
        while($pos = array_ereg("/^(case:*|default:)/", $block, $pos + 1))
        {
            $var = $block[$pos-1].$block[$pos].$block[$pos+1];

            switch($var)
            {
                case "{default:}":
                    $cases[] = array("default" => $pos);
                    break;
                default: // case:
                    $cases[] = array($block[$pos] => $pos);
                    break;
            }
        }

        for($i = 0 ; $i < count($cases); $i++) {
            $case = key($cases[$i]);
            $from = $cases[$i][$case] + 2;

            if($i < count($cases) - 1) { $size = $cases[$i + 1][key($cases[$i + 1])] - $from - 1; }
            else { $size = count($block) - $from; }
            if($case == "default") { $default = array_slice($block, $from, $size);  }
            else { $result[$case] = array_slice($block, $from, $size); }

        }
        return array($result, $default);
    }

    private function __condition($var, &$vars)
    {
        return $this->__evalVar("return $var", $vars);
    }


    private function __evalVar($var, &$vars)		// Evalua variable o constante para while o if
    {
        $txt = preg_replace("/\\\$([^_]\w*)/", "\\\$vars['\\1']", $var);
        if ($this->_checkCode("$txt;"))
            return eval("$txt;");
    }


    private function __getVar($var, $vars)
    {
        $vars_var = preg_replace("/^\\\$([^_]\w*)/", "\\\$vars['\\1']", "\$".$var);
        $obj = explode("->", $vars_var, 2);
        if($obj[0] == '$vars[\'this\']') {
            $method = $obj[1];
            return $this->$method;
        }

        if(!is_object(eval("return $obj[0];")) && count($obj) > 1) {
            return eval("return \$$var;");
        }

//        echo "return $vars_var;";
        $vars_var  = eval("return $vars_var;");
        // Si no devolvemos el valor de la variable y si es un objeto con la propiedad toHtml .. la llamamos.

        return is_object($vars_var) && method_exists($vars_var, "toHtml") ? $vars_var->toHtml() : $vars_var;
    }

    private function __errorEvalHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        echo "ERROR : $errno, $errstr, $errfile, $errline, $errcontext<br>";
//		restore_error_handler();
//		set_error_handler($self->old_error_handler);
    }

    /** @access private
    * @todo Crear infraestructura de filtros (de entrada y de salida)
    */
    private function __applyFilters($filters, $txt)
    {
        $regs = array();
        foreach(array_reverse($filters) as $filter)
        {
            if($filter == "null") $txt = "";
            else {
                preg_match("/([^\(]*)(\(([^\(]*)\))?/", $filter, $regs);
                $filter = $regs[1];
                $params = array();
                if(isset($regs[3])) $params = array_filter(preg_split("/\s*,\s*/", $regs[3]), create_function('$a', "return \$a != '';"));
                array_unshift($params, $txt);

                if (is_callable(array('html_template_filters', $filter))) {
                    $txt = call_user_func_array(array(html_template_filters, $filter), $params);
                } else {
                    if(strpos($filter, "::")) $filter = explode("::", $filter);

                    $txt = call_user_func_array($filter, $params);
                }
            }
        }
        return $txt;
    }

    /** @access private */
    private function isBlockCommand($command, $closed = false)
    {
        if(!$closed) {
            return in_array($command, html_template::$__blocks_elements);
        } else {
            return in_array($command, html_template::$__blocks_close_elements);
        }
    }

    public function __set($item, $value) {
        if(isset($this->$item))
            $this->$item = $value;
        else
            $this->assign($item, $value);
    }

    public function __get($item) {
        if(isset($this->$item))
            return $this->$item;
        else
            return $this->__vars[$item];
    }

    public function addJs($file) {
        $this->__vars["js_files"] .= js($file);
        return $this;
    }

    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }
    public function getLayout()
    {
        return $this->_layout;
    }

    public function cloneTemplate($template)
    {
        $this->__vars = &$template->__vars;
        $this->__blocks =  $template->__blocks;
    }

    private function _checkCode($code) {
        if (@eval('return true;' . $code) === true) return true;
        $error = error_get_last();
        web::error("<font color=red>ERROR Al evaluar: $code</font> -- ".$error["message"]." --> <br/>".$this->__file);
        return false;
    }

    private function _eval($code) {
        if (@eval('return true;' . $code) === true) {
            return eval($code);
        } else {
            $error = error_get_last();
            web::error("ERROR Al evaluar: $code -- <br>$error");
        }
        return null;
    }

    public function setVars($vars)
    {
        $this->__vars = $vars;
        return $this;
    }

    public function setData($vars)
    {
        $this->__vars = $vars;
        return $this;
    }
}


class html_template_text extends html_template {

    public function __construct($data = null, $assign = null, $inputFilter = "html_template_filter_stripcomments", $outputFilter = null, $path = null)  {
        parent::__construct(null, $assign, $inputFilter, $outputFilter, $path);

        if(isset($data)) $this->loadData($data);
    }
}


function parse_result($txt)
{
    return strtr(strip_tags($txt), "{}", "()");
}

function array_ereg($pattern, $haystack, $from = 0)
{
    for($i = $from; $i < count($haystack); $i++)
    {
            if (preg_match($pattern, $haystack[$i])) return $i;
    }
    return false;
}
?>
