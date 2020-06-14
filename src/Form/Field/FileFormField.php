<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class FileFormField extends FormField {

    public function display($options) {
        if(!isset($options['value'])) {
            $options['value'] = null;
        }

        if(!is_array($options['value'])) {
            $options['value'] = [$options['value']];
        }

        return $this->template('file', $options);
    }

    public function submit($data) {
        return json_encode(!is_array($data) ? [$data] : $data);
    }

}