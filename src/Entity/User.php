<?php

namespace Carnival\Entity;

use Lampion\Debug\Console;

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

    /** @var(type="file") */
    public $img;

    public function setPwd(string $pwd) {
        if(!empty($pwd)) {
            if(!password_verify($pwd, $this->pwd)) {
                return password_hash($pwd, PASSWORD_DEFAULT);
            }
        }
    }

    public function getRole() {
        $role = json_decode($this->role, true);

        if(is_array($role)) {
            if(!in_array('ROLE_USER', $role) || empty($role)) {
                $role[] = 'ROLE_USER';
            }

            $this->role = json_encode($role);
        }

        return $this->role;
    }

    public function hasPermission($permissions = ['ROLE_USER']) {
        if(!$permissions) {
            return true;
        }

        if(!empty(array_intersect(json_decode($this->getRole(), true), $permissions))) {
            return true;
        }

        return false;
    }

    public function __toString() {
        return $this->username;
    }
}