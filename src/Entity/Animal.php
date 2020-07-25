<?php

namespace Carnival\Entity;

class Animal {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
	public $name;

	/** @var(type="entity" nullable="false" mapped_by="user_id" entity="Carnival\Entity\User") */
	public $user;

}