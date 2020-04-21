<?php

namespace Carnival\Admin\Core\Admin;

use Lampion\Session\Lampion as LampionSession;
use Lampion\Database\Query;
use Lampion\Debug\Console;

class AdminConfig {

    protected $translator;

    protected function configTwig() {
        $args['__css__']     = WEB_ROOT . APP . 'carnival' . CSS;
        $args['__scripts__'] = WEB_ROOT . APP . 'carnival' . SCRIPTS;
        $args['__img__']     = WEB_ROOT . APP . 'carnival' . IMG;
        $args['user']        = (array)LampionSession::get('user');
        $args['title']       = $this->title;
        
        foreach($this->config->entities as $key => $entity) {
            $args['entities'][] = [
                'name'   => $key,
                'icon'   => $entity->icon ?? null,
                'title'  => $entity->title ?? $key,
                'active' => $this->entityName == $key ? true : false,
                'type'   => $entity->nav_section ?? 'entity'
            ];
        }

        $this->view->setFilter('trans', function($key, $path) {
            return $this->translator->read($path)->get($key);
        });

        /*
        $this->view->setFilter('transEntity', function($key) {
            return $this->translator->read('entity/' . strtolower($this->entityName))->get($key);
        });

        $this->view->setFilter('transGlobal', function($key) {
            return $this->translator->read('global')->get($key);
        });

        $this->view->setFilter('trans', function($key, $test="") {
            return $this->translator->read('action/' . $this->action)->get($key) . $test;
        });
        */ 

        $this->header = $this->view->load('partials/header', $args);
        $this->nav    = $this->view->load('partials/nav'   , $args);
        $this->footer = $this->view->load('partials/footer', $args);
    }

    protected function configColumns() {
        $columns  = Query::raw('DESCRIBE `' . $this->table . '`');
        $metadata = $this->em->metadata($this->className);

        foreach($columns as $column) {
            if($column['Field'] == 'id') {
                continue;
            }

            preg_match_all('/[a-zA-Z]+/', $column['Type'], $type);
            preg_match_all('/\d+/', $column['Type'], $length);

            $this->entityColumns[$column['Field']] = new \stdClass();

            // TODO: Maybe assign metadata values before doing regex? Could save a bit of comp time.
            $this->entityColumns[$column['Field']]->name   = $column['Field'];
            $this->entityColumns[$column['Field']]->type   = $metadata->{$column['Field']}->type   ?? $type[0][0];
            $this->entityColumns[$column['Field']]->length = $metadata->{$column['Field']}->length ?? $length[0][0] ?? null;
        }
    }

    protected function configDefaultAction() {
        if(isset($this->entityConfig->default_action)) {
            $this->defaultAction = $this->entityConfig->default_action;
        }

        else {
            if(isset($this->config->defaultAction)) {
                $this->defaultAction = $this->config->defaultAction;
            }

            else {
                $this->defaultAction = 'list';
            }
        }

        return explode('/', explode($this->entityName, $_GET['url'])[1])[1] ?? $this->defaultAction;
    }

    protected function configTitle($action) {
        if(isset($this->entityConfig->actions->{$action}->title)) {
            $this->title = $this->entityConfig->actions->{$action}->title;
        }

        else {
            if(isset($this->entityConfig->title)) {
                $this->title = $this->entityConfig->title;
            }

            else {
                $this->title = null;
            }
        }
    }

}