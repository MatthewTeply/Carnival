<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\AdminCore;
use Carnival\Entity\User;
use Lampion\Debug\Console;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Misc\Util;
use Lampion\Session\Lampion as LampionSession;

class EditAction extends AdminCore {

    public $action;
    public $form;
    public $entityId;

    public function __construct() {
        parent::__construct();

        $this->entityId = $_GET['id'];

        $this->action = Url::link($this->entityName) . '/edit?id=' . $this->entityId;
        $this->entityConfig = $this->entityConfig->actions->edit;

        $this->form = new Form($this->action, 'POST');
    }

    public function display() {
        $entity = new $this->className($this->entityId);

        if(isset($this->entityConfig->fields)) {
            foreach($this->entityConfig->fields as $fieldName => $field) {
                if(Util::validateJson(htmlspecialchars_decode($entity->$fieldName))) {
                    $value = json_decode(htmlspecialchars_decode($entity->$fieldName), true);
                }
    
                else {
                    $value = $entity->$fieldName;
                }
    
                $this->form->field($field->type, [
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

            $action_label = $this->entityConfig->action_label ?? null;

            $this->form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $action_label ?? 'Submit',
                'class' => 'yellow-button',
                'type'  => 'submit'
            ]);
        }

        else {
            $this->constructForm($this->form, $entity);
        }

        $this->view->render('admin/actions/edit', [
            'form'       => $this->form,
            'title'      => $this->title,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function submit() {
        $fields = $this->entityConfig->fields ?? $this->entityColumns;
        $entity = $this->em->find(User::class, $_GET['id']);

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

        $this->em->persist($entity);

        Url::redirect($this->entityName, [
            'success' => 'edit'
        ]);
    }

}