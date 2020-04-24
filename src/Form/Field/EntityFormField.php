<?php

namespace Carnival\Form\Field;

use Lampion\Entity\EntityManager;
use Lampion\Form\FormField;

class EntityFormField extends FormField {

    public function display($options) {
        $em = new EntityManager();

        $options['entities'] = $em->all($options['metadata']->entity);

        return $this->template('entity', $options);
    }

    public function submit($data) {
        return $data;
    }

}