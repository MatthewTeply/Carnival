<?php

namespace Carnival\Form\Field;

use Lampion\Form\FormField;

class IconFormField extends FormField {

    public function display($options) {
        return $this->template('icon', $options);
    }

    public function submit($data) {
        return $data;
    }

}