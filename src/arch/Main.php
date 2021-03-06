<?php


namespace arch;


use arch\pmmp\entities\ArrowProjectile;
use arch\pmmp\entities\EntityBase;
use arch\pmmp\entities\GameDealer;
use arch\pmmp\entities\SmokeEntity;
use arch\pmmp\hotbarmenu\NPCHotbarMenu;
use arch\pmmp\items\Bow;
use arch\pmmp\items\Smoke;
use arch\pmmp\scoreboards\ArchGameScoreboard;
use game_chef\api\GameChef;
use game_chef\models\GameStatus;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\events\AddScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerJoinGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class Main extends PluginBase implements Listener
{

    public function onEnable() {
        DataFolderPath::init(
            $this->getDataFolder(),
            $this->getFile() . "resources" . DIRECTORY_SEPARATOR,
        );

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        Entity::registerEntity(ArrowProjectile::class, true, ["Arrow", EntityIds::ARROW]);
        Entity::registerEntity(GameDealer::class, true, [GameDealer::NAME]);
        ItemFactory::registerItem(new Bow(), true);
        ItemFactory::registerItem(new Smoke(), true);
        ArchGameScoreboard::init();
    }

    public function onJoin(PlayerJoinEvent $event) {
        $pk = new GameRulesChangedPacket();
        $pk->gameRules["doImmediateRespawn"] = [1, true];
        $event->getPlayer()->sendDataPacket($pk);
    }

    public function onJoinGame(PlayerJoinGameEvent $event) {
        $player = $event->getPlayer();
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        $game = GameChef::findFFAGameById($gameId);
        if ($game->getStatus()->equals(GameStatus::Started())) {
            Arch::sendToArchGame($player, $game);
        } else {
            $player->sendMessage("???????????????????????????");
        }
    }

    public function onQuitGame(PlayerQuitGameEvent $event) {
        $player = $event->getPlayer();
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = $this->getServer()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($player->getName() . "??????????????????????????????");
        }
        Arch::backToLobby($player);
    }

    public function onStartGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        $game = GameChef::findFFAGameById($gameId);
        GameChef::setFFAPlayersSpawnPoint($gameId);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            Arch::sendToArchGame($player, $game);
        }

        $map = $game->getMap();
        $handler = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $tick) use ($map): void {
            Arch::spawnMapItem($map);
        }), 20 * 15);
        Arch::$handlers[strval($gameId)] = $handler;
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;
        Arch::$handlers[strval($gameId)]->cancel();
        unset(Arch::$handlers[strval($gameId)]);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            Arch::backToLobby($player);
        }
        //TODO:??????
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        //?????????????????????
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossbar = Bossbar::findByType($player, Arch::getBossBarType());
            if ($bossbar === null) continue;
            if ($event->getTimeLimit() === null) {
                $bossbar->updateTitle("????????????:({$event->getElapsedTime()})");
            } else {
                $bossbar->updateTitle("{$event->getElapsedTime()}/{$event->getTimeLimit()}");
                $bossbar->updatePercentage(1 - ($event->getElapsedTime() / $event->getTimeLimit()));
            }
        }
    }

    public function onAddedScore(AddScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        $game = GameChef::findFFAGameById($gameId);
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            ArchGameScoreboard::update($player, $game);
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        if (!GameChef::isRelatedWith($player, Arch::getGameType())) return;

        //??????????????????????????????
        GameChef::setFFAPlayerSpawnPoint($event->getPlayer());

        $event->setDrops([]);
        $event->setXpDropAmount(0);
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $attacker = $event->getAttacker();
        $killedPlayer = $event->getKilledPlayer();
        if (!$gameType->equals(Arch::getGameType())) return;

        //????????????????????????
        $message = "[{$attacker->getName()}] killed [{$killedPlayer->getName()}]";
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($message);
        }

        //??????????????????
        GameChef::addFFAGameScore($gameId, $attacker->getName(), new Score(1));
    }

    public function onTapBlock(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if ($item->getId() === Smoke::ITEM_ID) {
            Arch::spawnSmokeEntity($player);
        }
    }

    public function onTapAir(DataPacketReceiveEvent $event) {
        $packet = $event->getPacket();
        if ($packet instanceof LevelSoundEventPacket) {
            if ($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) {
                $player = $event->getPlayer();
                $item = $event->getPlayer()->getInventory()->getItemInHand();
                if ($item->getId() === Smoke::ITEM_ID) {
                    Arch::spawnSmokeEntity($player);
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!($sender instanceof Player)) return false;
        if ($label === "npc") {
            $menu = new NPCHotbarMenu($sender);
            $menu->send();
            return true;
        }
        return false;
    }

    public function onDamagedEntity(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if (!($entity instanceof EntityBase)) return;

        if ($entity instanceof GameDealer) {
            $event->setCancelled();

            if ($event instanceof EntityDamageByEntityEvent) {
                $attacker = $event->getDamager();
                if (!($attacker instanceof Player)) return;

                $entity->onTap($attacker);
                return;
            }
            return;
        }

        if ($entity instanceof SmokeEntity) {
            $event->setCancelled();
            return;
        }
    }
}