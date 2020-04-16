<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\AdminCore;
use Lampion\Http\Url;

class DeleteAction extends AdminCore {

    public function submit() {
        $entity = new $this->className($_GET['id']);

        $entity->destroy();

        Url::redirect($this->entityName, [
            'success' => 'delete'
        ]);
    }

}