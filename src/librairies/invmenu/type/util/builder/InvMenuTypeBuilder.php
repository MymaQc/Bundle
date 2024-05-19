<?php

declare(strict_types=1);

namespace bundle\librairies\invmenu\type\util\builder;

use bundle\librairies\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}
