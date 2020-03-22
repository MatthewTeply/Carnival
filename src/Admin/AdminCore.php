<?php

namespace Carnival\Admin;

use Error;
use Lampion\Core\FileSystem;
use Lampion\Core\Router;
use Lampion\Database\Query;
use Lampion\Form\Form;
use Lampion\Http\Url;
use Lampion\Misc\Util;
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

    public $fs;

    public function __construct() {
        $this->view = new View(ROOT . APP . 'carnival' . TEMPLATES, 'carnival');
        $this->fs = new FileSystem();

        $args['__css__']     = WEB_ROOT . APP . 'carnival' . CSS;
        $args['__scripts__'] = WEB_ROOT . APP . 'carnival' . SCRIPTS;
        $args['__img__']     = WEB_ROOT . APP . 'carnival' . IMG;
        $args['user']        = (array)LampionSession::get('user');

        $this->header = $this->view->load('partials/header', $args);
        $this->nav    = $this->view->load('partials/nav',    $args);
        $this->footer = $this->view->load('partials/footer', $args);

        $this->config = json_decode(file_get_contents(ROOT . APP . 'carnival/' . CONFIG . 'carnival.json'));

        $this->entityName = explode('/', $_GET['url'])[0];
        $this->className = 'Carnival\Entity\\' . $this->entityName;
        $this->table = strtolower($this->entityName);

        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;
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
            $router->get($entity, 'Carnival\Admin\AdminCore::listAction');

            # Register entity route, and all it's actions
            foreach($actions as $action) {
                $router->get($entity . '/' . $action, 'Carnival\Admin\AdminCore::' . explode('/{', $action)[0] . 'Action');
                $router->post($entity . '/' . $action, 'Carnival\Admin\AdminCore::' . explode('/{', $action)[0] . 'ActionSubmit');
            }
        }
    }

    public function listAction() {
        # If entity's name is reserved in SQL, try entity prefix
        $ids = Query::select(Query::tableExists($this->table) ? $this->table : 'entity_' . $this->table, ['id']);

        $entities = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = new $this->className($id['id']);

            $entityConfig = $this->entityConfig->list;

            if(isset($entityConfig->fields)) {
                $columns = array_keys((array)$entityConfig->fields);

                foreach($columns as $column) {
                    $type = $entityConfig->fields->$column->type ?? null;

                    if($type) {
                        $entity->{$column} = $this->view->load('admin/types/' . $type, [
                            'value' => $entity->{$column}
                        ]);
                    }
                }
            }

            else {
                $columns = array_keys(get_object_vars($entity));
            }

            foreach($columns as $col_key => $column) {
                $columns[$col_key] = [
                    'name' => $column,
                    'label' => $entityConfig->fields->$column->label ?? $column
                ];
            }

            foreach($columns as $column) {
                try {
                    $entities[$key][$column['name']] = $entity->{$column['name']};
                }

                catch(Error $e) {
                    // TODO: Error handling
                }
            }

            $entities[$key]['id'] = $entity->id;
        }

        $this->view->render('admin/actions/list', [
            'columns'    => $columns ?? array_keys((array)$this->entityConfig->list->fields),
            'entities'   => $entities,
            'title'      => $entityConfig->title ?? $this->entityName,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function newAction() {
        $action = Url::link($this->entityName) . '/new';
        $entityConfig = $this->entityConfig->new;

        $form = new Form($action, 'POST');

        foreach($entityConfig->fields as $fieldName => $field) {
            $form
            ->field($field->type, [
                'name' => $fieldName,
                'label' => $field->label ?? null,
                'attr' => [
                    'id' => $field->id ?? null,
                    'class' => $field->class ?? null,
                    'placeholder' => $field->label ?? null
                ]
            ]);
        }

        $action_label = $entityConfig->action_label ?? null;

        $form->field('button', [
            'name'  => $this->entityName . '_submit',
            'label' => $action_label ?? 'Submit',
            'class' => 'yellow-button',
            'type'  => 'submit'
        ]);

        $this->view->render('admin/actions/new', [
            'form'       => $form,
            'title'      => $entityConfig->title ?? $this->entityName,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function newActionSubmit() {
        $entityConfig = $this->entityConfig->new;

        $fields = array_keys((array)$entityConfig->fields);

        $entity = new $this->className();

        # Post
        foreach($fields as $field) {
            $entity->$field = $_POST[$field];
        }

        # Files
        foreach($_FILES as $key => $file) {
            if(!empty($file)) {
                $entity->$key = APP . LampionSession::get('app') . STORAGE . $this->fs->upload($file, '');
            }
        }

        $entity->persist();

        Url::redirect($this->entityName, [
            'success' => 'new'
        ]);
    }

    public function editAction() {
        $entity_id = $_GET['Request']->params['id'];

        $action = Url::link($this->entityName) . '/edit/' . $entity_id;
        $entityConfig = $this->entityConfig->edit;

        $form = new Form($action, 'POST');

        $entity = new $this->className($entity_id);

        foreach($entityConfig->fields as $fieldName => $field) {
            if(Util::validateJson(htmlspecialchars_decode($entity->$fieldName))) {
                $value = json_decode(htmlspecialchars_decode($entity->$fieldName), true);
            }

            else {
                $value = $entity->$fieldName;
            }

            $form
            ->field($field->type, [
                'name' => $fieldName,
                'label' => $field->label ?? null,
                'value' => $value,
                'attr' => [
                    'id' => $field->id ?? null,
                    'class' => $field->class ?? null,
                    'placeholder' => $field->label ?? null
                ]
            ]);
        }

        $action_label = $entityConfig->action_label ?? null;

        $form->field('button', [
            'name'  => $this->entityName . '_submit',
            'label' => $action_label ?? 'Submit',
            'class' => 'yellow-button',
            'type'  => 'submit'
        ]);

        $this->view->render('admin/actions/edit', [
            'form'       => $form,
            'title'      => $entityConfig->title ?? $this->entityName,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function editActionSubmit() {
        $entityConfig = $this->entityConfig->new;

        $fields =$entityConfig->fields;

        $entity = new $this->className($_GET['Request']->params['id']);

        foreach($fields as $key => $field) {
            if($field->type == 'boolean') {
                if(!isset($_POST[$key])) {
                    $_POST[$key] = 'false';
                }
            }

            $entity->$key = $_POST[$key];
        }

        # Files
        foreach($_FILES as $key => $file) {
            if(!empty($file['name'])) {
                $entity->$key = APP . LampionSession::get('app') . STORAGE . $this->fs->upload($file, '');
            }
        }

        $entity->persist();

        Url::redirect($this->entityName, [
            'success' => 'edit'
        ]);
    }

    public function deleteAction() {
        $entity = new $this->className($_GET['Request']->params['id']);

        $entity->destroy();

        Url::redirect($this->entityName, [
            'success' => 'delete'
        ]);
    }
}