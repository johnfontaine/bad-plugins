<?php
namespace aliuly\mobsters\idiots;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\Player;
use pocketmine\entity\Animal;


class Chicken extends Animal{
	const NETWORK_ID=10;

	public $width = 0.5;
	public $length = 0.8125;
	public $height = 0.875;

	public function getName(){
		return "Chicken";
	}

	public function spawnTo(Player $player){
		$pk = new AddMobPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->getData();
		$player->dataPacket($pk);

		$player->addEntityMotion($this->getId(), $this->motionX, $this->motionY, $this->motionZ);

		parent::spawnTo($player);
	}

	public function getData(){ //TODO
		$flags = 0;
		$flags |= $this->fireTicks > 0 ? 1 : 0;
		//$flags |= ($this->crouched === true ? 0b10:0) << 1;
		//$flags |= ($this->inAction === true ? 0b10000:0);
		$d = [
			0 => ["type" => 0, "value" => $flags],
			1 => ["type" => 1, "value" => $this->airTicks],
			16 => ["type" => 0, "value" => 0],
			17 => ["type" => 6, "value" => [0, 0, 0]],
		];

		return $d;
	}

	public function getDrops(){
		$drops = [
			Item::get($this->fireTicks > 0 ? Item::COOKED_CHICKEN : Item::RAW_CHICKEN, 0, 1)
		];
		$feather = mt_rand(0,2);
		if ($feather) {
			$drops[] = Item::get(Item::FEATHER, 0, $feather);
		}
		return $drops;
	}

}
