<?php

namespace Carnival\Entity;

class Color {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255" translatable="true") */
	public $favcolor;

	/** @var(type="varchar" nullable="false" length="255" translatable="true") */
	public $leastfavcolor;

	/** @var(type="int" nullable="false" length="11") */
	public $randomnumber;

	public function __toString() {
		return $this->favcolor;
	}

}