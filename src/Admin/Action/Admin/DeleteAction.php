<?php

namespace Carnival\Admin\Action\Admin;

use Carnival\Admin\Core\Admin\AdminController;
use Carnival\Entity\User;
use Lampion\Debug\Console;
use Lampion\Http\Url;

class DeleteAction extends AdminController {

    public function display() {
        $entity = $this->em->find($this->className, $this->request->query('id'));

        $this->em->destroy($entity);

        if(!$this->request->isAjax()) {
            Url::redirect($this->entityName, [
                'success' => 'delete'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link($this->entityName, [
                    'success' => 'delete'
                ])
            ]);
        }
    }

}