<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\AdminCore;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Session\Lampion as LampionSession;

class NewAction extends AdminCore {

    public function display() {
        $action = Url::link($this->entityName) . '/new';
        $entityConfig = $this->entityConfig->actions->new;

        $form = new Form($action, 'POST');

        foreach($entityConfig->fields as $fieldName => $field) {
            $form->field($field->type, [
                'name'  => $fieldName,
                'label' => $field->label ?? null,
                'attr'  => [
                    'id'          => $field->id ?? null,
                    'class'       => $field->class ?? null,
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
            'title'      => $this->title,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function submit() {
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

}