<?php

namespace Carnival\Admin\Action;

use Carnival\Admin\Core\Controller;

use Carnival\Entity\Timeline;

use Carnival\Admin\Core\Manager\Translation;
use Carnival\Admin\Core\Manager\Timeline as TimelineManager;

use Closure;

use Lampion\Database\Query;

class ListAction extends Controller {
    public function display() {
        $metadata = $this->em->metadata($this->className);

        // TODO: Default limit, currently fixed to 25
        $limit = $this->entityConfig->actions->list->limit ?? 25;

        $sortBy    = $this->entityConfig->actions->list->sortBy    ?? null;
        $sortOrder = $this->entityConfig->actions->list->sortOrder ?? null;

        $sortBy    = $this->request->query('sortBy')    ?? $sortBy;
        $sortOrder = $this->request->query('sortOrder') ?? $sortOrder;

        $sortBy = $metadata->{$sortBy}->mappedBy ?? $sortBy; 

        $sortString = $sortBy ? ' ORDER BY ' . $sortBy . ' ' . $sortOrder : null;
        $queryString = 'SELECT id FROM ' . $this->table . $sortString . ' LIMIT ' . $limit . ' OFFSET ' . ($this->request->hasQuery('page') ? ($this->request->query('page') - 1) * $limit : 0);

        # If entity's name is reserved in SQL, try entity prefix
        $ids = $this->request->query('ids') ?? Query::raw($queryString);

        $entityCount = Query::select($this->table, ['COUNT(*)'])[0]['COUNT(*)'];

        $entities        = [];
        $columns         = [];
        $customTemplates = [];

        foreach($ids as $key => $id) {
            if(!isset($id['id'])) {
                continue;
            }

            $entity = $this->em->find($this->className, $id['id']);

            if(!Translation::isParent($entity)) {
                continue;
            }

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

                            # Add custom template to an array, so the css and js can be included automatically
                            if(!in_array($template, $customTemplates)) {
                                $customTemplates[] = $template;
                            }
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

        $timeline = $this->em->findBy(Timeline::class, [
            'entity_name' => $this->className
        ], 'id', 'DESC');

        if($timeline) {
            foreach($timeline as $key => $item) {
                $timeline[$key] = TimelineManager::get($item);
            }
        }

        $template = $this->view->load('admin/actions/list', [
            'entity'       => $this->entityConfig,
            'user'         => $this->user,
            'action'       => $this->entityConfig->actions->list ?? null,
            'columns'      => $columns ?? array_keys((array)$this->entityConfig->actions->list->fields),
            'listEntities' => $entities,
            'resultsCount' => $entityCount,
            'page'         => $this->request->query('page') ?? 1,
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
            'sortBy'          => $this->request->query('sortBy') ?? $sortBy,
            'sortOrder'       => $sortOrder,
            'sortString'      => isset($sortBy) ? '&sortBy=' . ($this->request->query('sortBy') ?? $sortBy) . '&sortOrder=' . $sortOrder : '',
            'description'     => $this->description,
            'new'             => isset($this->entityConfig->actions->new)    ? is_object($this->entityConfig->actions->new)    : true,
            'edit'            => isset($this->entityConfig->actions->edit)   ? is_object($this->entityConfig->actions->edit)   : true,
            'delete'          => isset($this->entityConfig->actions->delete) ? is_object($this->entityConfig->actions->delete) : true,
            'batch'           => $this->entityConfig->batch ?? true,
            'customTemplates' => $customTemplates,
            'timeline'        => $timeline
        ]);

        $this->renderTemplate($template);
    }

}