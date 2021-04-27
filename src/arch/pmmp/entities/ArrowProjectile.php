<?php


namespace arch\pmmp\entities;


use arch\pmmp\utilities\PlaySound;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\math\RayTraceResult;
use pocketmine\Player;

class ArrowProjectile extends Projectile
{
    /** @var float */
    protected $damage = 10.0;

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
        $blockHit->getLevel()->addParticle(new DestroyBlockParticle($blockHit->asVector3(), $blockHit));
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
        parent::onHitEntity($entityHit, $hitResult);

        $owner = $this->getOwningEntity();
        if (!($owner instanceof Player)) return;
        if (!($entityHit instanceof Player)) return;

        $owner->sendTip("hit:" . $entityHit->getName());
        PlaySound::execute($owner, $owner->getPosition(), "random.anvil_land");

        $level = $entityHit->getLevel();
        for ($i = 0; $i <= 3; $i++) {
            $level->addParticle(new LavaParticle($entityHit->getPosition()));
        }
        $level->addParticle(new HeartParticle($entityHit->getPosition()));
    }
}