<?php

namespace bundle\util;

use pocketmine\player\Player;

final class BundleUtil {

    /**
     * @param Player $player
     * @return string
     */
    public static function getPlayerName(Player $player): string {
        return str_replace(" ", "_", $player->getName());
    }

    /**
     * @param int $number
     * @return string
     */
    public static function formatNumber(int $number): string {
        $suffixes = [
            1000000 => 'M',
            1000 => 'k',
        ];
        foreach ($suffixes as $divisor => $suffix) {
            if ($number >= $divisor) {
                return rtrim(sprintf('%d%s', $number / $divisor, $suffix), '0.');
            }
        }
        return number_format($number);
    }

}