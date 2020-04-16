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
    public $defaultAction;
    public $declaredActions;

    public $fs;

    public function __construct() {

        /**
         * Initial config
         */
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');
        $this->fs   = new FileSystem();
        
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/admin.json'));
        
        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        $this->table      = strtolower($this->entityName);

        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;

        /**
         * Setting the defualt action
         */
        if(isset($this->entityConfig->default_action)) {
            $this->defaultAction = $this->entityConfig->default_action;
        }

        else {
            if(isset($this->config->defaultAction)) {
                $this->defaultAction = $this->config->defaultAction;
            }

            else {
                $this->defaultAction = 'list';
            }
        }

        $action = explode('/', explode($this->entityName, $_GET['url'])[1])[1] ?? $this->defaultAction;

        /**
         * Setting title
         */
        if(isset($this->entityConfig->{$action}->title)) {
            $this->title = $this->entityConfig->{$action}->title;
        }

        else {
            if(isset($this->entityConfig->title)) {
                $this->title = $this->entityConfig->title;
            }

            else {
                $this->title = $this->entityName;
            }
        }

        /**
         * Getting declared actions
         */
        if($this->entityConfig) {
            $this->declaredActions = [];
    
            foreach($this->entityConfig->actions as $key => $value) {
                if(!is_object($value) && !$value) {
                    $this->declaredActions[] = '-' . $key;
                }
            }
        }

        /**
         * Setting twig variables and partials
         */
        $args['__css__']     = WEB_ROOT . APP . 'carnival' . CSS;
        $args['__scripts__'] = WEB_ROOT . APP . 'carnival' . SCRIPTS;
        $args['__img__']     = WEB_ROOT . APP . 'carnival' . IMG;
        $args['user']        = (array)LampionSession::get('user');
        $args['title']       = $this->title;
        
        foreach($this->config->entities as $key => $entity) {
            $args['entities'][] = [
                'name'   => $key,
                'icon'   => $entity->icon ?? null,
                'title'  => $entity->title ?? $key,
                'active' => $this->entityName == $key ? true : false,
                'type'   => $entity->nav_section ?? 'entity'
            ];
        }

        $this->header = $this->view->load('partials/header', $args);
        $this->nav    = $this->view->load('partials/nav'   , $args);
        $this->footer = $this->view->load('partials/footer', $args);
    }

    public function registerRoutes(Router $router) {
        # Getting all entities
        $entities = array_keys((array)$this->config->entities);

        # Default actions
        $defaultActions = [
            'list',
            'new',
            'edit',
            'delete',
            'show'
        ];

        # Check for declared actions in carnival.json
        if($this->declaredActions) {
            foreach($defaultActions as $action) {
                if(!in_array($action, $this->declaredActions)) {
                    if(in_array('-' . $action, $this->declaredActions)) {
                        unset($this->declaredActions[array_search('-' . $action, $this->declaredActions)]);
                    }
    
                    else {
                        $this->declaredActions[] = $action;
                    }
                }
            }
        }

        # If there are no declared actions for entity, use the default ones
        else {
            $this->declaredActions = $defaultActions;
        }

        foreach($entities as $entity) {
            # Register default action
            $router->get($entity, 'Carnival\\Admin\Action\\Admin\\' . ucfirst($this->defaultAction) . 'Action::display');

            # Register entity route, and all it's actions
            if($this->declaredActions) {
                foreach($this->declaredActions as $action) {
                    $router->get($entity . '/' . $action, 'Carnival\\Admin\\Action\\Admin\\' . ucfirst($action) . 'Action::display');
                    $router->post($entity . '/' . $action, 'Carnival\\Admin\\Action\\Admin\\' . ucfirst($action) . 'Action::submit');
                }
            }
        }
    }

}