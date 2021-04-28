<?php


namespace arch\pmmp\entities;


use arch\Arch;
use pocketmine\Player;

class GameDealer extends EntityBase
{
    const NAME = "GameDealer";

    public string $skinName = self::NAME;
    public string $geometryId = "geometry." . self::NAME;
    public string $geometryName = self::NAME . ".geo.json";

    public function onTap(Player $player): void {
        Arch::randomJoin($player);
    }
}