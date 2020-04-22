<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Carnival\Entity\Article;
use Error;
use Lampion\Database\Query;
use Lampion\Debug\Console;

class ListAction extends AdminController {
    public function display() {
        $limit = $this->entityConfig->actions->list->limit ?? 25;

        # If entity's name is reserved in SQL, try entity prefix
        $ids = Query::raw('SELECT id FROM ' . $this->table . ' LIMIT ' . $limit . ' OFFSET ' . (isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0));

        $entityCount = Query::select($this->table, ['COUNT(*)'])[0]['COUNT(*)'];

        $entities = [];
        $columns  = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = $this->em->find($this->className, $id['id']);

            if(isset($this->entityConfig->actions->list->fields)) {
                $columns = array_keys((array)$this->entityConfig->actions->list->fields);

                foreach($columns as $column) {
                    $type = $this->entityConfig->actions->list->fields->$column->type ?? null;
                    $methodName = 'get' . ucfirst($column);

                    # Check if column has a getter defined in entity
                    if(method_exists($entity, $methodName)) {
                        $value = $entity->$methodName();
                    }

                    else {
                        $value = $entity->{$column};
                    }

                    if($type) {
                        $template = $type;

                        # Check if custom template is defined in field's config
                        if(isset($this->entityConfig->actions->list->fields->{$column}->template)) {
                            $this->entityConfig->actions->list->fields->{$column}->template;
                        }

                        $entity->{$column} = $this->view->load('admin/types/' . $template, [
                            'value'      => $value,
                            'entityName' => $this->entityName
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
                    'label' => $this->entityConfig->actions->list->fields->$column->label ?? $column
                ];
            }

            foreach($columns as $column) {
                try {
                    $methodName = 'get' . ucfirst($column['name']);

                    # Check if column has a getter defined in entity
                    if(method_exists($entity, $methodName)) {
                        $entities[$key][$column['name']] = $entity->$methodName();
                    }

                    else {
                        $entities[$key][$column['name']] = $entity->{$column['name']};
                    }
                }

                catch(Error $e) {
                    // TODO: Error handling
                }
            }

            $entities[$key]['id'] = $entity->id;
        }

        $this->view->render('admin/actions/list', [
            'entity'       => $this->entityConfig,
            'user'         => $this->user,
            'action'       => $this->entityConfig->actions->{$this->action},
            'columns'      => $columns ?? array_keys((array)$this->entityConfig->actions->list->fields),
            'listEntities' => $entities,
            'resultsCount' => $entityCount,
            'page'         => $_GET['page'] ?? 1,
            'pagesCount'   => floor($entityCount / $limit) > 0 ? floor($entityCount / $limit) : 1,
            'title'        => $this->title,
            'entityName'   => $this->entityName,
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
            'labels'       => [
                'new'      => $this->entityConfig->actions->new->label    ?? null,
                'delete'   => $this->entityConfig->actions->delete->label ?? null,
                'edit'     => $this->entityConfig->actions->edit->label   ?? null
            ],
            'new'    => isset($this->entityConfig->actions->new)    ? is_object($this->entityConfig->actions->new)    : true,
            'edit'   => isset($this->entityConfig->actions->edit)   ? is_object($this->entityConfig->actions->edit)   : true,
            'delete' => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true
        ]);
    }

}