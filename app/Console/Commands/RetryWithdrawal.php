<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RetryWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "retry:withdrawal";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command is used to retry a failed withdrawal attempt.";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Command::SUCCESS;
    }
}
