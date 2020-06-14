<?php

namespace Carnival\Entity;

class Tag {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" length="255" nullable="false") */
	public $title;

	/** @var(type="entity" mappedBy="user_id" entity="Carnival\Entity\User" nullable="false") */
	public $user;

	public function __toString() {
		return $this->title;
	}

}