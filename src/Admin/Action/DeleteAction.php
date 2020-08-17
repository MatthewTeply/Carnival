<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\Core\Controller;
use Carnival\Entity\Timeline;
use Lampion\Http\Url;

use Carnival\Admin\Core\Manager\Timeline as TimelineManager;
use Carnival\Admin\Core\Manager\Translation;

class DeleteAction extends Controller {

    public function display() {
        if(!$this->request->query('ids')) {
            $entities = [$this->em->find($this->className, $this->request->query('id'))];
        }

        else {
            $entities = [];

            foreach(explode(',', $this->request->query('ids')) as $id) {
                $entities[] = $this->em->find($this->className, $id);
            }
        }

        foreach($entities as $entity) {
            Translation::deleteChildren($entity);
            TimelineManager::trashEntity($entity);
    
            $this->em->destroy($entity);
    
            $timeline = new Timeline();
    
            $timeline->title       = 'Item deleted';
            $timeline->content     = 'delete';
            $timeline->created     = date('Y-m-d H:i:s');
            $timeline->entity_name = $this->className;
            $timeline->entity_id   = $entity->id;
            $timeline->user        = $this->user;
            $timeline->type        = 'danger';
    
            TimelineManager::set($timeline);
        }
        
        if(!$this->request->isAjax()) {
            Url::redirect($this->entityName, [
                'success' => 'delete'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link($this->entityName, [
                    'success' => 'delete'
                ])
            ]);
        }
    }

}