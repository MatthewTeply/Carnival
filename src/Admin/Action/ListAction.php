<?php

namespace Carnival\Admin\Action;

use Error;
use Carnival\Admin\AdminCore;
use Lampion\Database\Query;

class ListAction extends AdminCore {

    public function display() {
        # If entity's name is reserved in SQL, try entity prefix
        $ids = Query::select(Query::tableExists($this->table) ? $this->table : 'entity_' . $this->table, ['id']);

        $entities = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = new $this->className($id['id']);

            $entityConfig = $this->entityConfig->actions->list;

            if(isset($entityConfig->fields)) {
                $columns = array_keys((array)$entityConfig->fields);

                foreach($columns as $column) {
                    $type = $entityConfig->fields->$column->type ?? null;

                    if($type) {
                        $entity->{$column} = $this->view->load('admin/types/' . $type, [
                            'value' => $entity->{$column}
                        ]);
                    }
                }
            }

            else {
                $columns = array_keys(get_object_vars($entity));
            }

            foreach($columns as $col_key => $column) {
                $columns[$col_key] = [
                    'name' => $column,
                    'label' => $entityConfig->fields->$column->label ?? $column
                ];
            }

            foreach($columns as $column) {
                try {
                    $entities[$key][$column['name']] = $entity->{$column['name']};
                }

                catch(Error $e) {
                    // TODO: Error handling
                }
            }

            $entities[$key]['id'] = $entity->id;
        }

        $this->view->render('admin/actions/list', [
            'columns'    => $columns ?? array_keys((array)$this->entityConfig->list->fields),
            'entities'   => $entities,
            'title'      => $this->title,
            'entityName' => $this->entityName,
            'header'     => $this->header,
            'nav'        => $this->nav,
            'footer'     => $this->footer,
            'labels'     => [
                'new'   => $this->entityConfig->new->label ?? null,
                'edit'  => $this->entityConfig->edit->label ?? null
            ]
        ]);
    }

}