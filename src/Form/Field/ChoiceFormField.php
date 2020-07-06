<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class ChoiceFormField extends FormField {

    public function submit($data) {
        return $data;
    }

    public function display($options) {
        return isset($options['choices']) ? $this->template('choice', $options) : false;
    }
}