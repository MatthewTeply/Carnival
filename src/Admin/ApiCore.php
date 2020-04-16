<?php

namespace Carnival\Admin;

use Lampion\Core\Router;

class ApiCore {

    public $config;
    public $entities;

    public function __construct() {
        $this->config   = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/api.json'));
        $this->entities = $this->config->entities;
    }

    public function registerRoutes(Router $router) {
        foreach ($this->entities as $entity) {
        	$entityConfig = $this->configy->entities->$entity;

			foreach($entityConfig->actions as $action) {
                
            }        	
        }
    }

}