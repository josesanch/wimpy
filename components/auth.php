<?php
// Valid coding standards | except: $user_field, $password_field
class auth
{

    public $table = 'users';
    public $user_field = 'user';
    public $password_field = 'password';
    public $type = "form";
    public $title = "Área de cliente";
    public $showLogo = true;
    public $message = "Si tiene algún problema puede contactar con nuestro soporte por e-mail en soporte@o2w.es o por teléfono llamando al 968 19 29 05";

    const FORM = 100;
    const REALM = 101;
    const FORM_MD5 = 102;

    const VIEW = 1;
    const ADD = 2;
    const MODIFY = 3;
    const DELETE = 4;

    public function __construct($params)
    {
        if ($params['table']) $this->table = $params['table'];
        if ($params['user_field']) $this->user_field = $params['user_field'];
        if ($params['password_field'])
            $this->password_field = $params['password_field'];
    }

    public function login($user = null, $pass = null)
    {
        if (!($user && $pass)) {
            $user = $_POST["username"];
            $pass = $_POST["password"];
        }

        $this->usuario = $user;
        $password_field = $this->getPasswordField($this->password_field);
        $sql =
            "SELECT * FROM
            $this->table WHERE
            $this->user_field=".web::instance()->database->quote($user)."
            and $password_field=".web::instance()->database->quote($pass);

        $statement = web::instance()->database->query($sql);
        if ($statement) {
            $result = $statement->fetch();
            if ($result['id']) {
                $_SESSION["auth_session_".$this->table] = $result;
                log::add($result['user'], "LOGIN $user", log::OK);
                web::mail("LOGIN $user IP:".$_SERVER["HTTP_CLIENT_IP"].": ".$_SERVER["HTTP_X_FORWARDED"], "LOGIN en ".$_SERVER['SERVER_NAME'], "jose@o2w.es");
                return true;
            }

        }
        log::add(
            "",
            "LOGIN FAILED $user",
            log::ERROR,
            "USER: $user, PASS: $pass"
        );
        return false;
    }

    public function getPasswordField($field)
    {
        return web::instance()->authMethod == Auth::FORM_MD5 ? "md5(CONCAT($field, '".$_SESSION["auth_number"]."'))" : $field;
    }

    public function isLogged()
    {
        if (array_key_exists("auth_session_".$this->table, $_SESSION))
            return ($_SESSION["auth_session_".$this->table]);
    }

    public function logout()
    {
        $_SESSION['logout'] = true;
        log::add('', "LOGOUT", log::OK);
        unset($_SESSION["auth_session_".$this->table]);

    }

    public function get($item)
    {
        if (isset($_SESSION["auth_session_".$this->table][$item])) {
            return $_SESSION["auth_session_".$this->table][$item];
        }
    }


    public function _requestAuthFormMd5()
    {
        if (!$_SESSION["auth_number"]) $_SESSION["auth_number"] = rand(0, 99999);
        $view = new html_template(dirname(__FILE__)."/../views/auth/md5.html");
        $view->title = $this->title;
        $view->showLogo = $this->showLogo;
        $view->message = $this->message;

        if ($_POST["username"] && $_POST["password_user"]) {
            $usuario = ($_POST["username"]);
            $clave = ($_POST["password_user"]);
            if ($this->login($usuario, $clave, true) == true) {
                return true; unset($_SESSION['logout']);
            }
            $view->error = __("Error de autentificación");
        }


        $view->numero = $_SESSION["auth_number"];
        echo $view->display();

    }

    public function _requestAuthForm()
    {
        $view = new html_template(dirname(__FILE__)."/../views/auth/index.html");
        $view->title = $this->title;
        $view->showLogo = $this->showLogo;
        $view->message = $this->message;
        $view->titulo = $view->css_files = $view->js_files = $view->error =  '';

        if (array_key_exists('username', $_POST)
            && $_POST["username"]
            && array_key_exists('password', $_POST)
            && $_POST["password"]) {
            $usuario = $_POST["username"];
            $clave = $_POST["password"];
            if ($this->login($usuario, $clave) == true) {
                return true; unset($_SESSION['logout']);
            }
            $view->error = __("Error de autentificación");
        }

        echo $view->display();
    }


    public function _requestAuthRealm()
    {
        if (isset($_SERVER['PHP_AUTH_USER'])
            && !$_SESSION["auth_module"]["logout"]
            && !isset($_SESSION['logout'])
        ) {
            $usuario = mysql_escape_string($_SERVER['PHP_AUTH_USER']);
            $clave = mysql_escape_string($_SERVER['PHP_AUTH_PW']);
            if ($this->login($usuario, $clave) == true) {
                return true; unset($_SESSION['logout']);
            }
        }

        unset($_SESSION['logout']);
        $content = ob_get_clean();
        header('WWW-Authenticate: Basic realm="Zona de acceso restringido"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    public function requestAuth()
    {
        switch (web::instance()->authMethod) {
            case Auth::REALM:
                return $this->_requestAuthRealm();
            break;
            case Auth::FORM:
                return $this->_requestAuthForm();
            break;
            case Auth::FORM_MD5:
                return $this->_requestAuthFormMd5();
            break;

        }
    }

    public function hasPermission($perm, $model)
    {
        return true;
    }

}
