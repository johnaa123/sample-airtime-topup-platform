<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topup;
use App\Models\System;
use Carbon\Carbon;

class SyncTopups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:topups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync topups with the Reloadly and MyPaga Platforms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line("");
        $this->line("****************************************************************");
        $this->info("Started Sync of Topups");
        $this->line("****************************************************************");
        $this->line("Getting Topups that are PENDING");
        Topup::whereIn('status',['PENDING','PROCESSING'])->chunk(100, function($topups)
        {
            $this->info(sizeof($topups)." Topups Found.");
            foreach ($topups as $topup){
                if (Carbon::now()->addMinutes(10) < new Carbon($topup['created_at']))
                    continue;
                if($topup['scheduled_datetime'] && isset($topup['timezone'])){
                    $now = Carbon::now();
                    $datetime = Carbon::parse($topup['scheduled_datetime'],$topup['timezone']['utc'][0]);
                    if ($datetime <= $now)
                        $topup->sendTopup();
                }else
                    $topup->sendTopup();
            }
            $this->info(sizeof($topups)." Topups Synced !!!");
        });
        $this->line("****************************************************************");
        $this->info("All Topups Synced !!! ");
        $this->line("****************************************************************");
        $this->line("");
    }
}
