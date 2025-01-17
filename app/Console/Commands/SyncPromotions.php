<?php

namespace App\Console\Commands;

use App\Models\Operator;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Console\Command;

class SyncPromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:promotions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Promotions with the Reloadly Platform';

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
        $this->info("Started Sync of Promotions with Reloadly Platform");
        $this->line("****************************************************************");
        $this->line("Removing all current promotions.");
        Promotion::truncate();
        $this->info("All Promotions Removed.");
        $page=1;
        do{
            $this->line("Fetching Promotions Page : ".$page);
            $response = User::admin()->getPromotions($page);
            $this->info("Fetch Success !!!");
            $page++;
            $this->line("Syncing with Database");
            foreach ($response['content'] as $promotion){
                if (isset($promotion['promotionId'])){
                    Promotion::updateOrCreate(
                        ['rid' => $promotion['promotionId']],
                        [
                            'rid' => $promotion['promotionId'],
                            'operator_id' => Operator::where('rid',$promotion['operatorId'])->first()['id'],
                            'title' => $promotion['title'],
                            'title2' => $promotion['title2'],
                            'description' => $promotion['description'],
                            'start_date' => $promotion['startDate'],
                            'end_date' => $promotion['endDate'],
                            'denominations' => $promotion['denominations'],
                            'localDenominations' => $promotion['localDenominations']
                        ]
                    );
                }
            }
            $this->info("Sync Completed For ".count($response['content'])." Promotions");
        }while($response['totalPages'] >= $page);
        $this->line("****************************************************************");
        $this->info("All Promotions Synced !!! ");
        $this->line("****************************************************************");
        $this->line("");
    }
}
