<?php

namespace Carnival\Entity;

class Background {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="file" nullable="false") */
	public $img;

	/** @var(type="varchar" length="255" nullable="false") */
    public $textColor;
    
    public function __toString() {
        return $this->img->filename;
    }

}