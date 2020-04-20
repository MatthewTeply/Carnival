<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Language\Translator;

class NewAction extends AdminController {

    public $action;
    public $form;

    public function __construct() {
        parent::__construct();

        $this->action = Url::link($this->entityName) . '/new';
        $this->entityConfig = $this->entityConfig->actions->new ?? null;

        $this->form = new Form($this->action, 'POST');
    }

    public function display() {

        # If fields are defined, use them
        if(isset($this->entityConfig->fields)) {
            foreach($this->entityConfig->fields as $fieldName => $field) {
                $this->form->field($field->type, [
                    'name'  => $fieldName,
                    'label' => $field->label ?? null,
                    'attr'  => [
                        'id'          => $field->id ?? null,
                        'class'       => $field->class ?? null,
                        'placeholder' => $field->label ?? null
                    ]
                ]);
            }
            
            $action_label = $this->entityConfig->action_label ?? null;
    
            $this->form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $this->translator->read('entity/' . $this->entityName)->get($action_label) ?? $this->translator->read('global')->get('Submit'),
                'class' => 'yellow-button',
                'type'  => 'submit'
            ]);
        }

        # If fields are not defined, automatically construct the form
        else {
            $this->constructForm($this->form);
        }
        
        $this->view->render('admin/actions/new', [
            'form'       => $this->form,
            'title'      => $this->title,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function submit() {
        $fields = $this->entityConfig->fields ?? array_keys((array)$this->entityColumns);

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

        Url::redirect($this->entityName, [
            'success' => 'new'
        ]);
    }

}