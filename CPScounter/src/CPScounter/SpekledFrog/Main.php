<?php

declare(strict_types=1);

namespace CPScounter\SpekledFrog;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;

class Main extends PluginBase implements Listener{
    private static $clicks = [];
    
    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function addClick(Player $player)
    {
        if(!isset(self::$clicks[$player->getName()]) || empty(self::$clicks[$player->getName()])){
            self::$clicks[$player->getName()][] = microtime(true);
	}else{
	    array_unshift(self::$clicks[$player->getName()], microtime(true));
	    if (count(self::$clicks[$player->getName()]) >= 100) {
	        array_pop(self::$clicks[$player->getName()]);
	    }
	        $player->sendTip(TextFormat::RED . "CPS§f: " . TextFormat::RESET . self::getCps($player));
	}   
    }

    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            if ($packet->trData->getTypeId() == InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $this->addClick($event->getOrigin()->getPlayer());
            }
        }
        if ($packet instanceof LevelSoundEventPacket and $packet->sound == 42) {
            $this->addClick($player);
         }
        if ($event->getPacket()->pid() === AnimatePacket::NETWORK_ID) {
            $event->getOrigin()->getPlayer()->getServer()->broadcastPackets($event->getOrigin()->getPlayer()->getViewers(), [$event->getPacket()]);
            $event->cancel();
        }
    }
    public static function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1): float
    {
        if (empty(self::$clicks[$player->getName()])) {
            return 0.0;
        }
        $mt = microtime(true);
        return round(count(array_filter(self::$clicks[$player->getName()], static function (float $t) use ($deltaTime, $mt): bool {
                return ($mt - $t) <= $deltaTime;
            })) / $deltaTime, $roundPrecision);
    }

}
