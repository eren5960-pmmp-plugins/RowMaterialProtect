<?php 

declare(strict_types=1);

namespace Eren5960\RowMaterial;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\permission\Permission;
use pocketmine\event\block\BlockBreakEvent;

class RowMaterial extends PluginBase implements Listener {
	/** @var Config[] */
	protected $config = [];
	/** @var string[] */
	protected $levels = [];
	/** @var string[] */
	protected $blocks = [];
	
	public function onLoad(): void{
		$this->initPermissions();
	}
	
	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->initPlugin();
	}

	public function initPlugin(): void{
        $this->initConfig();
        $this->initLevels();
        $this->initBlocks();
    }

	private function initConfig(): void{
		$this->saveDefaultConfig();
		$this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
	}
	
	private function initLevels(): void{
		$this->levels = $this->config["levels"];
	}
	
	private function initBlocks(): void{
		$this->blocks = $this->config["blocks"];
	}
	
	private function initPermissions(): void{
		$perms = [];
		foreach ($this->blocks as $block) {
			$perms[$this->getPermission($block)]["default"] = "op";
		}
		Permission::loadPermissions($perms);
	}

    /**
     * @param string $block_name
     *
     * @return string
     */
	private function getPermission(string $block_name): string{
		return "area." . strtolower(str_replace(" ", ".", $block_name));
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function inLevel(Player $player): bool{
		return in_array($player->getLevel()->getFolderName(), $this->levels);
	}

	/**
	 * @param Block $block
	 *
	 * @return bool
	 */
	public function inBlock(Block $block): bool{
		return in_array($block->getName(), $this->blocks);
	}

	/**
	 * @param Player $player
	 * @param Block  $block
	 *
	 * @return bool
	 */
	public function hasPermission(Player $player, Block $block): bool{
		return $player->hasPermission($this->getPermission($block->getName()));
	}

	/**
	 * @param Player $player
	 */
	public function sendMessage(Player $player): void{
		$message = $this->config["message"];
		switch ($this->config["message-type"]) {
			case 'popup':
				$player->sendPopup($message);
				break;
			case 'title':
				$player->addTitle($message, $this->config["sub-message"]);
				break;
			case 'chat':
			default:
				$player->sendMessage($message);
				break;
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event): void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if(!$this->inLevel($player)) return;

		if($this->inBlock($block)){
			if(!$this->hasPermission($player, $block)){
				$event->setCancelled();
				$this->sendMessage($player);
			}
		}
	}
}
