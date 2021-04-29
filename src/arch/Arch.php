<?php


namespace arch;


use arch\pmmp\entities\SmokeEntity;
use arch\pmmp\items\Bow;
use arch\pmmp\items\Smoke;
use arch\pmmp\scoreboards\ArchGameScoreboard;
use game_chef\api\FFAGameBuilder;
use game_chef\api\GameChef;
use game_chef\models\FFAGame;
use game_chef\models\GameType;
use game_chef\models\Map;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\bossbar\BossbarType;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\item\Arrow;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

class Arch
{
    /**Arch
     * @var TaskHandler[]
     * gameId => handler
     */
    static array $handlers = [];

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
        $ffaGameBuilder->setMaxPlayers(null);
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
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 600, 4));

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
        $player->removeAllEffects();

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
            try {
                $mapNames = GameChef::getAvailableFFAGameMapNames(self::getGameType());
                if (count($mapNames) === 0) {
                    throw new \LogicException(self::getGameType() . "に対応したマップを作成してください");
                }
                $mapName = $mapNames[rand(0, count($mapNames) - 1)];
                self::buildGame($mapName);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                return;
            }
        }

        $games = GameChef::getGamesByType(self::getGameType());
        $game = $games[0];
        $result = GameChef::joinFFAGame($player, $game->getId());
        if (!$result) {
            $player->sendMessage("試合に参加できませんでした");
        }

        if (count(GameChef::getPlayerDataList($game->getId())) >= 2) {
            GameChef::startGame($game->getId());
        }
    }

    static function spawnMapItem(Map $map) {
        $items = [
            new Smoke()
        ];

        $vectors = $map->getCustomArrayVectorData("map_items");
        $level = Server::getInstance()->getLevelByName($map->getLevelName());
        foreach ($vectors as $vector) {
            //1/2でスポーン
            if (rand(0, 1) === 0) {
                $index = array_rand($items, 1);
                $level->dropItem($vector->add(0, 1), $items[$index]);
            }
        }
    }

    static function spawnSmokeEntity(Player $player) {
        $vector = $player->asVector3();
        $nbt = Entity::createBaseNBT($vector, $player->getDirectionVector());

        $smokeEntity = new SmokeEntity($player->getLevel(), $nbt);
        $smokeEntity->setMotion($smokeEntity->getMotion()->multiply(1.5));
        $smokeEntity->spawnToAll();
    }
}