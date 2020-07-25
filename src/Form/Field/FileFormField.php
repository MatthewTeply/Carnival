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

        if(isset($options['value'][0]) && $options['value'][0] != null)  {
            foreach($options['value'] as $file) {
                $options['ids'][] = $file->id;
            }
        }

        else {
            $options['ids'] = [];
        }

        return $this->template('file', $options);
    }

    public function submit($data) {
        return $data;
    }

}