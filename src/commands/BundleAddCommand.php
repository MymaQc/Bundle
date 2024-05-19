<?php

namespace bundle\commands;

use bundle\Bundle;
use bundle\manager\BundleManager;
use bundle\provider\BundleProvider;
use pocketmine\command\{Command, CommandSender};
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

final class BundleAddCommand extends Command {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        $api = BundleManager::getInstance();
        $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
        parent::__construct(
            $api->getCommandName("addbundle") ?? "addbundle",
            $api->getCommandDescription("addbundle") ?? "Ajouter des bundles à un joueur",
            $api->getCommandUsage("addbundle") ?? null,
            $api->getCommandAliases("addbundle") ?? []
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
                            $provider->add($target, $quantity);
                            $sender->sendMessage(str_replace(["{quantity}", "{player}"], [$quantity, $target->getName()], $config->getNested("bundle.message.add-sender")));
                            $target->sendMessage(str_replace(["{staff}", "{quantity}", "{bundle}"], [$sender->getName(), $quantity, $provider->get($target)], $config->getNested("bundle.message.add-target")));
                        } else {
                            $sender->sendMessage($config->getNested("bundle.message.not-numeric"));
                        }
                    } else {
                        $sender->sendMessage(str_replace("{player}", $args[0], $config->getNested("bundle.message.no-player")));
                    }
                } else {
                    $sender->sendMessage($api->getCommandUsage("addbundle"));
                }
            } else {
                $sender->sendMessage($config->getNested("bundle.message.no-perm"));
            }
        } else {
            $sender->sendMessage($config->getNested("bundle.message.no-console"));
        }
    }

}