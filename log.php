<?
class log {
	const INFO = "info";
	const ERROR = "error";
	const WARNING = "warning";

	private static $data, $classesToDebug = array (
		"ALL_CLASSES"
	), $log_type;

	public static $active = true;
	public static $bench;

	/**
	 *    Escribe en la pantalla de debug lo que se le pasa como parametro.
	 */

	public static function debug($str = "", $type = log :: INFO, $line = null, $file = null, $classname = null)
	{
		if (count(log :: $classesToDebug) == 0 || !log::is_active()) return;

		$mem = memory_get_usage();
		$elapsed = (log::is_bench_active()) ? "(" . log::$bench->elapsed() . " seg)" : "";
		log::$data .= "<span class=debug_$type><b>$classname</b>: " . htmlspecialchars(str_replace("\n", "", nl2br($str)), ENT_QUOTES) . " $elapsed - <b>Mem Used</b>: " . log :: bytes($mem) . " - " . $mem . "<br></span>";
	}

	function to_file($str) {
		fwrite(fopen($_SERVER["DOCUMENT_ROOT"] . "/log_debug.txt", "a+"), time().": $str\n");
	}

	function verbose($arr = null, $type = 4) {
		log :: $log_type = $type;
		if ($arr != null) {
			if (is_array($arr)) {
				$verbose = array_merge(log :: $classesToDebug, $arr);
			} else {
				array_push(log :: $classesToDebug, $arr);
			}
		}
	}

	function off()
	{
		log :: $active= false;
	}

	function clearVerbose()
	{
		log :: $classesToDebug = array ();
	}

	private function debug_window($str)
	{
		return "<script>debugWindow.document.write('$str');debugWindow.scrollBy(0,999999);debugWindow.focus();</script>";
	}

	function to_html()
	{
		if (log::$data == "" || count(log :: $classesToDebug) == 0 || !log::is_active()) return "";
		return log::create_window().log::debug_window(str_replace("'", "\"", log::$data));
	}

	private function bytes($size) {
		$kb = 1024;
		$mb = 1048576;
		$gb = 1073741824;
		$tb = 1073741824 * 1024;
		if ($size < $kb) {
			return $size . " B";
		} else
			if ($size < $mb) {
				return round($size / $kb, 2) . " KB";
			} else
				if ($size < $gb) {
					return round($size / $mb, 2) . " MB";
				} else
					if ($size < $tb) {
						return round($size / $gb, 2) . " GB";
					} else {
						return round($size / $tb, 2) . " TB";
					}
	}

	public static function is_active()
	{
		return log :: $active;
	}

	public static function is_bench_active()
	{
		return log::$bench ? true : false;
	}


	public static function set($level)
	{
		switch ($level) {
			case "true":
				log::$active = true;
				log::$bench = new bench();
				break;
			case "bench":
				log::$active = false;
				log::$bench = new bench();
				break;
			default:
				log::$active = false;
				log::$bench = false;

				break;
		}
	}

	private function create_window() {
		return "<script>var debugWindow = window.open('', 'debugWindow', 'dependent=yes,height=300,width=600,menubar=no,personalbar=no,resizable=yes,screenX=700,screenY=20,toolbar=no,titlebar=no,scrollbars=yes'); var a= 'hola';
						debugWindow.document.write('<style>.debug_error { color:red; }</style><title>Debug</title><body topmargin=0><hr width=100% color=blue><font size=1 face=verdana>');
						</script>";
	}

}
?>
