<?php

namespace App\Console\Commands;

use App\Custom\EmailManager;
use App\Custom\PerformWithdrawal;
use App\Models\FailedWithdrawal;
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
        /*if (FailedWithdrawal::count() > 0) {
            new PerformWithdrawal(FailedWithdrawal::oldest()->first()->user_id, FailedWithdrawal::oldest()->first()->payment_id, FailedWithdrawal::oldest()->first()->amount_usd, "withdrawal", "", "", "", true);
        }*/
        $test = new EmailManager();
        $test->sendOtp("ugiezie@gmail.com", "English", "This a job scheduler test");
    }
}
