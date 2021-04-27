<?php


namespace arch\pmmp\items;


use pocketmine\item\Item;
use pocketmine\Player;

class TapToDoItem extends Item
{
    private \Closure $onTap;

    public function __construct(int $itemId, string $name, \Closure $onTap) {
        $this->onTap = $onTap;
        parent::__construct($itemId, 0, $name);
    }

    public function onTapBlock(Player $player) {
        ($this->onTap)($player);
    }
}