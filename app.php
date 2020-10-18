<?php

use Lampion\Http\Router;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\Http\Url;
use Lampion\User\Auth;

use Carnival\Admin\Bootstrap as Admin;
use Lampion\Application\Application;

use Reflector\Bootstrap as Reflector;

$app    = new Application;
$router = new Router;

// TODO: Remove this, idiot
$_SESSION['Lampion']['lang'] = 'cs';

$token = null;

if(isset($_POST['authToken'])) { $token = $_POST['authToken']; }
if(isset($_GET['authToken']))  { $token = $_GET['authToken']; }

if(Auth::isLoggedIn($token)) {
    $ac = new Admin();
    $ac->registerRoutes($router);

    $router->get('logout', function(Request $req, Response $res) {
        Auth::logout();

        if($req->isAjax()) {
            $res->json([
                'redirect' => Url::link('login')
            ]);
        }

        else {
            $res->redirect('login');
        }
    });
}

else {
    Url::redirectOnce('login');
}

$router
    ->get("login", "Carnival\Admin\Controller\User\LoginController::index")
    ->post("login", "Carnival\Admin\Controller\User\LoginController::login");

$app->router($router);

$app->listen();

if(ENVIRONMENT == 'dev') {
    $reflector = new Reflector;

    $reflector->db       = $_SESSION['Lampion']['DB'];
    $reflector->carnival = true;

    $reflector->display();
}