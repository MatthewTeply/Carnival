<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Debug\Console;
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

        $this->form = new Form($this->action, 'POST', true);
    }

    public function display() {
        $entity = $this->em->find($this->className, $this->entityId);

        if(isset($this->entityConfig->fields)) {
            foreach($this->entityConfig->fields as $fieldName => $field) {
                if(is_array($entity->$fieldName) || Util::validateJson(htmlspecialchars_decode($entity->$fieldName))) {
                    if(is_array($entity->$fieldName)) {
                        foreach($entity->$fieldName as $fieldValue) {
                            $value[] = json_decode(htmlspecialchars_decode($fieldValue), true);
                        }
                    }

                    else {
                        $value = json_decode(htmlspecialchars_decode($entity->$fieldName), true);
                    }
                }
    
                else {
                    $value = $entity->$fieldName;
                }

                $options = [
                    'name'  => $fieldName,
                    'label' => $field->label ?? null,
                    'value' => $value,
                    'attr'  => $field->attr ?? null
                ];

                if(isset($field->field_options)) {
                    foreach($field->field_options as $key => $value) {
                        $options[$key] = $value;
                    }
                }
    
                $this->form->field($field->type, $options);
            }

            $action_label = $this->entityConfig->action_label ?? null;

            /*
            $this->form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $this->translator->read('entity/' . $this->entityName)->get($action_label) ?? $this->translator->read('global')->get('Submit'),
                'type'  => 'submit',
                'attr' => [
                    'class' => 'btn btn-yellow'
                ]
            ]);
            */
        }

        else {
            $this->constructForm($this->form, $entity);
        }

        $template = $this->view->load('admin/actions/edit', [
            'form'        => $this->form,
            'title'       => $this->title,
            'entityName'  => $this->entityName,
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'icon'        => $this->config->entities->{$this->entityName}->icon ?? null,
            'description' => $this->description,
            'referer'     => $this->referer
        ]);

        $this->renderTemplate($template);
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

        if(!$this->ajax) {
            Url::redirect($this->entityName, [
                'success' => 'edit'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link($this->entityName, [
                    'success' => 'edit'
                ])
            ]);
        }
    }

}