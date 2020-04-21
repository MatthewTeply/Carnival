<?php

namespace Carnival\Entity;

class User
{
    /** @var(type="int", length=11) */
    public $id;

    /** @var(type="varchar", length=255) */
    public $username;

    /** @var(type="json") */
    public $role;

    /** @var(type="varchar", length=255) */
    public $pwd;

    public function setPwd(string $pwd) {
        if(!password_verify($pwd, $this->pwd) && !empty($pwd)) {
            $this->pwd = password_hash($pwd, PASSWORD_DEFAULT);
        }
    }

    public function getRole() {
        if(empty($this->role)) {
            $this->role = '["ROLE_USER"]';
        }

        return $this->role;
    }

    public function __toString() {
        return $this->username;
    }
}