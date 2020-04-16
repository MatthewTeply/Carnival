<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\AdminCore;
use Carnival\Entity\User;
use Lampion\Http\Url;

class DeleteAction extends AdminCore {

    public function display() {
        $entity = $this->em->find(User::class, $_GET['id']);

        $this->em->destroy($entity);

        Url::redirect($this->entityName, [
            'success' => 'delete'
        ]);
    }

}