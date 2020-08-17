<?php

namespace Carnival\Entity;

class Article {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255" translatable="true") */
	public $title;

	/** @var(type="text" nullable="false" translatable="true") */
	public $content;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\User" mapped_by="user_id") */
	public $user;

	public function __toString() {
		return $this->title;
	}

}