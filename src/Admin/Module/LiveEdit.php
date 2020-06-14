<?php

namespace Carnival\Admin\Module;

use Lampion\User\Auth;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Entity\EntityManager;

use Carnival\Entity\LiveEdit as LEEntity;
use Carnival\Entity\User;
use Lampion\Application\Application;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\View\View;

use stdClass;
use Error;

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
                    [
                        'outer' => $req->post('contentOriginalOuter'),
                        'inner' => $req->post('contentOriginalInner')
                    ],
                    (bool)$req->post('editing'),
                    $req->post('nameOriginal')
                )
            );

            exit;
        });

        $router->post('le/delete', function(Request $req, Response $res) {
            if(!Auth::isLoggedIn()) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
            
            $le = new LiveEdit();

            $res->send(
                $le->delete(
                    $req->post('name'),
                    $req->post('route'),
                    $req->post('template')
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
        $nodes    = new stdClass;
        $entities = $this->em->findBy(LEEntity::class, ['route' => $this->route ?? $route ], 'name', 'ASC');

        if(empty($entities)) {
            return [];
        }

        # Making LiveEdit node's name the index
        foreach($entities as $entity) {
            $nodes->{$entity->name}               = $entity;
            $nodes->{$entity->name}->contentClean = $entity->content;
            $nodes->{$entity->name}->content      = $entity->getHTMLContent();

            # Get rid of the original, as it is not needed and only causes JSON parsing issues
            unset($nodes->{$entity->name}->original);
        }

        return $nodes;
    }

    public function set(string $name, string $route, $content, string $template, array $contentOriginal, bool $editing, string $nameOriginal = null) {
        if(!Auth::isLoggedIn()) {
            // TODO: Error handling
            throw new Error('You are not logged in!');
            exit;
        }

        # -- SETTING ENTITY -- #

        $leEntity = $this->em->findBy(LEEntity::class, [
            'name'  => $nameOriginal ?? $name, 
            'route' => $route
        ]);

        # Checking for duplicate name
        if(!$editing && $leEntity) {
            return json_encode([
                'error' => 'Node with this name already exists!'
            ]);
        }

        # Creating a new node
        if(!$leEntity) {
            $leEntity = new LEEntity();
        }

        # Editing an existing node
        else {
            $leEntity = $leEntity[0];
        }

        # If node is being edited, replace it's original content
        if($editing) {
            $content = str_replace($contentOriginal['inner'], $content, $leEntity->content);
        }

        $leEntity->name    = $name;
        $leEntity->route   = $route;
        $leEntity->content = $content;

        # Setting the original content, so if node is deleted, the content can be restored
        if(!$editing) {
            $leEntity->original = $contentOriginal['inner'];
        }
        
        $leEntity->user = $this->user;

        # -- TEMPLATE EDITING -- #

        # Replacing static HTML with a liveEdit object
        if(!$editing) {
            # Getting template's contents
            $template = ROOT . APP . Application::name() . TEMPLATES . $template . '.twig';
            $html     = file_get_contents($template);

            # Checking for all occurences of the original content
            $offset = 0;
            $allpos = array();
            while (($pos = strpos($html, $contentOriginal['outer'], $offset)) !== FALSE) {
                $offset   = $pos + 1;
                $allpos[] = $pos;
            }

            # Checking for duplicates
            if(sizeof($allpos) > 1) {
                # Giving duplicates a LiveEdit duplicate attribute, basically giving them their own "ID"
                $element            = substr($html, $allpos[0], strlen($contentOriginal['outer']));
                $elementOccurrences = substr_count($html, $element);

                # Replace every occurence of element in HMTL
                for($i = 0; $i < $elementOccurrences; $i++) {
                    $elementNew = $this->str_replace_first('>', ' data-le-id="' . uniqid() . '">', $element);

                    $html = $this->str_replace_first($element, $elementNew, $html);
                }

                file_put_contents($template, $html);

                # Send back an error message, prompting user to refresh the page and load the new HTML
                # Possible TODO: no refresh
                echo 'Duplicate detected, we are sorry about that, please reload the page and try again!';
                exit;
            }

            $element = substr($html, $allpos[0], strlen($contentOriginal['outer']));
            $element = str_replace($contentOriginal['inner'], '{{ liveEdit.' . $leEntity->name . '|raw }}', $element);

            $html = str_replace($contentOriginal['outer'], $element, $html);

            file_put_contents($template, $html);
        }

        # Renaming a liveEdit object
        elseif($editing && $name != $nameOriginal) {
            $template = ROOT . APP . Application::name() . TEMPLATES . $template . '.twig';
            $html     = file_get_contents($template);

            $html = str_replace('liveEdit.' . $nameOriginal, 'liveEdit.' . $name, $html);

            file_put_contents($template, $html);
        }

        $this->em->persist($leEntity);

        return json_encode([
            'content' => $this->em->findBy(LEEntity::class, [
                'name'  => $name,
                'route' => $route
            ])[0]->getHTMLContent(),
            'nodes' => $this->get($route)
        ]);
    }

    public function delete(string $name, string $route, string $template) {
        $leEntity = $this->em->findBy(LEEntity::class, [
            'name'  => $name,
            'route' => $route
        ])[0];

        $template = ROOT . APP . Application::name() . TEMPLATES . $template . '.twig';
        $html = file_get_contents($template);

        $html = str_replace('{{ liveEdit.' . $name . '|raw }}', $leEntity->original, $html);

        file_put_contents($template, $html);

        $this->em->destroy($leEntity);

        return json_encode([
            'original' => $leEntity->original,
            'nodes'    => $this->get($route)
        ]);
    }

    private function str_replace_first($from, $to, $content) {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }

}