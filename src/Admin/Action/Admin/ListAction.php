<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Carnival\Entity\Article;
use Error;
use Lampion\Database\Query;
use Lampion\Debug\Console;

class ListAction extends AdminController {
    public function display() {
        $metadata = $this->em->metadata($this->className);

        // TODO: Default limit, currently fixed to 25
        $limit = $this->entityConfig->actions->list->limit ?? 25;

        $sortBy    = $this->entityConfig->actions->list->sortBy ?? null;
        $sortOrder = $this->entityConfig->actions->list->sortOrder ?? null;

        $sortBy    = $_GET['sortBy'] ?? $sortBy;
        $sortOrder = $_GET['sortOrder'] ?? $sortOrder;

        $sortBy = $metadata->{$sortBy}->mappedBy ?? $sortBy; 

        $sortString = $sortBy ? ' ORDER BY ' . $sortBy . ' ' . $sortOrder : null;
        $queryString = 'SELECT id FROM ' . $this->table . $sortString . ' LIMIT ' . $limit . ' OFFSET ' . (isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0);

        # If entity's name is reserved in SQL, try entity prefix
        $ids = Query::raw($queryString);

        $entityCount = Query::select($this->table, ['COUNT(*)'])[0]['COUNT(*)'];

        $entities = [];
        $columns  = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = $this->em->find($this->className, $id['id']);

            # If fields are set in entity's list action
            if(isset($this->entityConfig->actions->list->fields)) {
                $columns = array_keys((array)$this->entityConfig->actions->list->fields);

                foreach($columns as $colKey => $column) {
                    $permission = $this->entityConfig->actions->list->fields->{$column}->permission ?? null;

                    if(!$this->user->hasPermission($permission)) {
                        unset($columns[$colKey]);
                        continue;
                    }

                    $type = $this->entityConfig->actions->list->fields->{$column}->type ?? null;
                    $value = $entity->{$column};

                    if($type) {
                        $template = $type;

                        # Check if custom template is defined in field's config
                        if(isset($this->entityConfig->actions->list->fields->{$column}->template)) {
                            $template = $this->entityConfig->actions->list->fields->{$column}->template;
                        }

                        $args['__css__']     = $this->twigArgs['__css__'];
                        $args['__scripts__'] = $this->twigArgs['__scripts__'];
                        $args['__img__']     = $this->twigArgs['__img__'];
                        $args['__storage__'] = $this->twigArgs['__storage__'];
                        $args['value']       = $value;
                        $args['entityName']  = $this->entityName;
                        $args['rowIndex']    = $key;
                        $args['colIndex']    = $colKey;

                        $entity->{$column} = $this->view->load('admin/types/' . $template, $args);
                    }
                }
            }

            else {
                $columns = array_keys(get_object_vars($entity));
            }

            foreach($columns as $col_key => $column) {
                $columns[$col_key] = [
                    'name' => $column,
                    'label' => $this->entityConfig->actions->list->fields->$column->label ?? $column,
                    'sortOrder' => $sortOrder == 'DESC' ? 'ASC' : 'DESC'
                ];
            }

            foreach($columns as $column) {
                $entities[$key][$column['name']] = $entity->{$column['name']};
            }

            $entities[$key]['id'] = $entity->id;
        }

        $this->view->render('admin/actions/list', [
            'entity'       => $this->entityConfig,
            'user'         => $this->user,
            'action'       => $this->entityConfig->actions->list,
            'columns'      => $columns ?? array_keys((array)$this->entityConfig->actions->list->fields),
            'listEntities' => $entities,
            'resultsCount' => $entityCount,
            'page'         => $_GET['page'] ?? 1,
            'pagesCount'   => ceil($entityCount/$limit),
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
            'sortBy'      => $_GET['sortBy'] ?? $sortBy,
            'sortOrder'   => $sortOrder,
            'description' => $this->entityConfig->description ?? null,
            'new'         => isset($this->entityConfig->actions->new)    ? is_object($this->entityConfig->actions->new)    : true,
            'edit'        => isset($this->entityConfig->actions->edit)   ? is_object($this->entityConfig->actions->edit)   : true,
            'delete'      => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true
        ]);
    }

}