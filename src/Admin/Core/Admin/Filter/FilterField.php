<?php

namespace Carnival\Admin\Core\Filter;

use Lampion\View\View;
use Lampion\Application\Application;

abstract class FilterField {

    /**
     * Returns field's template
     * @param array $options
     * @return string
     */
    abstract public function display();

    /**
     * All the logic happens in this method
     * @param array $data
     * @return mixed
     */
    abstract public function submit();

    public $view;

    public function __construct() {
        $this->view = new View(ROOT . APP . Application::name() . TEMPLATES . 'admin/filters', Application::name());
    }

    public function template(string $path, $options) {
        return $this->view->load($path, $options, true);
    }

}