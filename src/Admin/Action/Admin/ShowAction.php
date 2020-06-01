<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Debug\Console;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Misc\Util;
use Lampion\Session\Lampion as LampionSession;
use stdClass;

class ShowAction extends AdminController {

    public $action;
    public $form;
    public $entityId;

    public function __construct() {
        parent::__construct();

        $this->entityId = $_GET['id'];

        $this->action = '';
        $this->entityConfig = $this->entityConfig->actions->show ?? null;

        $this->form = new Form($this->action, 'POST', true);
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
                    'name'  => $fieldName,
                    'label' => $field->label ?? null,
                    'value' => $value,
                    'attr'  => $field->attr ?? new stdClass()
                ];

                if(isset($field->field_options)) {
                    foreach($field->field_options as $key => $value) {
                        $options[$key] = $value;
                    }
                }

                $options['attr']->disabled = true;

                $this->form->field($field->type, $options);
            }

            $action_label = $this->entityConfig->action_label ?? null;
        }

        else {
            $this->constructForm($this->form, $entity);
        }

        $template = $this->view->load('admin/actions/show', [
            'form'        => $this->form,
            'title'       => $this->title,
            'entityName'  => $this->entityName,
            'entityId'    => $this->entityId,
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'icon'        => $this->config->entities->{$this->entityName}->icon ?? null,
            'description' => $this->description,
            'user'        => $this->user,
            'referer'     => $this->referer
        ]);

        $this->renderTemplate($template);
    }

}