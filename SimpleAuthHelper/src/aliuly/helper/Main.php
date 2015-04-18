<?php
namespace aliuly\helper;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\scheduler\CallbackTask;

class Main extends PluginBase implements Listener {
	protected $auth;
	protected $pwds;
	protected $cfg;

	public function onEnable(){
		$this->auth = $this->getServer()->getPluginManager()->getPlugin("SimpleAuth");
		if (!$this->auth) {
			$this->getLogger()->info(TextFormat::RED."Unable to find SimpleAuth");
			return;
		}
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"messages" => [
				"re-enter pwd" => "Please re-enter password to confirm:",
				"passwords dont match" => "Passwords do not match.\nPlease try again!\nEnter a new password:",
				"register ok" => "You have been registered!",
				"no spaces" => "Password should not contain spaces or tabs",
				"not name" => "Password should not be your name",
				"too many logins" => "You have attempted to login too many times.",
				"login timeout" => "Login timer expired!",
			],
			"nest-egg" => [
				"272:0:1",
				"17:0:16",
				"364:0:5",
				"266:0:10",
			],
			"max-attempts" => 5,
			"login-timeout" => 60,
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			unset($defaults["nest-egg"]);
		}
		$this->cfg=(new Config($this->getDataFolder()."config.yml",
										  Config::YAML,$defaults))->getAll();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->pwds = [];
	}
	public function onPlayerQuit(PlayerQuitEvent $ev) {
		$n = $ev->getPlayer()->getName();
		if (isset($this->pwds[$n])) unset($this->pwds[$n]);
	}
	public function onPlayerJoin(PlayerJoinEvent $ev) {
		if ($this->cfg["login-timeout"] == 0) return;
		$n = $ev->getPlayer()->getName();
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"checkTimeout"],[$n]),$this->cfg["login-timeout"]*20);
	}
	/**
	 * @priority LOW
	 */
	public function onPlayerCmd(PlayerCommandPreprocessEvent $ev) {
		if ($ev->isCancelled()) return;
		//echo __METHOD__.",".__LINE__."\n"; //##DEBUG;
		$pl = $ev->getPlayer();
		if ($this->auth->isPlayerAuthenticated($pl)) return;

		$n = $pl->getName();
		if (!$this->auth->isPlayerRegistered($pl)) {
			if (!isset($this->pwds[$n])) {
				if (!$this->checkPwd($pl,$ev->getMessage())) {
					$ev->setCancelled();
					$ev->setMessage("~");
					return;
				}
				$this->pwds[$n] = $ev->getMessage();
				$pl->sendMessage($this->cfg["messages"]["re-enter pwd"]);
				$ev->setCancelled();
				$ev->setMessage("~");
				return;
			}
			if ($this->pwds[$n] != $ev->getMessage()) {
				unset($this->pwds[$n]);
				$ev->setCancelled();
				$ev->setMessage("~");
				echo $this->cfg["messages"]["passwords dont match"]."\n";
				$pl->sendMessage($this->cfg["messages"]["passwords dont match"]);
				return;
			}
			$this->auth->registerPlayer($pl,$this->pwds[$n]);
			$this->auth->authenticatePlayer($pl);
			unset($this->pwds[$n]);
			$ev->setMessage("~");
			$ev->setCancelled();
			$pl->sendMessage($this->cfg["messages"]["register ok"]);
			if (isset($this->cfg["nest-egg"]) && !$pl->isCreative()) {
				// Award a nest egg to player...
				foreach ($this->cfg["nest-egg"] as $i) {
					$r = explode(":",$i);
					if (count($r) != 3) continue;
					$item = new Item($r[0],$r[1],$r[2]);
					$pl->getInventory()->addItem($item);
				}
			}
			return;
		}
		$ev->setMessage("/login ".$ev->getMessage());
		if ($this->cfg["max-attempts"] > 0) {
			if (isset($this->pwds[$n])) {
				++$this->pwds[$n];
			} else {
				$this->pwds[$n] = 1;
			}
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"checkLoginCount"],[$n]),5);
		}
		return;
	}
	public function checkTimeout($n) {
		echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
		$pl = $this->getServer()->getPlayer($n);
		if ($pl && !$this->auth->isPlayerAuthenticated($pl)) {
			echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
			$pl->kick($this->cfg["messages"]["login timeout"]);
		}
	}
	public function checkLoginCount($n) {
		echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
		if (!isset($this->pwds[$n])) return;
		echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
		$pl = $this->getServer()->getPlayer($n);
		if ($pl && !$this->auth->isPlayerAuthenticated($pl)) {
			echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
			if ($this->pwds[$n] >= $this->cfg["max-attempts"]) {
				echo __METHOD__.",".__LINE__."($n)\n"; //##DEBUG;
				$pl->kick($this->cfg["messages"]["too many logins"]);
				unset($this->pwds[$n]);
			}
			return;
		}
		unset($this->pwds[$n]);
		return;
	}
	public function checkPwd($pl,$pwd) {
		if (preg_match('/\s/',$pwd)) {
			$pl->sendMessage($this->cfg["messages"]["no spaces"]);
			return false;
		}
		if (strlen($pwd) < $this->auth->getConfig()->get("minPasswordLength")){
			$pl->sendMessage($this->auth->getMessage("register.error.password"));
			return false;
		}
		if (strtolower($pl->getName()) == strtolower($pwd)) {
			$pl->sendMessage($this->cfg["messages"]["not name"]);
			return false;
		}
		return true;
	}

}
