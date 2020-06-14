<?php

namespace Carnival\Entity;

class Blog {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" length="255" nullable="false") */
	public $title;

	/** @var(type="text" nullable="false") */
	public $content;

	/** @var(type="file" nullable="false") */
	public $bannerTest;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\Tag" mappedBy="tags" multiple="true") */
	public $tags;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\User" mappedBy="user_id") */
	public $user;

	public function __toString() {
		return $this->title;
	}
}