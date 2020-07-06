<?php

namespace Carnival\Entity;

class Extra {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="file" nullable="false") */
	public $img;

	/** @var(type="varchar" nullable="false" length="255") */
	public $name;

	/** @var(type="text" nullable="false" length="255") */
	public $description;

	/** @var(type="int" nullable="false" length="6") */
	public $price;

	public function __toString() {
		return $this->name;
	}
}