<?php

namespace bundle;

use bundle\commands\BundleAddCommand;
use bundle\commands\BundleGiveAllCommand;
use bundle\commands\BundleReduceCommand;
use bundle\commands\BundleSetCommand;
use bundle\librairies\invmenu\InvMenuHandler;
use bundle\listener\BundleListeners;
use bundle\manager\BundleManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Bundle extends PluginBase {

    use SingletonTrait;

    /**
     * @return void
     */
    protected function onLoad(): void {
        self::setInstance($this);
        $this->saveDefaultConfig();
    }

    /**
     * @return void
     */
    protected function onEnable(): void {
        BundleManager::getInstance()->load();

        $this->getServer()->getPluginManager()->registerEvents(new BundleListeners(), $this);

        $this->getServer()->getCommandMap()->registerAll("bundle", [
            new BundleAddCommand(),
            new BundleGiveAllCommand(),
            new BundleReduceCommand(),
            new BundleSetCommand()
        ]);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $this->getLogger()->notice($this->getConfig()->getNested("bundle.message.enable-plugin"));
    }

    /**
     * @return void
     */
    protected function onDisable(): void {
        $this->getLogger()->notice($this->getConfig()->getNested("bundle.message.disable-plugin"));
    }

}