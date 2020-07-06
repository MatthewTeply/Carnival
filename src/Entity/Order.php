<?php

namespace Carnival\Entity;

use Lampion\Debug\Console;
use Lampion\Entity\EntityManager;

class Order {

    /** @var(type="int", length="11") */
    public $id;

	/** @var(type="varchar" nullable="false" length="255") */
	public $firstname;

	/** @var(type="varchar" nullable="false" length="255") */
	public $lastname;

	/** @var(type="varchar" nullable="false" length="255") */
	public $email;

	/** @var(type="varchar" nullable="false" length="255") */
	public $phone;

	/** @var(type="varchar" nullable="false" length="255") */
	public $street;

	/** @var(type="int" nullable="false" length="10") */
	public $houseNumber;

	/** @var(type="varchar" nullable="false" length="255") */
	public $city;

	/** @var(type="int" nullable="false" length="10") */
	public $postalCode;

	/** @var(type="text" nullable="true") */
	public $note;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\Card" multiple="true") */
	public $cards;

	/** @var(type="entity" nullable="false" entity="Carnival\Entity\PromoCode") */
	public $promoCode;
	
	/** @var(type="int" nullable="false") */
	public $price;

	/** @var(type="entity" nullable="true" entity="Carnival\Entity\Extra" multiple="true") */
	public $extras;

	/** @var(type="timestamp" nullable="false") */
	public $created;

	public function getPrice() {
		$em = new EntityManager();

		$extraIds = json_decode($this->extras);
		$extras = [];

		if($this->extras !== null && is_iterable($extraIds)) {
			foreach($extraIds as $extraId) {
				$extras[] = $em->find(Extra::class, $extraId);
			}
		}

		$this->price = 0;

		if(is_iterable($this->cards)) {
			foreach($this->cards as $card) {
				$this->price += $card->price;
			}
		}

		foreach($extras as $extra) {
			$this->price += $extra->price;
		}
		
		return $this->promoCode != null 
			? [
				'before' => $this->price,
				'after' => $this->price - $this->price*(@$this->promoCode->amount/100)
			] 
			: $this->price;
	}

}