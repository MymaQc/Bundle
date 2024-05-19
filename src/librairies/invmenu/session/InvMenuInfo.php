<?php

declare(strict_types=1);

namespace bundle\librairies\invmenu\session;

use bundle\librairies\invmenu\InvMenu;
use bundle\librairies\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}
