<?php

declare(strict_types=1);

namespace bundle\librairies\invmenu\type;

use bundle\librairies\invmenu\InvMenu;
use bundle\librairies\invmenu\type\graphic\InvMenuGraphic;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuType{

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic;

	public function createInventory() : Inventory;
}
