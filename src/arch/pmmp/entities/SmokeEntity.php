<?php


namespace arch\pmmp\entities;


use game_chef\TaskSchedulerStorage;
use pocketmine\level\Level;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class SmokeEntity extends EntityBase
{

    private TaskHandler $handler;

    public function __construct(Level $level, CompoundTag $nbt) {
        parent::__construct($level, $nbt);

        TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick): void {
            if ($this->isAlive()) $this->kill();
        }), 20 * 5);

        $this->handler = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick): void {
            for ($i = 0; $i < 15; ++$i) {
                $vector = $this->asVector3()->add(rand(-1, 1), 0, rand(-1, 1));
                $this->getLevel()->addParticle(new MobSpawnParticle($vector, 4, 3));
            }
        }), 20 * 5);
    }

    protected function onDeath(): void {
        $this->handler->cancel();
        parent::onDeath();
    }
}