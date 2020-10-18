<?php

namespace Carnival\Admin\Module;

use Carnival\Entity\Language;
use Lampion\User\Auth;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Entity\EntityManager;

use Carnival\Entity\Liveedit as LEEntity;
use Carnival\Entity\User;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\View\View;

use stdClass;
use Error;

use Lampion\Debug\Console;
use Lampion\FileSystem\Path;
use Lampion\Misc\Util;

class Liveedit {

    private $route;
    private $template;
    private $user;
    private $em;
    private $view;

    public function __construct(string $route = null, $template = null) {
        $this->route    = $route;
        $this->template = $template;
        
        $this->em   = new EntityManager();
        $this->view = new View(Path::get('carnival:public/templates'), 'carnival');

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
            
            $le = new Liveedit();

            $res->send(
                $le->set(
                    $req->input('name'),
                    $req->input('route'),
                    $req->input('content'),
                    $req->input('type')
                )
            );

            exit;
        });

        $router->post('le/scan', function(Request $req, Response $res) {
            if(!Auth::isLoggedIn()) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
            
            $le = new Liveedit();

            $le->scan(
                $req->input('template'),
                $req->input('route'),
                $req->input('name'),
                $req->input('content'),
                $req->input('contentOriginal'),
                $req->input('type')
            );

            $res->json([
                'name' => $req->input('name')
            ]);

            exit;
        });

        $router->post('le/delete', function(Request $req, Response $res) {
            if(!Auth::isLoggedIn()) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
            
            $le = new Liveedit();

            $res->send(
                $le->delete(
                    $req->input('name'),
                    $req->input('route'),
                    $req->input('template')
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

        $language = $this->em->findBy(Language::class, [
            'code' => $_SESSION['Lampion']['language']
        ])[0];

        $entities = $this->em->findBy(LEEntity::class, [
            'route'    => $this->route ?? $route,
            'language' => $language
        ], 'name', 'ASC');

        if(empty($entities)) {
            return [];
        }

        # Making LiveEdit node's name the index
        foreach($entities as $entity) {
            $nodes->{$entity->name}               = $entity;
            $nodes->{$entity->name}->contentClean = $entity->content;
            $nodes->{$entity->name}->content      = $entity->content;

            # Get rid of the original, as it is not needed and only causes JSON parsing issues
            unset($nodes->{$entity->name}->original);
        }

        return $nodes;
    }

    public function set(string $name, string $route, $content, string $type) {
        if(!Auth::isLoggedIn()) {
            // TODO: Error handling
            throw new Error('You are not logged in!');
            exit;
        }

        $language = $this->em->findBy(Language::class, [
            'code' => $_SESSION['Lampion']['language']
        ])[0];

        # -- SETTING ENTITY -- #

        $leEntity = $this->em->findBy(LEEntity::class, [
            'name'     => $name,
            'route'    => $route,
            'language' => $language
        ])[0];

        /*
        # Checking for duplicate name
        if(!$editing && $leEntity) {
            return json_encode([
                'error' => 'Node with this name already exists!'
            ]);
        }

        # Editing an existing node
        else {
            $leEntity = $leEntity[0];
        }
        */

        # If node is being edited, replace it's original content
        /*
        if($editing) {
            $content = str_replace($contentOriginal['inner'], $content, $leEntity->content);
        }
        */

        $leEntity->name    = $name;
        $leEntity->route   = $route;
        $leEntity->content = $content;
        $leEntity->type    = $type;        
        $leEntity->user    = $this->user;

        # -- TEMPLATE EDITING -- #

        /*
        if(!$editing) {
            
        }

        # Renaming a liveEdit object
        elseif($editing && $name != $nameOriginal) {
            $template = Path::get('public/templates/' . $template . '.twig');
            $html     = file_get_contents($template);

            $html = str_replace('liveEdit.' . $nameOriginal, 'liveEdit.' . $name, $html);

            file_put_contents($template, $html);
        }
        */

        $this->em->persist($leEntity);

        return json_encode([
            'content' => $this->em->findBy(LEEntity::class, [
                'name'  => $name,
                'route' => $route
            ])[0]->getHTMLContent(),
            'nodes' => $this->get($route)
        ]);
    }

    public function scan(string $template, string $route, string $name, string $content, array $contentOriginal, string $type) {
        # Replacing static HTML with a liveEdit object

        # Creating a new node
        $leEntity = new LEEntity();

        $language = $this->em->findBy(Language::class, [
            'code' => $_SESSION['Lampion']['language']
        ])[0];

        $leEntity->name     = $name;
        $leEntity->route    = $route;
        $leEntity->content  = $content;
        $leEntity->original = $contentOriginal['inner'];
        $leEntity->type     = empty($type) ? 'text' : $type;        
        $leEntity->language = $language;        
        $leEntity->user     = $this->user;

        $this->em->persist($leEntity);

        //print_r($_SESSION['Lampion']['DB']['queries']);

        # Getting template's contents
        $template = Path::get('public/templates/' . $template . '.twig');
        $html     = file_get_contents($template);

        # Checking for all occurences of the original content
        $offset = 0;
        $allpos = array();
        while (($pos = strpos($html, $contentOriginal['outer'], $offset)) !== false) {
            $offset   = $pos + 1;
            $allpos[] = $pos; 
        }

        /*
        # Checking for duplicates
        if(sizeof($allpos) > 1) {
            # Giving duplicates a LiveEdit duplicate attribute, basically giving them their own "ID"
            $element            = substr($html, $allpos[0], strlen($contentOriginal['outer']));
            $elementOccurrences = substr_count($html, $element);

            # Replace every occurence of element in HMTL
            for($i = 0; $i < $elementOccurrences; $i++) {
                $elementNew = $this->str_replace_first('>', 'data-le-type="' . $leEntity->type . '" data-le-name="' . $leEntity->name . '">', $element);

                $html = $this->str_replace_first($element, $elementNew, $html);
            }

            file_put_contents($template, $html);

            # Send back an error message, prompting user to refresh the page and load the new HTML
            # Possible TODO: no refresh
            echo 'Duplicate detected, we are sorry about that, please reload the page and try again!';
            exit;
        }
        */

        $element = substr($html, $allpos[0], strlen($contentOriginal['outer']));

        $attrString = '';
        $attrString .= ' id="' . $leEntity->name . '"';
        $attrString .= ' data-le-name="' . $leEntity->name . '"';

        if(empty($type)) {
            $attrString += ' data-le-type="' . $leEntity->type . '"';
        }

        $attrString .= '>';

        $element = \Lampion\Utilities\General::strReplaceFirst('>', $attrString, $element);

        switch($leEntity->type) {
            case 'text':
                $element = str_replace($contentOriginal['inner'], '{{ liveEdit.' . $name . '|raw }}', $element);
                break;
            case 'img':
                preg_match_all('/(src="[^"]*")|[^"]*/m', $element, $src, PREG_SET_ORDER, 0);
                
                $element = str_replace($src[2], '{{ storage(\'carnival:\' ~ liveEdit.' . $name . '|raw) }}', $element);
                break;
        }

        $html = str_replace($contentOriginal['outer'], $element, $html);

        file_put_contents($template, $html);
    }

    public function delete(string $name, string $route, string $template) {
        $leEntity = $this->em->findBy(LEEntity::class, [
            'name'  => $name,
            'route' => $route
        ])[0];

        $template = Path::get('public/templates/' . $template . '.twig');
        $html = file_get_contents($template);

        $html = str_replace('{{ liveEdit.' . $name . '|raw }}', $leEntity->original, $html);

        file_put_contents($template, $html);

        $this->em->destroy($leEntity);

        return json_encode([
            'original' => $leEntity->original,
            'nodes'    => $this->get($route)
        ]);
    }

}