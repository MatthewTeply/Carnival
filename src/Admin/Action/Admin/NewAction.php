<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Debug\Console;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Language\Translator;
use Lampion\User\Auth;

class NewAction extends AdminController {

    public $action;
    public $form;

    public function __construct() {
        parent::__construct();

        $this->action = Url::link($this->entityName) . '/new';
        $this->entityConfig = $this->entityConfig->actions->new ?? null;

        $this->form = new Form($this->action, 'POST', true);
    }

    public function display() {
        # If fields are defined, use them
        if(isset($this->entityConfig->fields)) {
            foreach($this->entityConfig->fields as $fieldName => $field) {
                $attr = $field->attr;

                if(!isset($attr->placeholder)) {
                    $attr->placeholder = $field->label;
                }

                $options = [
                    'name'  => $fieldName,
                    'label' => $field->label ?? null,
                    'attr'  => $attr
                ];

                if(isset($field->field_options)) {
                    foreach($field->field_options as $key => $value) {
                        $options[$key] = $value;
                    }
                }

                $this->form->field($field->type, $options);
            }
            
            $action_label = $this->entityConfig->action_label ?? null;
    
            $this->form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $this->translator->read('entity/' . $this->entityName)->get($action_label) ?? $this->translator->read('global')->get('Submit'),
                'class' => 'btn btn-yellow',
                'type'  => 'submit',
                'attr'  => [
                    'class' => 'btn btn-yellow'
                ]
            ]);
        }

        # If fields are not defined, automatically construct the form
        else {
            $this->constructForm($this->form);
        }
        
        $template = $this->view->load('admin/actions/new', [
            'form'        => $this->form,
            'title'       => $this->title,
            'entityName'  => $this->entityName,
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'icon'        => $this->config->entities->{$this->entityName}->icon ?? null,
            'description' => $this->config->entities->{$this->entityName}->description ?? null
        ]);

        $this->renderTemplate($template);
    }

    public function submit() {
        $fields = isset($this->entityConfig->fields) ? array_keys((array)$this->entityConfig->fields) : array_keys((array)$this->entityColumns);

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

        $this->em->persist($entity);

        if(!$this->ajax) {
            Url::redirect($this->entityName, [
                'success' => 'new'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link($this->entityName, [
                    'success' => 'new'
                ])
            ]);
        }
    }

}