<?php

namespace Carnival\Entity;

use Lampion\User\Auth;

class Liveedit {
    
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

    /** @var(type="varchar", length="255") */
    public $type;

    /** @var(type="entity", entity="Carnival\Entity\User", mappedBy="user_id") */
    public $user;

    /** @var(type="entity", entity="Carnival\Entity\Language", mappedBy="language_id") */
    public $language;

    public function getHTMLContent() {
        /*
        if(Auth::isLoggedIn()) {
            $html  = '<span class=\'le-node-container\' id=\'le-' . $this->name . '\' data-le-name=\'' . $this->name . '\'>';
            $html .= $this->content;
            $html .= '</span>';

            return $html;
        }

        else {
            return $this->content;
        }
        */
        return $this->content;
    }

    public function getUser() {
        return $this->user->username ?? '';
    }

    public function __toString() {
        return $this->content;
    }
}