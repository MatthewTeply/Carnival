<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\Core\Controller;
use Carnival\Admin\Core\Manager\Timeline as TimelineManager;
use Carnival\Admin\Core\Manager\Translation;
use Carnival\Entity\Language;
use Lampion\Database\Query;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Misc\Util;
use Lampion\Session\Lampion as LampionSession;
use Carnival\Entity\Timeline;

class EditAction extends Controller {

    public $action;
    public $form;
    public $entityId;

    public function __construct() {
        parent::__construct();

        $this->entityId = $this->request->query('id');

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
            'referer'     => $this->referer,
            'hasTranslatables' => Translation::hasTranslatables(new $this->className()),
            'languages'        => $this->em->all(Language::class)
        ]);

        $this->renderTemplate($template);
    }

    public function submit() {
        $fields = $this->entityConfig->fields ?? $this->entityColumns;
        
        $entity = $this->em->find($this->className, $this->request->query('id'));
        $translationChildren = Translation::getChildren($entity);

        if(Translation::getTranslatables($entity)) {
            foreach($this->em->all(Language::class) as $language) {
                // TODO: Better default language implementation
                if($language->code == 1) {
                    continue;
                }
    
                if(!isset($translationChildren->{$language->code})) {
                    $entity = new $this->className();
    
                    $this->persistEntity($entity, $fields, $language);
                }
    
                else {
                    $this->persistEntity($translationChildren->{$language->code}, $fields, $language);
                }
            }
        }

        else {
            $this->persistEntity($entity, $fields, null);
        }

        $timeline = new Timeline();

        $timeline->title       = 'Item edited';
        $timeline->content     = 'edit';
        $timeline->created     = date('Y-m-d H:i:s');
        $timeline->entity_name = $this->className;
        $timeline->entity_id   = $this->request->query('id');
        $timeline->user        = $this->user;
        $timeline->type        = 'info';

        TimelineManager::set($timeline);
        
        if(!$this->request->isAjax()) {
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

    private function persistEntity($entity, $fields, $language) {
        $filledTranslatables = 0;

        foreach($fields as $key => $field) {
            if($field->type == 'boolean') {
                if(!$this->request->hasInput($key)) {
                    $this->request->input($key, 'false');
                }
            }
            
            if(Translation::isTranslatable($key, $this->className)) {
                $fieldValue = json_decode($this->request->input($key), true)[$language->code];

                if(!empty($fieldValue)) {
                    $entity->$key = $fieldValue;
                    $filledTranslatables++;
                }
            }

            else {
                $entity->$key = $this->request->input($key);
            }
        }

        # Files
        foreach($this->request->all()->files as $key => $file) {
            if(!empty($file['name'])) {
                $entity->$key = APP . LampionSession::get('app') . STORAGE . $this->fs->upload($file, '');
            }
        }

        if($filledTranslatables != 0 || !Translation::hasTranslatables(new $this->className)) {
            $this->em->persist($entity);
        
            if($language) {
                if(empty($entity->id)) {
                    Query::insert('translations', [
                        'entity_name' => $this->className,
                        'parent_id'   => $this->em->find($this->className, $this->request->query('id'))->id,
                        'child_id'    => $this->em->lastId(get_class($entity)),
                        'language_id' => $language->id
                    ]);
                }
            }
        }
    }
}