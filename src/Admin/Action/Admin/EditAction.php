<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Misc\Util;
use Lampion\Session\Lampion as LampionSession;

class EditAction extends AdminController {

    public $action;
    public $form;
    public $entityId;

    public function __construct() {
        parent::__construct();

        $this->entityId = $_GET['id'];

        $this->action = Url::link($this->entityName) . '/edit?id=' . $this->entityId;
        $this->entityConfig = $this->entityConfig->actions->edit ?? null;

        $this->form = new Form($this->action, 'POST');
    }

    public function display() {
        $entity = $this->em->find($this->className, $this->entityId);

        if(isset($this->entityConfig->fields)) {
            foreach($this->entityConfig->fields as $fieldName => $field) {
                if(Util::validateJson(htmlspecialchars_decode($entity->$fieldName))) {
                    $value = json_decode(htmlspecialchars_decode($entity->$fieldName), true);
                }
    
                else {
                    $value = $entity->$fieldName;
                }

                $options = [
                    'name' => $fieldName,
                    'label' => $field->label ?? null,
                    'value' => $value,
                    'attr' => [
                        'id' => $field->id ?? null,
                        'class' => $field->class ?? null,
                        'placeholder' => $field->label ?? null
                    ]
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
        $entity = $this->em->find($this->className, $_GET['id']);

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