<?php

namespace Carnival\Entity;

use Lampion\Debug\Console;

class User
{
    # Public:
    public $id;
    public $username;
    public $role;
    public $pwd;

    public function setPwd(string $pwd) {
        $this->pwd = password_hash($pwd, PASSWORD_DEFAULT);

        Console::log('Password: ' . $this->pwd);
    }

    public function getPwd() {
        return $this->pwd;
    }

    public function getRole() {
        if(empty($role)) {
            $this->role = '["ROLE_USER"]';
        }

        return $this->role;
    }

    public function __toString() {
        return $this->username;
    }
}