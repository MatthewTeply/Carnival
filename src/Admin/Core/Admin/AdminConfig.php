<?php

namespace Carnival\Admin\Core\Admin;

use Carnival\Entity\User;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Database\Query;
use Lampion\Debug\Console;
use Lampion\Application\Application;
use Lampion\Core\FileSystem;

class AdminConfig {

    protected $translator;

    protected function configEntity() {
        $this->entityConfigDir = ROOT . APP . Application::name() . '/' . CONFIG . 'carnival/admin/entity/';

        # Adding config fron entity files to main config file's entities
        foreach($this->fs->ls('config/carnival/admin/entity/')['files'] as $file) {
            $entityName = ucfirst(explode('.' . $file['extension'], $file['name'])[0]);

            $this->config->entities->{$entityName} = json_decode(file_get_contents($this->entityConfigDir . $file['name']))->{$entityName};
        }

        $this->entityConfig = is_object($this->config) && isset($this->config->entities->{$this->entityName}) ? $this->config->entities->{$this->entityName} : null;

        # Getting declared actions
        if(isset($this->entityConfig->actions)) {
            $this->declaredActions = [];
    
            foreach($this->entityConfig->actions as $key => $value) {
                if(!is_object($value) && !$value) {
                    $this->declaredActions[] = '-' . $key;
                }

                else {
                    $this->declaredActions[$key] = $value;
                }
            }
        }
    }
    
    protected function configTwig() {
        $args['__css__']     = WEB_ROOT . APP . 'carnival' . CSS;
        $args['__scripts__'] = WEB_ROOT . APP . 'carnival' . SCRIPTS;
        $args['__img__']     = WEB_ROOT . APP . 'carnival' . IMG;
        $args['user']        = (array)LampionSession::get('user');
        $args['title']       = $this->title;
        $args['logo']        = $this->config->logo;
        
        foreach($this->config->entities as $key => $entity) {
            # Check if entity's list action has set permission
            if(isset($entity->actions->list->permission)) {
                if(!$this->user->hasPermission($entity->actions->list->permission)) {
                    continue;
                }
            }

            else {
                if(isset($entity->permission)) {
                    if(!$this->user->hasPermission($entity->permission)) {
                        continue;
                    }
                }
            }

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

        $this->view->setFilter('hasPermission', function(User $user, $action = null) {
            if(!$action) {
                $action = $this->action;
            }

            if(isset($this->entityConfig->actions->{$action}->permission)) {
                return $user->hasPermission($this->entityConfig->actions->{$action}->permission);
            }

            else {
                return @$user->hasPermission($this->entityConfig->permission);
            }
        });

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

        $this->action = explode('/', explode($this->entityName, $_GET['url'])[1])[1] ?? $this->defaultAction;
    }

    protected function configTitle() {
        if(isset($this->entityConfig->actions->{$this->action}->title)) {
            $this->title = $this->entityConfig->actions->{$this->action}->title;
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

    protected function configPermissions() {
        $permissions = null;

        # Check entity's permission
        if(isset($this->entityConfig->permission)) {
            $permissions = $this->entityConfig->permission;
        }

        # Check action's permission, action permission overwrites entity's permission
        if(isset($this->declaredActions[$this->action]->permission)) {
            $permissions = $this->declaredActions[$this->action]->permission;
        }

        # Check user's permission
        if(!$this->user->hasPermission($permissions)) {
            # If user doesn't have sufficent privileges, display error
            $this->view->render('admin/errors/actionDenied', [
                'header' => $this->header,
                'nav'    => $this->nav,
                'footer' => $this->footer
            ]);

            exit();
        }
    }
}