<?php

namespace Carnival\Admin\Core\Admin;

use Carnival\Entity\User;
use Lampion\View\View;
use Lampion\FileSystem\FileSystem;
use Lampion\FileSystem\Path;
use Lampion\Entity\EntityManager;
use Lampion\Database\Query;
use Lampion\Http\Request;
use Lampion\Http\Response;
use Lampion\Http\Url;
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
    public $description;

    # Twig partials
    public $header;
    public $nav;
    public $footer;

    # Twig
    public $twigArgs;

    # Ajax
    public $ajax;
    public $response;

    # Other
    public $referer;
    
    public function __construct() {
        # Getting config file's JSON, and turning it into an object
        $this->config = json_decode(file_get_contents(Path::get('config/carnival/admin.json')));
        
        $this->request  = new Request();
        $this->response = new Response();
        
        # Initial config
        $this->view = new View(Path::get('public/templates'), 'carnival');
        $this->fs   = new FileSystem(Path::get('/'));
        $this->em   = new EntityManager();

        # Getting currently logged in user
        $this->user = $this->em->find(User::class, unserialize(LampionSession::get('user'))->id);

        # Setting up the translator
        $this->translator = new Translator(LampionSession::get('lang'));

        # Entity variables
        $this->entityName = explode('/', $this->request->url())[0];
        $this->className  = 'Carnival\Entity\\' . $this->entityName;

        # Setting referer
        $this->referer = $this->request->referer() ?? Url::link($this->entityName . '/list');
        
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

        # Setting description
        $this->configDescription();

        # Setting twig variables and partials
        $this->configTwig();

        # Setting permission
        $this->configPermissions();
    }

    public function constructForm(&$form, $entity = null) {
        if(!$this->action) {
            $this->configDefaultAction();
        }

        if($entity) {
            $form->field('number', [
                'name'  => 'id',
                'type'  => 'hidden',
                'value' => $entity->id
            ]);
        }

        foreach($this->entityColumns as $column) {
            $attr = [
                'class'       => 'col-lg-12',
                'placeholder' => $this->translator->read('entity/' . strtolower($this->entityName))->get($column->name)
            ];

            if($this->action == 'show') {
                $attr['disabled'] = true;
            }

            $form->field($column->type, [
                'name'     => $column->name,
                'type'     => $column->type,
                'label'    => $column->name,
                'value'    => $entity->{$column->name} ?? null,
                'metadata' => $column->metadata,
                'attr'     => $attr
            ]);
        }
    
        $action_label = $this->entityConfig->action_label ?? null;

        /*
        if($this->action != 'show') {
            $form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $action_label ?? 'Submit',
                'type'  => 'submit',
                'attr' => [
                    'class' => 'btn btn-yellow'
                ]
            ]);
        }
        */
    }

    public function renderTemplate($template) {
        $breadcrumb = [];
        $pages      = explode('/', $this->request->url());

        foreach($pages as $page) {
            $breadcrumb[] = $this->translator->read('partials/nav')->get($page);
        }

        if($this->request->isAjax()) {
            $this->response->json([
                'template'   => htmlspecialchars($template),
                'title'      => $this->title,
                'breadcrumb' => $breadcrumb
            ]);
        }

        else {
            $this->response->send($template);
        }
    }
}