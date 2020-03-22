<?php

namespace Carnival\Entity;

use Lampion\Entity\Entity;

class User extends Entity
{
    # Public:
    public $id;
    public $username;
    public $role;

    # Protected:
    protected $pwd;

    public function __construct($id = null) {
        return $this->init($id);
    }

    public function setPassword(string $pwd) {
        $this->pwd = password_hash($pwd, PASSWORD_DEFAULT);
    }

    public function getPassword() {
        return $this->pwd;
    }

    public function persist() {
        $this->save();
    }

    public function destroy() {
        $this->delete();
    }

    public function __toString() {
        return $this->username;
    }
}