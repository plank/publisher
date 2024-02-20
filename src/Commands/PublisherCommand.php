<?php

namespace Plank\Publisher\Commands;

use Illuminate\Console\Command;

class PublisherCommand extends Command
{
    public $signature = 'publisher';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
