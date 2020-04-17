<?php

namespace Carnival\Admin\Action\Admin;

use Error;
use Carnival\Admin\AdminCore;
use Lampion\Database\Query;
use Lampion\Debug\Console;

class ListAction extends AdminCore {

    public function __construct() {
        parent::__construct();

        $this->entityConfig = $this->entityConfig->actions->list;
    }

    public function display() {
        $limit = $this->entityConfig->limit ?? 25;

        # If entity's name is reserved in SQL, try entity prefix
        $ids = Query::raw('SELECT id FROM ' . $this->table . ' LIMIT ' . $limit . ' OFFSET ' . (isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0));

        $entityCount = Query::select($this->table, ['COUNT(*)'])[0]['COUNT(*)'];

        Console::log($ids);

        $entities = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = $this->em->find($this->className, $id['id']);

            if(isset($this->entityConfig->fields)) {
                $columns = array_keys((array)$this->entityConfig->fields);

                foreach($columns as $column) {
                    $type = $this->entityConfig->fields->$column->type ?? null;
                    $methodName = 'get' . ucfirst($column);

                    # Check if column has a getter defined in entity
                    if(method_exists($entity, $methodName)) {
                        $value = $entity->$methodName();
                    }

                    else {
                        $value = $entity->{$column};
                    }

                    if($type) {
                        $entity->{$column} = $this->view->load('admin/types/' . $type, [
                            'value' => $value
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
                    'label' => $this->entityConfig->fields->$column->label ?? $column
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
            'columns'      => $columns ?? array_keys((array)$this->entityConfig->actions->list->fields),
            'entities'     => $entities,
            'resultsCount' => sizeof($entities),
            'page'         => $_GET['page'] ?? 1,
            'pagesCount'   => floor($entityCount / $limit) > 0 ? floor($entityCount / $limit) : 1,
            'title'        => $this->title,
            'entityName'   => $this->entityName,
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
            'labels'       => [
                'new'      => $this->entityConfig->actions->new->label ?? null,
                'delete'   => $this->entityConfig->actions->delete->label ?? 'Smazat',
                'edit'     => $this->entityConfig->actions->edit->label ?? 'Upravit'
            ],
            'new'    => isset($this->entityConfig->actions->new) ? is_object($this->entityConfig->actions->new) : true,
            'edit'   => isset($this->entityConfig->actions->edit) ? is_object($this->entityConfig->actions->edit) : true,
            'delete' => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true
        ]);
    }

}