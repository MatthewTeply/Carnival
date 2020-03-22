<?php

namespace Carnival\Entity;

use Lampion\Entity\Entity;

class Liveedit extends Entity {
    
    public $id;
    public $le_id;
    public $content;

    public function __construct($id = null) {
        $this->init($id);
    }

    public function persist() {
        $this->save();
    }

    public function destroy() {
        $this->delete();
    }
    
}