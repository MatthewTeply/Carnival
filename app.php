<?php

use Lampion\Http\Router;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\Http\Url;
use Lampion\User\Auth;

use Carnival\Admin\AdminCore;

$router = new Router();

// TODO: Remove this, idiot
$_SESSION['Lampion']['lang'] = 'cs';

$token = null;

if(isset($_POST['authToken'])) { $token = $_POST['authToken']; }
if(isset($_GET['authToken']))  { $token = $_GET['authToken']; }

if(Auth::isLoggedIn($token)) {
    $ac = new AdminCore();
    $ac->registerRoutes($router);
}

else {
    $router->get(DEFAULT_HOMEPAGE, function(Request $req, Response $res) { $res->redirect('login'); });
}

$router
    ->get("login", "Carnival\Controller\User\LoginController::index")
    ->get('logout', function(Request $req, Response $res) {
        Auth::logout();

        if($req->isAjax()) {
            $res->json([
                'redirect' => Url::link('login')
            ]);
        }

        else {
            $res->redirect('login');
        }
    })
    ->post("login", "Carnival\Controller\User\LoginController::login")
    ->listen();

