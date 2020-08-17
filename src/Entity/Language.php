<?php

namespace Carnival\Entity;

class Language {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="file" nullable="false") */
	public $icon;

	/** @var(type="varchar" nullable="false" length="5") */
	public $code;

	/** @var(type="varchar" nullable="false" length="255") */
	public $name;

	public function __toString() {
		return $this->name;
	}

}