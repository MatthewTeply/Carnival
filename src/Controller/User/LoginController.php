<?php

namespace Carnival\Controller\User;

use Lampion\Controller\ControllerBase;
use Lampion\Http\Url;
use Lampion\User\Auth;

class LoginController extends ControllerBase {

    public function index() {
        if(Auth::isLoggedIn()) {
            Url::redirect(DEFAULT_HOMEPAGE);
        }

        $view = $this->load()->view();

        $view->render('user/login');
    }

    public function login() {
        if(!isset($_POST['username']) || !isset($_POST['pwd'])) {
            return Url::redirect('login', [
                'error' => 'empty'
            ]);
        }

        if(Auth::login($_POST['username'], $_POST['pwd'])) {
            return Url::redirect(DEFAULT_HOMEPAGE);
        }

        else {
            return Url::redirect('login', [
                'error' => 'wrong'
            ]);
        }
    }

}