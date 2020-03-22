<?php
namespace Carnival\Controller\Common;

use Lampion\Controller\ControllerBase;

class HomeController extends ControllerBase {

    public function index() {
        $view = $this->load()->view();

        $view->render('common/home');
    }

}