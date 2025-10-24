<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class MailScheduler
{
    public static function assignDelays(int $count, int $intervalSec = 3, int $startGrace = 1): array
    {
        $delays = [];
        $lock = Cache::lock('mailer:schedule:lock', 5);

        $lock->block(5, function () use ($count, $intervalSec, $startGrace, &$delays) {
            $now = now();
            $nextTs = Cache::get('mailer:next_ts');
            $cursor = $nextTs ? Carbon::createFromTimestamp($nextTs) : $now;

            if ($cursor->lt($now)) {
                $cursor = $now->copy()->addSeconds($startGrace);
            }

            for ($i = 0; $i < $count; $i++) {
                $delays[] = max(0, $now->diffInSeconds($cursor, false));
                $cursor->addSeconds($intervalSec);
            }

            Cache::put('mailer:next_ts', $cursor->getTimestamp(), 3600);
        });

        return $delays;
    }
}
