<?php


namespace arch\pmmp\hotbarmenu;


use arch\pmmp\entities\GameDealer;
use game_chef\pmmp\hotbar_menu\HotbarMenu;
use game_chef\pmmp\hotbar_menu\HotbarMenuItem;
use pocketmine\entity\Entity;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class NPCHotbarMenu extends HotbarMenu
{
    public function __construct(Player $player) {
        parent::__construct($player, [
            new HotbarMenuItem(
                ItemIds::DYE,
                1,
                "GameDealer",
                function (Player $player) {
                    var_dump("spawn");
                    $nbt = Entity::createBaseNBT($player->asVector3());
                    $entity = new GameDealer($player->getLevel(), $nbt);
                    $entity->spawnToAll();
                })
        ]);
    }
}