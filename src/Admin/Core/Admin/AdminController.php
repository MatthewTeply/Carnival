<?php

namespace Carnival\Admin\Core\Admin;

use Lampion\View\View;
use Lampion\Core\FileSystem;
use Lampion\Entity\EntityManager;
use Lampion\Database\Query;
use Lampion\Debug\Console;
use Lampion\Language\Translator;
use Lampion\Session\Lampion as LampionSession;

class AdminController extends AdminConfig {

    # Lampion classes
    public $view;
    public $em;
    public $fs;
    public $translator;

    # Config
    public $config;
    public $entityConfig;

    # Entity
    public $entityName;
    public $className;
    public $table;
    public $entityColumns;
    public $title;
    public $action;
    public $defaultAction;
    public $declaredActions;

    # Twig partials
    public $header;
    public $nav;
    public $footer;
    
    public function __construct() {
        # Getting config file's JSON, and turning it into an object
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/admin.json'));

        # Initial config
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');
        $this->fs   = new FileSystem();
        $this->em   = new EntityManager();

        # Setting up the translator
        $this->translator = new Translator(LampionSession::get('lang'));

        # Entity variables
        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;
        
        # DB config
        $this->table = strtolower($this->entityName);
        $this->table = Query::tableExists($this->table) ? $this->table : 'entity_' . $this->table;

        # Getting entity's DB columns
        $this->configColumns();

        # Entity config
        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;

        # Getting declared actions
        if(isset($this->entityConfig->actions)) {
            $this->declaredActions = [];
    
            foreach($this->entityConfig->actions as $key => $value) {
                if(!is_object($value) && !$value) {
                    $this->declaredActions[] = '-' . $key;
                }
            }
        }

        # Setting the defualt action
        $this->action = $this->configDefaultAction();

        # Setting title
        $this->configTitle($this->action);

        # Setting twig variables and partials
        $this->configTwig();
    }

    public function constructForm(&$form, $entity = null) {
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
                    'placeholder' => $this->translator->read('entity/' . strtolower($this->entityName))->get($column->name)
                ]
            ]);
        }
    
        $action_label = $this->entityConfig->action_label ?? null;

        $form->field('button', [
            'name'  => $this->entityName . '_submit',
            'label' => $action_label ?? 'Submit',
            'class' => 'btn btn-yellow',
            'type'  => 'submit'
        ]);
    }
}