<?php

namespace Carnival\Entity;

class City {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
    public $name;
    
    public function __toString() {
        return $this->name;
    }

}