<?php

namespace bundle\commands;

use bundle\Bundle;
use bundle\manager\BundleManager;
use bundle\provider\BundleProvider;
use pocketmine\command\{Command, CommandSender};
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

final class BundleGiveAllCommand extends Command {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        $api = BundleManager::getInstance();
        $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
        parent::__construct(
            $api->getCommandName("giveallbundle") ?? "giveallbundle",
            $api->getCommandDescription("giveallbundle") ?? "Ajouter des packs à tous les joueurs connectés",
            $api->getCommandUsage("giveallbundle") ?? null,
            $api->getCommandAliases("giveallbundle") ?? []
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $api = BundleManager::getInstance();
        $config = Bundle::getInstance()->getConfig();
        if ($sender instanceof Player) {
            if ($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                if (isset($args[0])) {
                    if (is_numeric($args[0])) {
                        $quantity = round(max(1, intval($args[0])));
                        $provider = BundleProvider::getInstance();
                        $onlinePlayers = Server::getInstance()->getOnlinePlayers();
                        foreach ($onlinePlayers as $onlinePlayer) {
                            $provider->add($onlinePlayer, $quantity);
                            $onlinePlayer->sendMessage(str_replace(["{staff}", "{quantity}", "{bundle}"], [$sender->getName(), $quantity, $provider->get($onlinePlayer)], $config->getNested("bundle.message.giveall-target")));
                        }
                        $sender->sendMessage(str_replace(["{quantity}", "{online}"], [$quantity, count($onlinePlayers)], $config->getNested("bundle.message.giveall-sender")));
                    } else {
                        $sender->sendMessage($config->getNested("bundle.message.not-numeric"));
                    }
                } else {
                    $sender->sendMessage($api->getCommandUsage("giveallbundle"));
                }
            } else {
                $sender->sendMessage($config->getNested("bundle.message.no-perm"));
            }
        } else {
            $sender->sendMessage($config->getNested("bundle.message.no-console"));
        }
    }

}
