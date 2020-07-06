<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class EntityFieldsFormField extends FormField {

    public function display($options) {
        return $this->template('entityFields', $options);
    }

    public function submit($data) {
        return $data;
    }

}