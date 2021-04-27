<?php


namespace arch\pmmp\entities;


use arch\Arch;
use pocketmine\Player;

class ArchNPC extends NPCBase
{
    const NAME = "ArchNPC";

    public string $skinName = self::NAME;
    public string $geometryId = "geometry." . self::NAME;
    public string $geometryName = self::NAME . ".geo.json";

    public function onAttackedByPlayer(Player $player): void {
        Arch::randomJoin($player);
    }
}