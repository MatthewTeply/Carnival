<?php

namespace Carnival\Admin\Controller\Dev;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Application\Application;
use Lampion\Debug\Console;
use Lampion\Entity\EntityCreator;
use Lampion\FileSystem\FileSystem;
use Lampion\Form\Form;
use Lampion\Http\Url;

class EntityManagerController extends AdminController {

    public $fs;

    public function __construct() {
        parent::__construct();

        $this->fs = new FileSystem(ROOT . APP . Application::name() . APP_VAR . 'entity/');
    }

    public function listGet() {
        $entities = [];

        foreach($this->fs->ls('')['files'] as $entityFile) {
            $json = json_decode(file_get_contents($entityFile['fullPath']), true);

            $entities[array_key_first($json)] = $json[array_key_first($json)];
        }

        $this->renderTemplate($this->view->load('admin/dev/entityManager/list', [
            'entities'     => $entities,
            'entity'       => $this->entityConfig,
            'user'         => $this->user,
            'title'        => $this->title,
            'entityName'   => $this->entityName,
            'description'  => $this->description,
            'resultsCount' => sizeof($entities),
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
            'new'          => isset($this->entityConfig->actions->new)    ? is_object($this->entityConfig->actions->new)    : true,
            'edit'         => isset($this->entityConfig->actions->edit)   ? is_object($this->entityConfig->actions->edit)   : true,
            'delete'       => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true,
        ]));
    }

    public function newGet() {
        $form = new Form(Url::link('EntityManager') . '/new', 'POST', true);

        $form->field('headline', [
            'text' => 'General'
        ]);

        # Title
        $form->field('string', [ 
            'name' => 'title',
            'label' => 'Title',
            'attr' => [
                'placeholder' => 'Title',
                'class' => 'col-12'
            ]
        ]);

        # Icon
        $form->field('icon', [ 
            'name' => 'icon',
            'label' => 'Icon',
            'attr' => [
                'placeholder' => 'Icon',
                'class' => 'col-12'
            ]
        ]);

        # Description
        $form->field('text', [ 
            'name' => 'description',
            'label' => 'Description',
            'attr' => [
                'placeholder' => 'Description',
                'class' => 'col-12'
            ]
        ]);

        # Permission
        $form->field('json', [ 
            'name' => 'permission',
            'label' => 'Permission',
            'attr' => [
                'class' => 'col-12'
            ],
            'choices' => [
                'ROLE_ADMIN'     => 'Administrátor',
                'ROLE_MODERATOR' => 'Moderátor',
                'ROLE_AUTHOR'    => 'Autor',
                'ROLE_USER'      => 'Uživatel'
            ]
        ]);

        $form->field('headline', [
            'text' => 'Fields'
        ]);

        $form->field('entityFields', [
            'name' => 'fields',
            'label' => 'Fields'
        ]);

        $this->renderTemplate($this->view->load('admin/dev/entityManager/new', [
            'form'         => $form,
            'entity'       => $this->entityConfig,
            'user'         => $this->user,
            'title'        => $this->title,
            'entityName'   => $this->entityName,
            'description'  => $this->description,
            'icon'         => $this->config->entities->{$this->entityName}->icon ?? null,
            'referer'      => $this->referer,
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
            'new'          => isset($this->entityConfig->actions->new)    ? is_object($this->entityConfig->actions->new)    : true,
            'edit'         => isset($this->entityConfig->actions->edit)   ? is_object($this->entityConfig->actions->edit)   : true,
            'delete'       => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true,
        ]));
    }

    public function newPost() {
        $fields = json_decode($_POST['fields'], true);

        $entityArray = [
            $_POST['title'] => [
                'general' => [
                    'title'       => $_POST['title'],
                    'icon'        => $_POST['icon'],
                    'description' => $_POST['description'],
                    'permission'  => json_decode($_POST['permission'])
                ],
                'fields' => $fields
            ]
        ];

        $ec = new EntityCreator();
        $this->fs->write($_POST['title'] . '.json', json_encode($entityArray), 0777);

        $ec->create($entityArray);
    }
}