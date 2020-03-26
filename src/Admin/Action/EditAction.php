<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\AdminCore;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Misc\Util;
use Lampion\Session\Lampion as LampionSession;

class EditAction extends AdminCore {

    public function display() {
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
            'title'      => $this->title,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer
        ]);
    }

    public function submit() {
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

}