<?php

declare(strict_types=1);

namespace bundle\librairies\invmenu\type\graphic\network;

use bundle\librairies\invmenu\session\InvMenuInfo;
use bundle\librairies\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

interface InvMenuGraphicNetworkTranslator{

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void;
}
