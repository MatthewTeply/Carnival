<?php

namespace Carnival\Entity;

class Product {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255" translatable="true") */
	public $name;

	/** @var(type="int" nullable="false" length="11" translatable="false") */
	public $price;

	public function __toString() {
		return $this->name;
	}

}