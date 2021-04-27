<?php

namespace arch\pmmp\utilities;


use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;

class PlaySound
{
    static function execute(Player $player, Vector3 $pos, string $name, int $volume = 50, int $pitch = 1): void {
        $packet = new PlaySoundPacket();
        $packet->x = $pos->x;
        $packet->y = $pos->y;
        $packet->z = $pos->z;
        $packet->volume = $volume;
        $packet->pitch = $pitch;
        $packet->soundName = $name;
        $player->sendDataPacket($packet);
    }
}