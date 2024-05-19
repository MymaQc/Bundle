<?php

namespace bundle\provider;

use bundle\Bundle;
use bundle\util\BundleUtil;
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\{Config, SingletonTrait};

final class BundleProvider {

    use SingletonTrait;

    /**
     * @var Config
     */
    private Config $provider;

    /**
     * @var array
     */
    private array $cache = [];

    /**
     * @return void
     */
    public function load(): void {
        $eloFilePath = Bundle::getInstance()->getDataFolder() . "Bundle.json";
        $this->provider = new Config($eloFilePath, Config::JSON);
        if (!is_file($eloFilePath)) {
            file_put_contents($eloFilePath, json_encode([], JSON_PRETTY_PRINT));
        }
        $providerData = $this->getProvider()->getAll();
        foreach ($providerData as $key => $value) {
            $this->cache[$key] = $value;
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function create(Player $player): void {
        if (!$this->exist($player)) {
            $playerName = BundleUtil::getPlayerName($player);
            $this->cache[$playerName] = 0;
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function exist(Player $player): bool {
        return array_key_exists(BundleUtil::getPlayerName($player), $this->cache);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function get(Player $player): int {
        return intval($this->cache[BundleUtil::getPlayerName($player)]) ?? 0;
    }

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function add(Player $player, int $amount): void {
        $this->cache[BundleUtil::getPlayerName($player)] += $amount;
    }

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function reduce(Player $player, int $amount): void {
        $this->cache[BundleUtil::getPlayerName($player)] -= min($this->get($player), $amount);
    }

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function set(Player $player, int $amount): void {
        $this->cache[BundleUtil::getPlayerName($player)] = $amount;
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function unload(): void {
        $provider = $this->getProvider();
        $provider->setAll([]);
        foreach ($this->cache as $key => $value) {
            $provider->set($key, $value);
        }
        $provider->save();
    }

    /**
     * @return Config
     */
    public function getProvider(): Config {
        return $this->provider;
    }

}
