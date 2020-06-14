<?php

namespace Carnival\Entity;

class Food {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
	public $name;

	/** @var(type="int" nullable="false" length="3") */
	public $price;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\User" mappedBy="owner_id") */
	public $owner;

}