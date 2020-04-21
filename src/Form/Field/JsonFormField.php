<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class JsonFormField extends FormField {

    public function submit($data) {
        return json_encode($data);
    }

    public function display($options) {
        return isset($options['choices']) ? $this->template('json', $options) : null;
    }
}