<?php

namespace Carnival\Admin;

use Lampion\Core\FileSystem;
use Lampion\Core\Router;
use Lampion\View\View;
use Lampion\Session\Lampion as LampionSession;

class AdminCore {

    public $view;

    public $header;
    public $nav;
    public $footer;

    public $entityName;
    public $table;
    public $className;
    public $entityConfig;
    public $config;
    public $title;

    public $fs;

    public function __construct() {
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');
        $this->fs   = new FileSystem();
        
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival.json'));
        
        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        $this->table      = strtolower($this->entityName);

        // TODO: Default action
        $action = explode('/', explode($this->entityName, $_GET['url'])[1])[1] ?? 'list';
        
        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;
        
        if(isset($this->entityConfig->{$action}->title)) {
            $this->title = $this->entityConfig->{$action}->title;
        }

        else {
            if( isset($this->entityConfig->title)) {
                $this->title = $this->entityConfig->title;
            }

            else {
                $this->title = $this->entityName;
            }
        }

        $args['__css__']     = WEB_ROOT . APP . 'carnival' . CSS;
        $args['__scripts__'] = WEB_ROOT . APP . 'carnival' . SCRIPTS;
        $args['__img__']     = WEB_ROOT . APP . 'carnival' . IMG;
        $args['user']        = (array)LampionSession::get('user');
        $args['title']       = $this->title;

        $this->header = $this->view->load('partials/header', $args);
        $this->nav    = $this->view->load('partials/nav'   , $args);
        $this->footer = $this->view->load('partials/footer', $args);
    }

    public function registerRoutes(Router $router) {
        # Getting all entities
        $entities = array_keys((array)$this->config->entities);

        # Declaring entity actions
        $actions = [
            'list',
            'new',
            'edit/{id}',
            'delete/{id}',
            'show/{id}'
        ];

        foreach($entities as $entity) {
            # Register default action
            #   TODO: configurable default action
            $router->get($entity, 'Carnival\Admin\Action\ListAction::display');

            # Register entity route, and all it's actions
            foreach($actions as $action) {
                $router->get($entity . '/' . $action, 'Carnival\\Admin\\Action\\' . ucfirst(explode('/{', $action)[0]) . 'Action::display');
                $router->post($entity . '/' . $action, 'Carnival\\Admin\\Action\\' . ucfirst(explode('/{', $action)[0]) . 'Action::submit');
            }
        }
    }

}