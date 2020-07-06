<?php

namespace Carnival\Admin\Core\Filter;

use Lampion\View\View;
use Lampion\Application\Application;

class Filter {

    private $fields;

    public $entityName;

    public function __construct(string $entityName) {
        $this->entityName = $entityName;
    }

    public function setField(FilterField $field) {
        $this->fields[] = $field;
    }

    public function render() {
        $view = new View(ROOT . APP . Application::name() . TEMPLATES, Application::name());

        // TODO: Add field with entity's name so it can be used in processing

        $view->render('core/filterBase', [
            'fields' => $this->fields
        ]);
    }

    public static function process() {
        $table = 'entity_' . strtolower($_POST['entityName']);

        $query = 'SELECT * FROM ' . $table . 'WHERE ';

        foreach($_POST['filter']['fields'] as $key => $field) {
            # If key isn't 0, that means at least one conditing has already been applied
            # therefore AND has to be added to the query
            if($key != 0) {
                $query .= 'AND';
            }

            $query .= $field['name'] . $field['operator'] . $field['value'];
        }
    }
}