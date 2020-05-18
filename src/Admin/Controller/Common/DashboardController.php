<?php
namespace Carnival\Admin\Controller\Common;

use Carnival\Admin\Core\Admin\AdminController;
use Carnival\Admin\GoogleAnalytics;
use Lampion\Debug\Console;

class DashboardController extends AdminController {

    public function listGet() {
        $ga = new GoogleAnalytics();

        $analytics = $ga->initializeAnalytics();
        $profile = $ga->getFirstProfileId($analytics);
        $results = $ga->getResults($analytics, $profile);

        $template = $this->view->load('admin/common/dashboard', [
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'title'       => $this->entityConfig->title,
            'description' => $this->entityConfig->description,
            'users'       => $results->users->rows[0][0],
            'sessions'    => $results->sessions->rows[0][0]
        ]);

        $this->renderTemplate($template);
    }

}