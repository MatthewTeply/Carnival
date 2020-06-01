<?php

namespace Carnival\Entity;

class Article {

    /** @var(type="int") */
    public $id;

    /** @var(type="string") */
    public $title;

    /** @var(type="longstring", cascade="remove") */
    public $content;

    /** @var(type="entity", entity="Carnival\Entity\User", mappedBy="user_id") */
    public $user;

    /** @var(type="file") */
    public $banner;

    public function __toString() {
        return $this->title;
    }

}