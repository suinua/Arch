<?php


namespace arch;


use arch\pmmp\items\Bow;
use arch\pmmp\scoreboards\ArchGameScoreboard;
use game_chef\api\FFAGameBuilder;
use game_chef\api\GameChef;
use game_chef\models\FFAGame;
use game_chef\models\GameType;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\bossbar\BossbarType;
use pocketmine\entity\Attribute;
use pocketmine\item\Arrow;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;

class Arch
{
    const MAPS = [];

    static function getGameType(): GameType {
        return new GameType("Arch");
    }

    static function getBossBarType(): BossbarType {
        return new BossbarType("Arch");
    }

    /**
     * @param string $mapName
     * @throws \Exception
     */
    static function buildGame(string $mapName): void {
        $ffaGameBuilder = new FFAGameBuilder();
        $ffaGameBuilder->setGameType(self::getGameType());
        $ffaGameBuilder->setTimeLimit(600);
        $ffaGameBuilder->setVictoryScore(new Score(15));
        $ffaGameBuilder->setCanJumpIn(true);
        $ffaGameBuilder->selectMapByName($mapName);

        $ffaGame = $ffaGameBuilder->build();
        GameChef::registerGame($ffaGame);
    }

    static function sendToArchGame(Player $player, FFAGame $game): void {
        $levelName = $game->getMap()->getLevelName();
        $level = Server::getInstance()->getLevelByName($levelName);

        $player->teleport($level->getSpawnLocation());
        $player->teleport(Position::fromObject($player->getSpawn(), $level));
        $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue(0.25);

        //ボスバー
        $bossbar = new Bossbar($player, self::getBossBarType(), "", 1.0);
        $bossbar->send();
        ArchGameScoreboard::send($player, $game);

        $player->getInventory()->setContents([
            new Bow(),
            new Arrow()
        ]);
    }

    static function backToLobby(Player $player): void {
        $level = Server::getInstance()->getLevelByName("lobby");
        $player->teleport($level->getSpawnLocation());
        $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue(0.1);
        $player->getInventory()->setContents([
            //todo:インベントリセット
        ]);

        //ボスバー削除
        $bossbar = Bossbar::findByType($player, self::getBossBarType());
        if ($bossbar !== null) $bossbar->remove();
        ArchGameScoreboard::delete($player);
    }

    static function randomJoin(Player $player): void {
        $games = GameChef::getGamesByType(self::getGameType());
        if (count($games) === 0) {
            $mapName = self::MAPS[random_int(0, count(self::MAPS) - 1)];
            try {
                self::buildGame($mapName);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }

        $game = $games[0];
        $result = GameChef::joinFFAGame($player, $game->getId());
        if (!$result) {
            $player->sendMessage("試合に参加できませんでした");
        }
    }
}