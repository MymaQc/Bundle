<?php

namespace bundle\listener;

use bundle\Bundle;
use bundle\forms\BundleForms;
use bundle\manager\BundleManager;
use bundle\provider\BundleProvider;
use JsonException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\plugin\PluginDisableEvent;

final class BundleListeners implements Listener {

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        BundleProvider::getInstance()->create($event->getPlayer());
    }

    /**
     * @priority LOWEST
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $action = $event->getAction();
        if ($action === $event::RIGHT_CLICK_BLOCK) {
            $block = $event->getBlock();
            $api = BundleManager::getInstance();
            if ($api->isBundleBlock($block)) {
                if (!is_null($player->getCurrentWindow())) {
                    $player->removeCurrentWindow();
                }
                $player->sendForm(BundleForms::getInstance()->getMainForm($player));
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     * @return void
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        $api = BundleManager::getInstance();
        if ($api->isCustomItem($item)) {
            $api->executeCustomItem($player, $item);
            $event->cancel();
        }
    }

    /**
     * @priority LOWEST
     * @param PluginDisableEvent $event
     * @return void
     * @throws JsonException
     */
    public function onPluginDisable(PluginDisableEvent $event): void {
        if ($event->getPlugin()->getName() == Bundle::getInstance()->getName()) {
            BundleProvider::getInstance()->unload();
        }
    }

}
