<?php


namespace arch\pmmp\items;


use pocketmine\item\Item;

class Smoke extends Item
{
    public const ITEM_ID = Item::MAGMA_CREAM;

    public function __construct() {
        parent::__construct(self::ITEM_ID, 0,"スモーク");
        $this->setCustomName($this->getName());
    }
}