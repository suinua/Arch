<?php


namespace arch;


use arch\pmmp\entities\ArrowProjectile;
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
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
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
        ItemFactory::registerItem(new Bow(), true);
        ItemFactory::registerItem(new Smoke(), true);
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
            $player->sendMessage("試合に参加しました");
        }
    }

    public function onQuitGame(PlayerQuitGameEvent $event) {
        $player = $event->getPlayer();
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = $this->getServer()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($player->getName() . "が試合から去りました");
        }
        Arch::backToLobby($player);
    }

    public function onStartGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        $game = GameChef::findFFAGameById($gameId);
        GameChef::setTeamGamePlayersSpawnPoint($gameId);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            Arch::sendToArchGame($player, $game);
        }
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            Arch::backToLobby($player);
        }
        //TODO:演出
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(Arch::getGameType())) return;

        //ボスバーの更新
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossbar = Bossbar::findByType($player, Arch::getBossBarType());
            if ($bossbar === null) continue;
            if ($event->getTimeLimit() === null) {
                $bossbar->updateTitle("経過時間:({$event->getElapsedTime()})");
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

        //スポーン地点を再設定
        GameChef::setFFAPlayerSpawnPoint($event->getPlayer());
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $attacker = $event->getAttacker();
        $killedPlayer = $event->getKilledPlayer();
        if (!$gameType->equals(Arch::getGameType())) return;

        //メッセージを送信
        $message = "[{$attacker->getName()}] killed [{$killedPlayer->getName()}]";
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($message);
        }

        //スコアの追加
        GameChef::addFFAGameScore($gameId, $attacker->getName(), new Score(1));
    }
}