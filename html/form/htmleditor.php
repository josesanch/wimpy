<?
class html_form_htmleditor extends html_form_input {

    protected $attrs = array
    (
        'type'    => 'textarea',
        'class'   => 'textbox',
        'style'   => 'standard',
        'name' => '',
        'value'   => ''
    );


    public function toHtml() {

        switch(web::instance()->defaultHtmlEditor) {
            case "fckeditor":
                return $this->toHtmlFCKeditor();
            case "tinymce":
                return $this->toHtmlTinymce();
            case "ckeditor":
                return $this->toHtmlCKeditor();
        }

//
    }

    private function toHtmlTinymce() {
        static $initialized = false;
        if($this->attrs['label']) {
            $str = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform htmleditor'><span>".$this->attrs['label']."</span>\n";
        }
        $str .= js_once('tiny_mce/tiny_mce');
//			mode: "textareas",
//			elements : "'.($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name']).'",

        $str .= '<textarea name="'.($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name']).'" class="htmleditor">'.$this->attrs['value'].'</textarea>';

        if(!$initialized) {
            $str .= '
            <script>
            tinyMCE.init({
                theme : "advanced",
                plugins: "xhtmlxtras",
                mode :  "specific_textareas",
                editor_selector : /(htmleditor)/,
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_resizing : true,
                theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|"
                + "justifyleft,justifycenter,justifyright,justifyfull,"
                + "bullist,numlist,outdent,indent,|,forecolor,backcolor,|,styleselect,formatselect,fontselect,fontsizeselect,|,code",
                theme_advanced_buttons2 : "link,unlink,anchor,image,separator,"
                +"undo,redo,cleanup,code,separator,sub,sup,charmap",
                theme_advanced_buttons3 : "",
                height:"450px",
                entity_encoding : "raw",
                width:"100%"
              });
            </script>';
          $initialized = true;
        }

//				content_css : "/telefono.css"
        if($this->attrs['label']) $str .= "</label>";
        return $str;

    }

    private function toHtmlFCKeditor() {

        if($this->attrs['label']) {
            $str = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform htmleditor'><span>".$this->attrs['label']."</span>\n";
        }

        include_once(dirname(__FILE__)."/../../resources/fckeditor/fckeditor.php");
        //return "$str<textarea ".$this->getAttributes('value, type').">".$this->attrs['value']."</textarea>";

        $oFCKeditor = new FCKeditor($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name']);
        $oFCKeditor->BasePath = '/resources/fckeditor/';
        $oFCKeditor->EditorPath = '/resources/fckeditor/';
        $oFCKeditor->Config["UserFilesAbsolutePath"] = $_SERVER["DOCUMENT_ROOT"]."/assets/";

/*		if(array_key_exists("basic", $this->__atributos)  || array_key_exists("simple", $this->__atributos)) {
            $oFCKeditor->ToolbarSet = "Basic";
            $oFCKeditor->Config["ToolbarStartExpanded"] = "true";
        }
        */
//		$style = $this->__atributos["style"];

        $oFCKeditor->ToolbarSet = $this->attrs['style'];

        if($this->attrs['width']) $oFCKeditor->Width = $this->attrs['width'];
        if($this->attrs['height']) $oFCKeditor->Height = $this->attrs['height']  + 50;

        if(array_key_exists("css", $this->attrs) && $this->attrs['css'])
        {
            if($oFCKeditor->ToolbarSet != "Basic") $oFCKeditor->ToolbarSet = "Styles";
            $oFCKeditor->Config["EditorAreaCSS"] = $this->attrs["css"];
            $oFCKeditor->Config["StylesXmlPath"] = "/resources/fckeditor/stylesxml.php?file=".$this->attrs["css"];
        }

        $oFCKeditor->Config["CustomConfigurationsPath"] = '/resources/fckeditor/default_config.js';
        $oFCKeditor->Value = $this->attrs['value'];

        $txt = $oFCKeditor->CreateHtml() ;
        $str .= $txt;
        if($this->attrs['label']) $str .= "</label>";
        return $str;
    }


    private function toHtmlCKeditor() {
        $str = js_once('ckeditor/ckeditor')."\n".js_once('ckeditor/config');
        $id = array_key_exists('id', $this->attrs) && $this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'];
        if(array_key_exists('label', $this->attrs) && $this->attrs['label']) {
            $str .= "
                    <label for='$id' class='ckeditor'>
                        <span>".$this->attrs['label']."</span>
                    </label>
                    ";

        }
        $value = array_key_exists('value', $this->attrs) ? $this->attrs['value'] : "";
        $str .= '<textarea name="'.$id.'" id="'.$id.'" class="ckeditor">'.$value.'</textarea>';
        if(array_key_exists('label', $this->attrs) && $this->attrs['label']) {
            $str .= "</label>\n";
        }
        return $str;
    }
}