<?php

namespace bundle\commands;

use bundle\Bundle;
use bundle\manager\BundleManager;
use bundle\provider\BundleProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

final class BundleReduceCommand extends Command {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        $api = BundleManager::getInstance();
        $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
        parent::__construct(
            $api->getCommandName("reducebundle") ?? "reducebundle",
            $api->getCommandDescription("reducebundle") ?? "Retirer des bundles à un joueur",
            $api->getCommandUsage("reducebundle") ?? null,
            $api->getCommandAliases("reducebundle") ?? ["removebundle"]
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $api = BundleManager::getInstance();
        $config = Bundle::getInstance()->getConfig();
        if ($sender instanceof Player) {
            if ($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                if (isset($args[0], $args[1])) {
                    $target = Server::getInstance()->getPlayerByPrefix($args[0]);
                    if ($target instanceof Player) {
                        if (is_numeric($args[1])) {
                            $quantity = round(max(1, intval($args[1])));
                            $provider = BundleProvider::getInstance();
                            if ($provider->get($target) >= $quantity) {
                                $provider->add($target, $quantity);
                                $sender->sendMessage(str_replace(["{quantity}", "{player}"], [$quantity, $target->getName()], $config->getNested("bundle.message.reduce-sender")));
                                $target->sendMessage(str_replace(["{staff}", "{quantity}", "{bundle}"], [$sender->getName(), $quantity, $provider->get($target)], $config->getNested("bundle.message.reduce-target")));
                            } else {
                                $sender->sendMessage(str_replace(["{player}", "{quantity}"], [$target->getName(), $quantity], $config->getNested("bundle.message.not-enought-bundle")));
                            }
                        } else {
                            $sender->sendMessage($config->getNested("bundle.message.not-numeric"));
                        }
                    } else {
                        $sender->sendMessage(str_replace("{player}", $args[0], $config->getNested("bundle.message.no-player")));
                    }
                } else {
                    $sender->sendMessage($api->getCommandUsage("reducebundle"));
                }
            } else {
                $sender->sendMessage($config->getNested("bundle.message.no-perm"));
            }
        } else {
            $sender->sendMessage($config->getNested("bundle.message.no-console"));
        }
    }

}