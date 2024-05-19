<?php

namespace bundle\manager;

use bundle\Bundle;
use bundle\librairies\invmenu\InvMenu;
use bundle\librairies\invmenu\type\InvMenuTypeIds;
use bundle\provider\BundleProvider;
use bundle\util\BundleUtil;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\{enchantment\EnchantmentInstance,
    enchantment\VanillaEnchantments,
    Item,
    StringToItemParser,
    VanillaItems};
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;

final class BundleManager {

    use SingletonTrait;

    /**
     * @var Position
     */
    private Position $position;

    /**
     * @var int
     */
    private int $price;

    /**
     * @var int
     */
    private int $quantity;

    /**
     * @var array
     */
    private array $rewards;

    /**
     * @return void
     */
    public function load(): void {
        $config = Bundle::getInstance()->getConfig();
        [$x, $y, $z, $worldName] = $config->getNested("bundle.settings.position");
        Server::getInstance()->getWorldManager()->loadWorld($worldName);
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        $this->position =  new Position($x, $y, $z, $world);
        $this->price = intval($config->getNested("bundle.settings.price"));
        $this->quantity = intval($config->getNested("bundle.settings.quantity"));
        $this->loadRewards();
        BundleProvider::getInstance()->load();
    }

    /**
     * @return void
     */
    private function loadRewards(): void {
        $rewards = Bundle::getInstance()->getConfig()->getNested("bundle.settings.rewards");
        foreach ($rewards as $reward) {
            [$type, $itemName, $value] = explode(":", $reward);
            switch ($type) {
                case "item":
                    $item = StringToItemParser::getInstance()->parse("minecraft:" . $itemName);
                    if ($item instanceof Item) {
                        $this->rewards[] = $item->setCount($value);
                    }
                    break;
                case "bundle":
                case "money":
                case "kit":
                    $item = VanillaItems::PAPER()->setCustomName($itemName)->setCount(1)->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
                    $this->rewards[] = ($type == "kit" ? $item->getNamedTag()->setString($type, $value) : $item->getNamedTag()->setInt($type, $value));
                    break;
            }
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function open(Player $player): void {
        $quantity = $this->getQuantity();
        array_map(function (Item $reward) use ($player): void {
            if ($player->getInventory()->canAddItem($reward)) {
                $player->getInventory()->addItem($reward);
            } else {
                $player->getWorld()->dropItem($player->getPosition()->asVector3(), $reward);
            }
        }, $this->getRandomRewards($quantity));
        $player->sendTip(str_replace("{quantity}", $quantity, Bundle::getInstance()->getConfig()->getNested("bundle.message.open-bundle")));
        BundleProvider::getInstance()->reduce($player, 1);
    }

    /**
     * @param int $quantity
     * @return Item[]
     */
    private function getRandomRewards(int $quantity): array {
        $rewards = $this->getRewards();
        return $quantity > 1 ? array_values(array_intersect_key($rewards, array_flip(array_rand($rewards, $quantity)))) : [$rewards[array_rand($rewards)]];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function canOpen(Player $player): bool {
        return BundleProvider::getInstance()->get($player) > 0;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function visualizeRewards(Player $player): void {
        $rewards = $this->getRewards();
        $invMenu = InvMenu::create(count($rewards) <= 27 ? InvMenuTypeIds::TYPE_CHEST : InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $invMenu->setName($this->getVisualizeInventoryName());
        $invMenu->setListener(InvMenu::readonly());
        array_map(fn (int $slot, Item $item) => $invMenu->getInventory()->setItem($slot, $item), array_keys($rewards), $rewards);
        $invMenu->send($player);
    }

    /**
     * @param Player $player
     * @param Item $item
     * @return void
     * @noinspection PhpUndefinedVariableInspection
     */
    public function executeCustomItem(Player $player, Item $item): void {
        $compoundTag = $item->getNamedTag();
        $config = Bundle::getInstance()->getConfig();
        if (!is_null($compoundTag->getTag("kit"))) {
            $kit = $compoundTag->getString("kit");
            $command = "givekit \"" . $player->getName() . "\" " . $kit;
            $player->sendMessage(str_replace("{kit}", $kit, $config->getNested("bundle.message.kit-use")));
        } else if (!is_null($compoundTag->getTag("money"))) {
            $money = $compoundTag->getInt("money");
            $command = "givemoney \"" . $player->getName() . "\" " . $money;
            $player->sendMessage(str_replace("{montant}", BundleUtil::formatNumber($money), $config->getNested("bundle.message.money-use")));
        } else if (!is_null($compoundTag->getTag("bundle"))) {
            $bundle = $compoundTag->getInt("bundle");
            $command = "addbundle \"" . $player->getName() . "\" " . $bundle;
            $player->sendMessage(str_replace("{bundle}", $bundle, $config->getNested("bundle.message.bundle-use")));
        }
        $server = Server::getInstance();
        $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), $command);
        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? VanillaItems::AIR() : $item);
        $player->broadcastSound(new BlazeShootSound(), [$player]);
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function isCustomItem(Item $item): bool {
        $compoundTag = $item->getNamedTag();
        return !is_null($compoundTag->getTag("kit")) || !is_null($compoundTag->getTag("money")) || !is_null($compoundTag->getTag("bundle"));
    }

    /**
     * @param Block $block
     * @return bool
     */
    public function isBundleBlock(Block $block): bool {
        $blockPosition = $block->getPosition();
        return $blockPosition->equals($this->position->asVector3()) && $blockPosition->getWorld()->getFolderName() == $this->position->getWorld()->getFolderName();
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function canBuy(Player $player): bool {
        return EconomyAPI::getInstance()->myMoney($player) >= $this->getPrice();
    }

    /**
     * @return int
     */
    public function getPrice(): int {
        return $this->price;
    }

    /**
     * @return int
     */
    private function getQuantity(): int {
        return $this->quantity;
    }

    /**
     * @return array
     */
    private function getRewards(): array {
        return $this->rewards;
    }

    /**
     * @param string $type
     * @param string $component
     * @return string
     */
    public function getFormText(string $type, string $component): string {
        return strval(Bundle::getInstance()->getConfig()->getNested("bundle.forms.$type.$component"));
    }

    /**
     * @return string
     */
    private function getVisualizeInventoryName(): string {
        return strval(Bundle::getInstance()->getConfig()->getNested("bundle.settings.visualize-name"));
    }

    /**
     * @param string $command
     * @return string
     */
    public function getCommandName(string $command): string {
        return strval(Bundle::getInstance()->getConfig()->getNested("bundle.commands.$command.name"));
    }

    /**
     * @param string $command
     * @return Translatable|string
     */
    public function getCommandDescription(string $command): Translatable|string {
        return strval(Bundle::getInstance()->getConfig()->getNested("bundle.commands.$command.description"));
    }

    /**
     * @param string $command
     * @return Translatable|string|null
     */
    public function getCommandUsage(string $command): Translatable|string|null {
        return Bundle::getInstance()->getConfig()->getNested("bundle.commands.$command.usage");
    }

    /**
     * @param string $command
     * @return array
     */
    public function getCommandAliases(string $command): array {
        return Bundle::getInstance()->getConfig()->getNested("bundle.commands.$command.aliases");
    }

}
