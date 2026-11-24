<?php

namespace RonasIT\Support\Tests\Support\Mock\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public function backoff(): array
    {
        return [
            30,
            Carbon::SECONDS_PER_MINUTE * 1,
            Carbon::SECONDS_PER_MINUTE * 3,
            Carbon::SECONDS_PER_MINUTE * 5,
        ];
    }
}
