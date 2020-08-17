<?php

namespace Carnival\Admin;

use Carnival\Admin\Core\Config;
use Lampion\FileSystem\FileSystem;
use Lampion\Http\Router;
use Lampion\FileSystem\Path;
use Lampion\Http\Request;
use Lampion\Http\Response;

class Bootstrap extends Config {

    public $className;
    public $entityName;
    public $entityConfigDir;
    public $table;
    public $entityConfig;
    public $config;
    public $defaultAction;
    public $declaredActions;
    public $action;
    public $fs;
    public $response;

    public function __construct() {
        $this->config = json_decode(file_get_contents(Path::get('config/carnival/admin.json')));

        $this->fs = new FileSystem(Path::get('/'));

        $this->response = new Response();
        $this->request  = new Request();

        $this->entityName = explode('/', $this->request->url())[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        
        $this->table = 'entity_' . strtolower($this->entityName);

        # Setting entity variables
        $this->configEntity();

        # Setting the defualt action
        $this->configDefaultAction();

        # Getting declared actions
        if(isset($this->entityConfig->actions)) {
            $this->declaredActions = [];
    
            foreach($this->entityConfig->actions as $key => $value) {
                if(!is_object($value) && !$value) {
                    $this->declaredActions[] = '-' . $key;
                }

                else {
                    $this->declaredActions[] = $key;
                }
            }
        }
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

        # Check for declared actions in admin.json
        if(!empty($this->declaredActions)) {
            foreach($defaultActions as $action) {
                if(!in_array($action, $this->declaredActions)) {

                    # If -*action* is declared, remove that action from defaultActions
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
            if(isset($this->entityConfig->actions->{$this->defaultAction}->controller)) {
                $defaultActionClass = $this->entityConfig->actions->{$this->defaultAction}->controller . 'Get';
            }
            
            else {
                $defaultActionClass = 'Carnival\\Admin\Action\\' . ucfirst($this->defaultAction) . 'Action';
            }

            # Register default action
            $router->get($entity, $defaultActionClass . '::display');

            # Register entity route, and all it's actions
            if($this->declaredActions) {
                foreach($this->declaredActions as $action) {
                    $actionClassGet  = 'Carnival\\Admin\\Action\\' . ucfirst($action)  . 'Action::display';
                    $actionClassPost = 'Carnival\\Admin\\Action\\' . ucfirst($action)  . 'Action::submit';
                    
                    if(isset($this->entityConfig->actions->$action->controller)) {
                        $actionClassGet  = $this->entityConfig->actions->$action->controller  . 'Get';
                        $actionClassPost = $this->entityConfig->actions->$action->controller . 'Post';
                    }

                    $router->get($entity . '/' . $action, $actionClassGet);
                    $router->post($entity . '/' . $action, $actionClassPost);
                }
            }
        }
    }
}