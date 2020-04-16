<?php

namespace Carnival\Admin;

use Lampion\Core\FileSystem;
use Lampion\Core\Router;
use Lampion\View\View;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Database\Query;
use Lampion\Debug\Console;

use Carnival\Entity\User;
use Lampion\Entity\EntityManager;

class AdminCore {

    public $view;
    public $em;

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
    public $action;
    public $entityColumns;

    public $fs;

    public function __construct() {

        # Initial config
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');
        $this->fs   = new FileSystem();
        $this->em   = new EntityManager();
        
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/admin.json'));
        
        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        
        $this->table      = strtolower($this->entityName);
        $this->table      = Query::tableExists($this->table) ? $this->table : 'entity_' . $this->table;

        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;

        # Getting entity's columns
        $this->configColumns();

        # Setting the defualt action
        $this->action = $this->configDefaultAction();

        # Setting title
        $this->configTitle($this->action);

        # Getting declared actions
        if($this->entityConfig) {
            $this->declaredActions = [];
    
            foreach($this->entityConfig->actions as $key => $value) {
                if(!is_object($value) && !$value) {
                    $this->declaredActions[] = '-' . $key;
                }
            }
        }

        # Setting twig variables and partials
        $this->configTwig();
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
        if(!empty($this->declaredActions)) {
            foreach($defaultActions as $this->action) {
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

    private function configTitle($action) {
        if(isset($this->entityConfig->actions->{$action}->title)) {
            $this->title = $this->entityConfig->actions->{$action}->title;
        }

        else {
            if(isset($this->entityConfig->title)) {
                $this->title = $this->entityConfig->title;
            }

            else {
                $this->title = $this->entityName;
            }
        }
    }

    private function configTwig() {
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

    private function configColumns() {
        $columns = Query::raw('DESCRIBE `' . $this->table . '`');

        foreach($columns as $key => $column) {
            preg_match_all('/[a-zA-Z]+/', $column['Type'], $type);
            preg_match_all('/\d+/', $column['Type'], $length);

            $this->entityColumns[$column['Field']] = new \stdClass();

            $this->entityColumns[$column['Field']]->name   = $column['Field'];
            $this->entityColumns[$column['Field']]->type   = $type[0][0];
            $this->entityColumns[$column['Field']]->length = $length[0][0] ?? null;
        }
    }

    public function constructForm(&$form, $entity = null) {
        $user = new User();

        $user->doTest();

        if($entity) {
            $form->field('number', [
                'name'  => 'id',
                'type'  => 'hidden',
                'value' => $entity->id
            ]);
        }

        foreach($this->entityColumns as $column) {
            $form->field($column->type, [
                'name'  => $column->name,
                'type'  => $column->type,
                'label' => $column->name,
                'value' => $entity->{$column->name} ?? null,
                'attr'  => [
                    'class'       => 'col-lg-12',
                    'placeholder' => $column->name
                ]
            ]);
        }
    
        $action_label = $this->entityConfig->action_label ?? null;

        $form->field('button', [
            'name'  => $this->entityName . '_submit',
            'label' => $action_label ?? 'Submit',
            'class' => 'yellow-button',
            'type'  => 'submit'
        ]);
    }
}