<?php

namespace Carnival\Admin\Controller;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Application\Application;

class BaseController extends AdminController {

    public function display() {
        $this->view->render('admin/base', [
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
            'webroot'      => WEB_ROOT,
            'app'          => [
                'name' => Application::name(),
                'isDefault' => Application::name() == DEFAULT_APP ? true : false
            ]
        ]);
    }

}