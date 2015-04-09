<?php
namespace aliuly\notsoflat;

use pocketmine\block\Block;
use pocketmine\block\CoalOre;
use pocketmine\block\DiamondOre;
use pocketmine\block\Dirt;
use pocketmine\block\GoldOre;
use pocketmine\block\Gravel;
use pocketmine\block\IronOre;
use pocketmine\block\LapisOre;
use pocketmine\block\RedstoneOre;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GenerationChunkManager;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\generator\populator\Populator;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\populator\Tree;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\utils\Random;

class NotSoFlat extends Generator{

	/** @var Populator[] */
	private $populators = [];
	/** @var GenerationChunkManager */
	private $level;
	/** @var Random */
	private $random;
	private $worldHeight = 65;
	private $waterHeight = 54;
	/** @var Simplex */
	private $noiseHills;
	/** @var Simplex */
	private $noiseBase;

	public function __construct(array $options = []){

	}

	public function getName(){
		return "notsoflat";
	}

	public function getSettings(){
		return [];
	}

	public function init(GenerationChunkManager $level, Random $random){
		$this->level = $level;
		$this->random = $random;
		$this->random->setSeed($this->level->getSeed());
		$this->noiseHills = new Simplex($this->random, 3, 0.1, 12);
		$this->noiseBase = new Simplex($this->random, 16, 0.6, 16);

		$ores = new Ore();
		$ores->setOreTypes([
			new OreType(new CoalOre(), 20, 16, 0, 128),
			new OreType(New IronOre(), 20, 8, 0, 64),
			new OreType(new RedstoneOre(), 8, 7, 0, 16),
			new OreType(new LapisOre(), 1, 6, 0, 32),
			new OreType(new GoldOre(), 2, 8, 0, 32),
			new OreType(new DiamondOre(), 1, 7, 0, 16),
			new OreType(new Dirt(), 20, 32, 0, 128),
			new OreType(new Gravel(), 10, 16, 0, 128),
		]);
		$this->populators[] = $ores;

		$trees = new Tree();
		$trees->setBaseAmount(1);
		$trees->setRandomAmount(1);
		$this->populators[] = $trees;

		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(5);
		$tallGrass->setRandomAmount(0);
		$this->populators[] = $tallGrass;
	}

	public function generateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		$hills = [];
		$base = [];
		$incline = [];
		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$i = ($z << 4) + $x;
				$hills[$i] = $this->noiseHills->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), true);
				$base[$i] = $this->noiseBase->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), true);
				if($base[$i] < 0){
					$base[$i] *= 0.5;
				}
			}
		}

		$chunk = $this->level->getChunk($chunkX, $chunkZ);

		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$i = ($z << 4) + $x;
				$height = $this->worldHeight + $hills[$i] * 14 + $base[$i] * 7;
				$height = (int) $height;

				for($y = 0; $y < 128; ++$y){
					$diff = $height - $y;
					if($y <= 4 and ($y === 0 or $this->random->nextFloat() < 0.75)){
						$chunk->setBlockId($x, $y, $z, Block::BEDROCK);
					}elseif($diff > 2){
						$chunk->setBlockId($x, $y, $z, Block::STONE);
					}elseif($diff > 0){
						$chunk->setBlockId($x, $y, $z, Block::DIRT);
					}elseif($y <= $this->waterHeight){
						if(($this->waterHeight - $y) <= 1 and $diff === 0){
							$chunk->setBlockId($x, $y, $z, Block::SAND);
						}elseif($diff === 0){
							$chunk->setBlockId($x, $y, $z, Block::DIRT);
						}else{
							$chunk->setBlockId($x, $y, $z, Block::STILL_WATER);
						}
					}elseif($diff === 0){
						$chunk->setBlockId($x, $y, $z, Block::SAND);
					}
				}

			}
		}

	}

	public function populateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}
	}

	public function getSpawn(){
		return $this->level->getSafeSpawn(new Vector3(127.5, 128, 127.5));
	}

}