<?php

namespace Challengr;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class Util
{
    public static function betterTimestamps(Blueprint $table)
    {
        $table->dateTime('created_at')
            ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        $table->dateTime('updated_at')
            ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
    }

    public static function secondsToTime(int $durationInSeconds)
    {
        return implode(':', array_map(function ($item) {
            return str_pad($item, 2, '0', STR_PAD_LEFT);
        }, [floor($durationInSeconds / 3600) % 60, floor($durationInSeconds / 60) % 60, $durationInSeconds % 60]));
    }
}
