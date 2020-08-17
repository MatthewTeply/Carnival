<?php

namespace Carnival\Entity;

class Timeline {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
	public $title;

	/** @var(type="varchar" nullable="false" length="255") */
	public $content;

	/** @var(type="datetime" nullable="false") */
	public $created;

	/** @var(type="varchar" nullable="false" length="255") */
	public $entity_name;

	/** @var(type="int" nullable="true" length="11") */
	public $entity_id;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\User" mapped_by="user_id") */
	public $user;

	/** @var(type="varchar" nullable="false" length="255") */
	public $type;

}