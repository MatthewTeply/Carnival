<?php

namespace Carnival\Admin\Core\Admin;

use Carnival\Entity\User;
use Lampion\Application\Application;
use Lampion\View\View;
use Lampion\Core\FileSystem;
use Lampion\Entity\EntityManager;
use Lampion\Database\Query;
use Lampion\Debug\Console;
use Lampion\Http\Request;
use Lampion\Http\Response;
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
    public $entityConfigDir;

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

    # Twig
    public $twigArgs;

    # Ajax
    public $ajax;
    public $response;
    
    public function __construct() {
        # Getting config file's JSON, and turning it into an object
        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival/admin.json'));

        # Is request ajax?
        $this->ajax = Request::isAjax();

        $this->response = new Response();

        # Initial config
        $this->view = new View(ROOT . APP . Application::name() . TEMPLATES, 'carnival');
        $this->fs   = new FileSystem(ROOT . APP . Application::name() . '/');
        $this->em   = new EntityManager();

        # Getting currently logged in user
        $this->user = $this->em->find(User::class, unserialize(LampionSession::get('user'))->id);

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
        $this->configEntity();

        # Setting the defualt action
        $this->configDefaultAction();

        # Setting title
        $this->configTitle();

        # Setting twig variables and partials
        $this->configTwig();

        # Setting permission
        $this->configPermissions();
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
                'name'     => $column->name,
                'type'     => $column->type,
                'label'    => $column->name,
                'value'    => $entity->{$column->name} ?? null,
                'metadata' => $column->metadata,
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
            'type'  => 'submit',
            'attr' => [
                'class' => 'btn btn-yellow'
            ]
        ]);
    }

    public function renderTemplate($template) {
        if($this->ajax) {
            $this->response->json($returnArr = [
                'template' => htmlspecialchars($template),
                'title'    => $this->title
            ]);
        }

        else {
            $this->response->send($template);
        }
    }
}