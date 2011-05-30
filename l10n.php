<?
class l10n {

	private static $default_instance;	// La primera clase que se crea
	protected $default_language = 'es';
	protected $selected_language = 'es';
	protected $languages = array('es');
	private $cached_data = array();
	private $database;

	public function __construct() {
		session_start();
		if($_SESSION["l10n::selected_language"]) {
			$this->setLanguage($_SESSION["l10n::selected_language"]);
		}
		if(!l10n::$default_instance)  {
			l10n::$default_instance = $this;
		}
	}

	public function setLanguage($lang) {
		$this->selected_language = $lang;
		$_SESSION["l10n::selected_language"] = $lang;
        switch ($this->selected_language) {
        case "es":
            setlocale(LC_ALL, "es_ES.UTF-8");
        default:
        }
	}

	public function setDefaultLanguage($lang) {
		$this->default_language = $lang;
		if(!web::instance()->initialized())$this->setLanguage($lang);
	}

	public function get($id, $lang = null, $returnDefaultLanguage = true) {
		if(!$id || $id == '') return $id;
		if(!$lang) $lang = $this->selected_language;
		if (isset($this->cached_data[$lang][$id])) return $this->cached_data[$lang][$id];
		$sta = web::instance()->database->query("SELECT data from l10n where model='' and row=0 and field='".mysql_escape_string($id)."' and lang='$lang'");
		if($sta) $row = $sta->fetch();
		if($row && $row['data'] != '') {
			$this->cached_data[$lang][$id] = $row['data'];
			return $this->cached_data[$lang][$id];
		} else {
			if(!$returnDefaultLanguage) return '';
			// If the web is not in production we insert the new strings in l10n table in the database
			if($lang == $this->default_language ) {// && !web::instance()->isInProduction()) {
				$this->set($id, $id, $this->default_language);
				$this->cached_data[$lang][$id] = $id;
				return $this->cached_data[$lang][$id];
			} else {
				if($lang != $this->default_language) {
					$this->cached_data[$lang][$id] = $this->get($id, $this->default_language);
					return $this->cached_data[$lang][$id];
				}
			}

			return $id;
		}
	}

	public function set($id, $value, $lang = null) {
		if(!$lang) $lang = $this->selected_language;
		$this->cached_data[$lang][$id] = $value;
		$sta = web::instance()->database->query("SELECT data from l10n where model='' and row=0 and field='".mysql_escape_string($id)."' and lang='$lang'");
		if($sta) $row = $sta->fetch();
		if(!$row) {
			$sta = web::instance()->database->query("
			INSERT INTO l10n (lang, model, field, data, row)
			 VALUES('$lang', '', '".mysql_escape_string($id)."', '".mysql_escape_string($value)."', 0)");
		} else {
			$sta = web::instance()->database->query("
			UPDATE l10n set data='".mysql_escape_string($value)."' where model='' and row=0 and field='".mysql_escape_string($id)."' and lang='$lang'");
		}

/*		$sta = web::instance()->database->query("
			INSERT INTO l10n (lang, model, field, data, row)
			 VALUES('$lang', '', '$id', '$value', 0)
			 ON DUPLICATE KEY UPDATE data = '$value'
			");
*/
//		var_dump(web::instance()->database->errorInfo());

	}

	public static function instance() {
		return l10n::$default_instance;
	}

	public function getSelectedLang() {
		return $this->selected_language;
	}

	public function isNotDefault($lang = null) {
		if(!$lang) $lang = $this->selected_language;
		return $this->default_language != $lang;
	}
	public function setLanguages($langs) {
		$this->languages = $langs;
	}

	public function getLanguages() {
		return $this->languages;
	}
	public function getLanguage() { return $this->selected_language; }

	public function getNotDefaultLanguages() {
		return array_diff($this->languages, array($this->default_language));
	}

	public function getDefaultLanguage() { 	return $this->default_language;  }

	public function autoSelectLanguage() {
		foreach(explode(",",  $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $i => $value) $ask_lang[$i]=trim(array_shift(explode(';', $value)));
		$accept_lang = $this->languages;
    	foreach($ask_lang as $lang) {
			if (in_array($lang, $accept_lang))  { $this->setLanguage($lang); return true; }
        	$short_lang = substr($lang, 0, 2);
        	if (in_array($short_lang, $accept_lang))  { $this->setLanguage($short_lang); return true ; }
        }
//		return "es";
	}

}
?>
