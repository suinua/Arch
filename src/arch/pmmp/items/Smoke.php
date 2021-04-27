<?php


namespace arch\pmmp\items;


use arch\pmmp\entities\SmokeEntity;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;

class Smoke extends TapToDoItem
{
    public const ITEM_ID = Item::MAGMA_CREAM;

    public function __construct() {
        parent::__construct(self::ITEM_ID, "スモーク", function (Player $player) {
            $vector = $player->asVector3();
            $nbt = Entity::createBaseNBT($vector, $player->getDirectionVector());

            $smokeEntity = new SmokeEntity($player->getLevel(), $nbt);
            $smokeEntity->setMotion($smokeEntity->getMotion()->multiply(1.5));
            $smokeEntity->spawnToAll();
        });
        $this->setCustomName($this->getName());
    }
}