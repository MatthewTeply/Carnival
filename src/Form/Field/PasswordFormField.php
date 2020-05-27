<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class PasswordFormField extends FormField {

    public function submit($data) {
        return $data;
    }

    public function display($options) {
        return $this->template('password', $options);
    }

}