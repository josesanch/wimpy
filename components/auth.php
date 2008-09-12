<?
class auth {

	public $table = 'users';
	public $user_field = 'user';
	public $password_field = 'password';

	public function __construct($params) {
		if($params['table']) $this->table = $params['table'];
		if($params['user_field']) $this->user_field = $params['user_field'];
		if($params['password_field']) $this->password_field = $params['password_field'];
	}

	public function login($user = null, $pass = null) {
		if(!($user && $pass)) {
			$user = $_POST["username"];
			$pass = $_POST["password"];
		}
		$this->usuario = $user;
		$statement = web::instance()->database->query("SELECT * FROM $this->table WHERE $this->user_field='$user' and $this->password_field='$pass'");
		if($statement) {
			$result = $statement->fetch();
			if($result['id']) {
				$_SESSION["auth_session_".$this->table] = $result;
				return true;
			}

		}
		return false;
	}

	public function isLogged() {
		return ($_SESSION["auth_session_".$this->table]);
	}

	public function logout() {
	 	unset($_SESSION["auth_session_".$this->table]);
	}

	public function get($item) {
		return $_SESSION["auth_session_".$this->table][$item];
	}

	public function requestAuth() {
   		if(isset($_SERVER['PHP_AUTH_USER']) && $force == false && !$_SESSION["auth_module"]["logout"]) {
    			$usuario = mysql_escape_string($_SERVER['PHP_AUTH_USER']);
    			$clave = mysql_escape_string($_SERVER['PHP_AUTH_PW']);
    			if($this->login($usuario, $clave) == true)  { return true; }
    		}
//    		$_SESSION["auth_module"]["logout"]  = false;
    		$content = ob_get_clean();
			header('WWW-Authenticate: Basic realm="Autentificacin"');
    		header('HTTP/1.0 401 Unauthorized');

	}


}
?>
