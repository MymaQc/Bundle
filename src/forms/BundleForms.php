<?php

namespace bundle\forms;

use bundle\Bundle;
use bundle\librairies\formapi\SimpleForm;
use bundle\manager\BundleManager;
use bundle\provider\BundleProvider;
use bundle\util\BundleUtil;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class BundleForms {

    use SingletonTrait;

    /**
     * @param Player $player
     * @return SimpleForm
     */
    public function getMainForm(Player $player): SimpleForm {
        $api = BundleManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($api): void {
            if (is_string($data)) {
                switch ($data) {
                    case "open":
                        if ($api->canOpen($player)) {
                            $api->open($player);
                        } else {
                            $player->sendMessage(strval(Bundle::getInstance()->getConfig()->getNested("bundle.message.no-bundle")));
                        }
                        break;
                    case "buy":
                        $player->sendForm($this->getBuyForm($player));
                        break;
                    case "visualize":
                        $api->visualizeRewards($player);
                        break;
                }
            }
        });
        $form->setTitle($api->getFormText("main", "title"));
        $form->setContent(str_replace("{bundle}", BundleProvider::getInstance()->get($player), $api->getFormText("main", "content")));
        $form->addButton($api->getFormText("main", "buttons.open"), label: "open");
        $form->addButton($api->getFormText("main", "buttons.buy"), label: "buy");
        $form->addButton($api->getFormText("main", "buttons.visualize"), label: "visualize");
        return $form;
    }

    /**
     * @param Player $player
     * @return SimpleForm
     */
    private function getBuyForm(Player $player): SimpleForm {
        $api = BundleManager::getInstance();
        $price = $api->getPrice();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($api, $price): void {
            if (is_string($data)) {
                if ($data == "confirm") {
                    if ($api->canBuy($player)) {
                        EconomyAPI::getInstance()->reduceMoney($player, $price);
                        $provider = BundleProvider::getInstance();
                        $provider->add($player, 1);
                        $player->sendMessage(str_replace(["{price}", "{bundle}"], [BundleUtil::formatNumber($price), $provider->get($player)], Bundle::getInstance()->getConfig()->getNested("bundle.message.buy-bundle")));
                    } else {
                        $player->sendMessage(Bundle::getInstance()->getConfig()->getNested("bundle.message.no-money"));
                    }
                }
            }
        });
        $form->setTitle($api->getFormText("buy", "title"));
        $form->setContent(str_replace(["{price}", "{money}"], [BundleUtil::formatNumber($price), BundleUtil::formatNumber(EconomyAPI::getInstance()->myMoney($player))], $api->getFormText("buy", "content")));
        $form->addButton($api->getFormText("buy", "buttons.confirm"), label: "confirm");
        $form->addButton($api->getFormText("buy", "buttons.cancel"), label: "cancel");
        return $form;
    }

}
