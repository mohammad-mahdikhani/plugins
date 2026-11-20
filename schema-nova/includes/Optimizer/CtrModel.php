<?php
/**
 * Expected CTR by average position. Used to find titles that underperform
 * relative to where they already rank — the pages worth rewriting.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Optimizer;

final class CtrModel
{
    /**
     * Approximate organic CTR curve (web search, blended devices).
     */
    public static function expected(float $position): float
    {
        if ($position <= 0) {
            return 0.02;
        }

        $table = [
            1  => 0.28,
            2  => 0.15,
            3  => 0.11,
            4  => 0.08,
            5  => 0.06,
            6  => 0.05,
            7  => 0.04,
            8  => 0.035,
            9  => 0.03,
            10 => 0.025,
        ];

        $floor = (int) max(1, min(20, (int) round($position)));
        if (isset($table[$floor])) {
            return $table[$floor];
        }

        return max(0.01, 0.12 / $floor);
    }

    public static function gap(float $actualCtr, float $position): float
    {
        $expected = self::expected($position);
        if ($expected <= 0) {
            return 0.0;
        }

        return ($expected - $actualCtr) / $expected;
    }
}
