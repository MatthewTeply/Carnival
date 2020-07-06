<?php

namespace Carnival\Entity;

class Card {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="int" nullable="false" length="2") */
	public $rating;

	/** @var(type="varchar" nullable="false" length="255") */
	public $position;

	/** @var(type="varchar" nullable="false" length="255") */
	public $name;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\Background") */
	public $background;

	/** @var(type="file" nullable="false") */
	public $country;

	/** @var(type="file" nullable="false") */
	public $club;

	/** @var(type="file" nullable="false") */
	public $player;

	/** @var(type="json" nullable="false") */
	public $stats;

	/** @var(type="varchar" nullable="false" length="255") */
	public $cardType;

	/** @var(type="TIMESTAMP" nullable="false") */
	public $created;

	/** @var(type="int" nullable="false" length="10") */
	public $price;

	/** @var(type="smallint" nullable="false" length="1") */
	public $size;

	/** @var(type="varchar" nullable="false" length="255") */
	public $sessionId;

	public function __toString() {
		return $this->name;
	}
}