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
}
