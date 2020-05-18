<?php

namespace Carnival\Admin\Module;

use Lampion\Debug\Console;
use Lampion\User\Auth;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Entity\EntityManager;

use Carnival\Entity\LiveEdit as LEEntity;
use Carnival\Entity\User;
use Error;
use Lampion\Application\Application;
use Lampion\Database\Query;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\View\View;
use stdClass;

class LiveEdit {

    private $route;
    private $template;
    private $user;
    private $em;
    private $view;

    public function __construct(string $route = null, $template = null) {
        $this->route    = $route;
        $this->template = $template;
        
        $this->em   = new EntityManager();
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');

        if(Auth::isLoggedIn()) {
            $this->user = $this->em->find(User::class, unserialize(LampionSession::get('user'))->id);
        }
    }

    public static function registerRoutes(&$router) {
        $router->post('le/set', function(Request $req, Response $res) {
            if(!Auth::isLoggedIn()) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
            
            $le = new LiveEdit();

            $res->send(
                $le->set(
                    $req->post('name'),
                    $req->post('route'),
                    $req->post('content'),
                    $req->post('template'),
                    !empty($req->post('contentOriginalOuter')) ? [
                        'outer' => $req->post('contentOriginalOuter'),
                        'inner' => $req->post('contentOriginalInner')
                    ] : null
                )
            );

            exit;
        });
    }

    public function panel() {
        # If user is logged in, display control panel
        if(Auth::isLoggedIn()) {
            $this->view->render('admin/module/liveEdit/panel', [
                'nodes'    => $this->get($this->route),
                'user'     => $this->user,
                'route'    => $this->route,
                'template' => $this->template
            ]);
        }
    }
    
    public function get($route = null) {
        $nodes = new stdClass;
        $entities = $this->em->findBy(LEEntity::class, ['route' => $this->route ?? $route ], 'id', 'DESC');

        if(empty($entities)) {
            return [];
        }

        # Making LiveEdit node's name the index
        foreach($entities as $entity) {
            $nodes->{$entity->name}               = $entity;
            $nodes->{$entity->name}->content      = $entity->getHTMLContent();
            $nodes->{$entity->name}->contentClean = $entity->content;
            unset($nodes->{$entity->name}->original);
        }

        return $nodes;
    }

    public function set(string $name, string $route, $content, string $template, array $contentOriginal = null) {
        if(!Auth::isLoggedIn()) {
            // TODO: Error handling
            throw new Error('You are not logged in!');
            exit;
        }

        $leEntity = $this->em->findBy(LEEntity::class, [
            'name' => $name, 
            'route' => $route
        ]);

        if(!$leEntity) {
            $leEntity = new LEEntity();

            $edit = false;
        }

        else {
            $leEntity = $leEntity[0];

            $edit = true;
        }

        if($edit) {
            $content = str_replace($contentOriginal['outer'], $content, $leEntity->content);
        }

        $leEntity->name     = $name;
        $leEntity->route    = $route;
        $leEntity->content  = $content;
        $leEntity->original = $contentOriginal['outer'];
        $leEntity->user     = $this->user;

        if(!$edit) {
            $template = ROOT . APP . Application::name() . TEMPLATES . $template . '.twig';
            $html = file_get_contents($template);

            $offset = 0;
            $allpos = array();
            while (($pos = strpos($html, $contentOriginal['outer'], $offset)) !== FALSE) {
                $offset   = $pos + 1;
                $allpos[] = $pos;
            }

            if(sizeof($allpos) > 1) {
                echo 'Element has a duplicate! Please contact us to fix this!';
                exit;
            }

            $element = substr($html, $allpos[0], strlen($contentOriginal['outer']));
            $element = str_replace($contentOriginal['inner'], '{{ liveEdit.' . $leEntity->name . '|raw }}', $element);

            $html = str_replace($contentOriginal['outer'], $element, $html);

            file_put_contents($template, $html);
        }

        $this->em->persist($leEntity);

        return json_encode($this->get($route));
    }

}