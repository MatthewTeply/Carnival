<?php

namespace Carnival\Entity;

class Country {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" length="255" nullable="false") */
	public $name;

	/** @var(type="file" nullable="false") */
	public $flag;

	public function __toString() {
		return $this->name;
	}
}