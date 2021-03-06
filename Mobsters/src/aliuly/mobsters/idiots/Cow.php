<?php
namespace aliuly\mobsters\idiots;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\Network;

use pocketmine\Player;
use pocketmine\entity\Animal;


class Cow extends Animal{
	const NETWORK_ID=11;

	public $width = 0.625;
	public $length = 1.5;
	public $height = 1.6875;

	public function getName(){
		return "Cow";
	}

	public function spawnTo(Player $player){

		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk->setChannel(Network::CHANNEL_ENTITY_SPAWNING));
		parent::spawnTo($player);
	}
	
	public function getDrops(){
		$drops = [ Item::get($this->fireTicks > 0 ? Item::COOKED_BEEF : Item::RAW_BEEF, 0, mt_rand(1,3)) ];
		$leather = mt_rand(0,2);
		if($leather){
			$drops[] = Item::get(Item::LEATHER, 0, $leather);
		}
		return $drops;
	}

}
