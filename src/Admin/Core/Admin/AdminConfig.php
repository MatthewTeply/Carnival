<?php

namespace Carnival\Admin\Core\Admin;

use Carnival\Entity\User;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Database\Query;
use Lampion\Debug\Console;
use Lampion\Application\Application;
use Lampion\Entity\EntityManager;

class AdminConfig {

    protected $translator;

    protected function configEntity() {
        $this->entityConfigDir = ROOT . APP . Application::name() . '/' . CONFIG . 'carnival/admin/entity/';

        # Adding config fron entity files to main config file's entities
        foreach($this->fs->ls('config/carnival/admin/entity/')['files'] as $file) {
            $entityName = ucfirst(explode('.' . $file['extension'], $file['filename'])[0]);

            $this->config->entities->{$entityName} = json_decode(file_get_contents($this->entityConfigDir . $file['filename']))->{$entityName} ?? null;
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
        $this->twigArgs['__css__']     = WEB_ROOT . APP . Application::name() . CSS;
        $this->twigArgs['__scripts__'] = WEB_ROOT . APP . Application::name() . SCRIPTS;
        $this->twigArgs['__img__']     = WEB_ROOT . APP . Application::name() . IMG;
        $this->twigArgs['__storage__'] = WEB_ROOT . APP . Application::name() . STORAGE;

        $args['__css__']      = $this->twigArgs['__css__'];
        $args['__scripts__']  = $this->twigArgs['__scripts__'];
        $args['__img__']      = $this->twigArgs['__img__'];
        $args['__storage__']  = $this->twigArgs['__storage__'];
        $args['user']         = $this->user;
        $args['title']        = $this->title;
        $args['logo']         = $this->config->logo;
        $args['breadcrumbs']  = explode('/', $_GET['url']);
        $args['entityName']   = $this->entityName;
        $args['entityConfig'] = $this->entityConfig;
        $args['webroot']      = WEB_ROOT;
        $args['app']          = [
            'name'      => Application::name(),
            'isDefault' => Application::name() == DEFAULT_APP
        ];
        
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

        $this->view->setFilter('hasPermission', function($user, $action = null) {
            # If user class is Lampion user class, convert it to Carnival user class
            if(get_class($user) != User::class) {
                $em = new EntityManager();

                $user = $em->find(User::class, $user->id);

                if(!$user) {
                    //TODO: Error handling
                    return false;
                }
            }

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

        $this->view->setFilter('getClass', function($object) {
            return get_class($object);
        });

        if(!$this->ajax) {
            $this->header = $this->view->load('partials/header', $args);
            $this->nav    = $this->view->load('partials/nav'   , $args);
            $this->footer = $this->view->load('partials/footer', $args);
        }
    }

    protected function configColumns() {
        $columns  = Query::raw('DESCRIBE `' . $this->table . '`');
        $metadata = $this->em->metadata($this->className) ?? [];

        foreach($columns as $key => $column) {
            $columns[$column['Field']] = $column;
            unset($columns[$key]);
        }

        /*
        foreach($columns as $column) {
            if($column['Field'] === 'id') {
                continue;
            }

            preg_match_all('/[a-zA-Z]+/', $column['Type'], $type);
            preg_match_all('/\d+/', $column['Type'], $length);

            $colName = $metadata->{$column['Field']}->mappedBy ?? $column['Field'];

            $this->entityColumns[$colName] = new \stdClass();

            // TODO: Maybe assign metadata values before doing regex? Could save a bit of comp time.
            $this->entityColumns[$colName]->name   = $column['Field'];
            $this->entityColumns[$colName]->type   = $metadata->{$column['Field']}->type   ?? $type[0][0];
            $this->entityColumns[$colName]->length = $metadata->{$column['Field']}->length ?? $length[0][0] ?? null;
        }
        */

        foreach($metadata as $key => $value) {
            if($key === 'id') {
                continue;
            }

            $column = $columns[$value->mappedBy ?? $key] ?? null;

            if(!$column) {
                continue;
            }

            preg_match_all('/[a-zA-Z]+/', $column['Type'], $type);
            preg_match_all('/\d+/', $column['Type'], $length);

            $this->entityColumns[$key] = new \stdClass();

            // TODO: Maybe assign metadata values before doing regex? Could save a bit of comp time.
            $this->entityColumns[$key]->name     = $key;
            $this->entityColumns[$key]->type     = $value->type   ?? $type[0][0];
            $this->entityColumns[$key]->length   = $value->length ?? $length[0][0] ?? null;
            $this->entityColumns[$key]->metadata = $value ?? null;
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

    protected function configDescription() {
        if(isset($this->entityConfig->actions->{$this->action}->description)) {
            $this->description = $this->entityConfig->actions->{$this->action}->description;
        }

        else {
            if(isset($this->entityConfig->description)) {
                $this->description = $this->entityConfig->description;                
            }

            else {
                $this->description = null;
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
            $template = $this->view->load('admin/errors/actionDenied', [
                'header'        => $this->header,
                'nav'           => $this->nav,
                'footer'        => $this->footer,
                'entityName'    => $this->entityName,
                'title'         => $this->title,
                'icon'          => $this->entityConfig->icon ?? null,
                'description'   => $this->description ?? null,
                'user'          => $this->user,
                'defaultAction' => $this->entityConfig->default_action ?? 'list'
            ]);

            if(method_exists($this, 'renderTemplate')) {
                $this->renderTemplate($template);
            }

            else {
                echo $template;
            }

            exit();
        }
    }
}