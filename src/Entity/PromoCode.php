<?php

namespace Carnival\Entity;

class PromoCode {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
	public $code;

	/** @var(type="int" nullable="false" length="3") */
	public $amount;

	public function __toString() {
		return $this->code;
	}
}