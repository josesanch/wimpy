<?
class html_form_date extends html_form_input {

	static $current = 0;
	protected $attrs = array
	(
		'type'    => 'text',
		'class'   => 'textbox',
		'value'   => '',
		'size' => 10
	);

	public function toHtml() {
		$numCalendar = html_form_date::$current++;
		$str = "<input type=button class='boton botones' value='...' onclick='testCal($numCalendar, ".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] ).")' name=botoncito>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
		//$str = "<a href=javascript:testCal($numCalendar,$input);><img src=/resources/icons/calendar.png hspace=0 vspace=0 border=0></a>&nbsp;";
		$str .= "<div id=holder_cal_$numCalendar></div>";
		if($numCalendar == 0)
		{
		$str .= "
			<script type='text/javascript'>
			var current_cal = null;
		    function on_close(cal) {
		        if (current_cal) {
		            // the calendar is contained in the #holder element
		            var el = document.getElementById(cal.yourHolder);
		            el.style.visibility = 'hidden';      // here we hide it
		            el.removeChild(el.firstChild);       // and destroy the calendar
		            current_cal = null;                  // we don't have one anymore
		        }
		    }

		    function testCal(cal, input) {
		        if (current_cal) return false;
		        var el = document.getElementById('holder_cal_' + cal);
		        current_cal = new Calendar(true, input, on_close, 'holder_cal_' + cal);
		        el.appendChild(current_cal.element);
		        el.style.visibility = 'visible';
		    }
	    </script>

		".js_once("calendar/calendar").css_once("calendar");
			if($config->lang->code != "es") {
				$str .= js_once("calendar/calendar_".$config->lang->code);
			}
		}
		if($this->attrs['label']) {
			$label = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'>".$this->attrs['label']."</label>\n";
		}

	    return $label."<INPUT ".$this->getAttributes()."/>".$str."\n";
	}
}
?>
