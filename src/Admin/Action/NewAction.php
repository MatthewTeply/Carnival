<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\Core\Controller;

use Carnival\Admin\Core\Manager\Timeline as TimelineManager;
use Carnival\Admin\Core\Manager\Translation;

use Carnival\Entity\Timeline;
use Carnival\Entity\Language;

use Lampion\Database\Query;
use Lampion\Http\Url;
use Lampion\Form\Form;
use Lampion\Session\Lampion as LampionSession;

class NewAction extends Controller {

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
    
            /*
            $this->form->field('button', [
                'name'  => $this->entityName . '_submit',
                'label' => $this->translator->read('entity/' . $this->entityName)->get($action_label) ?? $this->translator->read('global')->get('Submit'),
                'class' => 'btn btn-yellow',
                'type'  => 'submit',
                'attr'  => [
                    'class' => 'btn btn-yellow'
                ]
            ]);
            */
        }

        # If fields are not defined, automatically construct the form
        else {
            $this->constructForm($this->form);
        }
        
        $template = $this->view->load('admin/actions/new', [
            'form'             => $this->form,
            'title'            => $this->title,
            'entityName'       => $this->entityName,
            'header'           => $this->header,
            'nav'              => $this->nav,
            'footer'           => $this->footer,
            'icon'             => $this->config->entities->{$this->entityName}->icon ?? null,
            'description'      => $this->description,
            'referer'          => $this->referer,
            'hasTranslatables' => Translation::hasTranslatables(new $this->className()),
            'languages'        => $this->em->all(Language::class)
        ]);

        $this->renderTemplate($template);
    }

    public function submit() {
        $fields = isset($this->entityConfig->fields) ? array_keys((array)$this->entityConfig->fields) : array_keys((array)$this->entityColumns);

        $parentId = null;
        
        $entity    = new $this->className();
        $languages = $this->em->all(Language::class);

        $i = Translation::hasTranslatables($entity) ? 0 : sizeof($languages);

        $filledEntities = 0;

        do {
            $entity = new $this->className();

            $filledTranslatables = 0;
    
            # Post
            foreach($fields as $field) {
                $language = Translation::isTranslatable($field, $this->className) ? $languages[$i] : false;

                if($language) {
                    $fieldValue = json_decode($this->request->input($field), true)[$language->code];

                    if(!empty($fieldValue)) {
                        $entity->$field = $fieldValue;
                        $filledTranslatables++;
                    }
                }
    
                else {
                    $entity->$field = $this->request->input($field);
                }
            }
    
            # Files
            foreach($this->request->all()->files as $key => $file) {
                if(!empty($file)) {
                    $entity->$key = APP . LampionSession::get('app') . STORAGE . $this->fs->upload($file, '');
                }
            }
    
            if($filledTranslatables != 0 || !Translation::hasTranslatables($entity)) {
                $this->em->persist($entity);
                
                if($filledEntities == 0) {
                    $parentId = $this->em->lastId($this->className);
                }
        
                if(Translation::hasTranslatables($entity)) {
                    Query::insert('translations', [
                        'entity_name' => $this->className,
                        'parent_id'   => $parentId,
                        'child_id'    => $this->em->lastId($this->className),
                        'language_id' => $languages[$i]->id
                    ]);
                }

                else {
                    break;
                }

                $filledEntities++;
            }

            $i++;

        } while($i < sizeof($languages));

        $timeline = new Timeline();

        $timeline->title       = 'New item';
        $timeline->content     = 'new';
        $timeline->created     = date('Y-m-d H:i:s');
        $timeline->entity_name = $this->className;
        $timeline->entity_id   = $parentId;
        $timeline->user        = $this->user;
        $timeline->type        = 'success';

        TimelineManager::set($timeline);

        if(!$this->request->isAjax()) {
            Url::redirect($this->entityName, [
                'success' => 'new'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link($this->entityName, [
                    'success' => 'new'
                ]),
                'debug' => [
                    'db' => $_SESSION['Lampion']['DB']
                ]
            ]);
        }
    }

}