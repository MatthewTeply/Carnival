<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class FileFormField extends FormField {

    public function display($options) {
        return $this->template('file', $options);
    }

    public function submit($data) {
        return $data;
    }

}