<?php

namespace Carnival\Entity;

class LiveEdit {
    
    /** @var(type="int", length="11") */
    public $id;

    /** @var(type="varchar", length="255") */
    public $name;
    
    /** @var(type="varchar", length="255") */
    public $route;

    /** @var(type="text") */
    public $content;

    /** @var(type="text") */
    public $original;

    /** @var(type="entity", entity="Carnival\Entity\User", mappedBy="user_id") */
    public $user;

    public function getHTMLContent() {
        $html  = '<span class=\'le-node-container\' id=\'le-' . $this->name . '\' data-le-name=\'' . $this->name . '\'>';
        $html .= $this->content;
        $html .= '</span>';

        return $html;
    }

    public function getUser() {
        return $this->user->username ?? '';
    }

    public function __toString() {
        return $this->content;
    }
}