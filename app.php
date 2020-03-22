<?php

use Lampion\Core\Router;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\User\Auth;

use Carnival\Admin\AdminCore;
use Carnival\Entity\Liveedit;
use Lampion\Database\Query;

$router = new Router();

if(Auth::isLoggedIn()) {
    $ac = new AdminCore();
    $ac->registerRoutes($router);
    
    $router
        ->post('liveedit', function(Request $req, Response $res) {
            $le_id = $_POST['le_id'];
            $content = $_POST['content'];
    
            $id = Query::select('liveedit', ['id'], [
                'le_id' => ['=', $le_id]
            ]);
    
            $le = new Liveedit(isset($id[0]['id']) ? $id[0]['id'] : null);
            $le->le_id = $le_id;
    
            if(isset($id[0]['id'])) {
                if(!$_POST['initial']) {
                    $le->content = $content;
                }
            }
    
            else {
                $le->content = $content;
            }
            
            $le->persist();
        });
}

else {
    $router->get(DEFAULT_HOMEPAGE, function(Request $req, Response $res) { $res->redirect('login'); });
}

$router
    ->get('liveedit/{id}', function(Request $req, Response $res) {
        @$content = Query::select('liveedit', ['content'], [
            'le_id' => ['=', $req->params['id']]
        ])[0]['content'];
        
        echo htmlspecialchars_decode($content);
    })
    ->get("login", "Carnival\Controller\User\LoginController::index")
    ->get('logout', function(Request $req, Response $res) {
        Auth::logout();

        $res->redirect('login');
    })
    ->post("login", "Carnival\Controller\User\LoginController::login")
    ->listen();

