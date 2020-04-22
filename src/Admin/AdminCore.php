<?php

namespace Carnival\Admin;

use Lampion\Core\Router;
use Lampion\Database\Query;
use Lampion\Debug\Console;

class AdminCore {

    public $className;
    public $entityName;
    public $table;
    public $entityConfig;
    public $config;
    public $defaultAction;
    public $declaredActions;
    public $action;

    public function __construct() {
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/admin.json'));

        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        
        $this->table      = strtolower($this->entityName);
        $this->table      = Query::tableExists($this->table) ? $this->table : 'entity_' . $this->table;

        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;

        # Setting the defualt action
        $this->action = $this->configDefaultAction();

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
                $defaultActionClass = 'Carnival\\Admin\Action\\Admin\\' . ucfirst($this->defaultAction) . 'Action';
            }

            # Register default action
            $router->get($entity, $defaultActionClass . '::display');

            # Register entity route, and all it's actions
            if($this->declaredActions) {
                foreach($this->declaredActions as $action) {
                    if(isset($this->entityConfig->actions->$action->controller)) {
                        $actionCustomClassGet = $this->entityConfig->actions->$action->controller  . 'Get';
                        $actionCustomClassPost = $this->entityConfig->actions->$action->controller . 'Post';
                    }

                    else {
                        $actionClassGet = 'Carnival\\Admin\\Action\\Admin\\' . ucfirst($action)  . 'Action::display';
                        $actionClassPost = 'Carnival\\Admin\\Action\\Admin\\' . ucfirst($action)  . 'Action::submit';
                    }
                    
                    $router->get($entity . '/' . $action, $actionCustomClassGet   ?? $actionClassGet);
                    $router->post($entity . '/' . $action, $actionCustomClassPost ?? $actionClassPost);
                }
            }
        }
    }

    private function configDefaultAction() {
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

        return explode('/', explode($this->entityName, $_GET['url'])[1])[1] ?? $this->defaultAction;
    }
}