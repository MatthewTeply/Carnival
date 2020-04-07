<?php

namespace Carnival\Entity;

use Lampion\Entity\Entity;

class Order extends Entity {
    
    public $id;
    public $name;
    public $address;
    public $phone;
    public $moreUnits;
    public $unit;

    public function __construct($id = null) {
        return $this->init($id);
    }

    public function persist() {
        return $this->save();
    }

    public function destroy() {
        return $this->delete();
    }

    public function __toString() {
        return $this->id;
    }
}