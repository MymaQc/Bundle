<?php

declare(strict_types=1);

namespace bundle\librairies\invmenu\session\network\handler;

use Closure;
use bundle\librairies\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}
