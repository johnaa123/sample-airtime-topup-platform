<?php

namespace App\Console\Commands;

use App\Models\Operator;
use App\Models\Country;
use App\Models\User;
use Illuminate\Console\Command;
use OTIFSolutions\ACLMenu\Models\UserRole;
use OTIFSolutions\Laravel\Settings\Models\Setting;

class SyncOperators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:operators';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Operators with the Reloadly Platform';

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
        $this->info("Started Sync of Operators with Reloadly Platform");
        $this->line("****************************************************************");
        $page=1;
        do{
            $this->line("Fetching Operators Page : ".$page);
            $response = User::admin()->getOperators($page);
            $this->info("Fetch Success !!!");
            $page++;
            $this->line("Syncing with Database");
            foreach ($response['content'] as $operator){
                if (isset($operator['operatorId'])){
                    Operator::updateOrCreate(
                        ['rid' => $operator['operatorId']],
                        [
                            'rid' => $operator['operatorId'],
                            'country_id' => Country::where('iso',$operator['country']['isoName'])->first()['id'],
                            'name' => $operator['name'],
                            'bundle' => $operator['bundle'],
                            'data' => $operator['data'],
                            'pin' => $operator['pin'],
                            'supports_local_amounts' => $operator['supportsLocalAmounts'],
                            'supports_geographical_recharge_plans' => $operator['supportsGeographicalRechargePlans'],
                            'denomination_type' => $operator['denominationType'],
                            'sender_currency_code' => $operator['senderCurrencyCode'],
                            'sender_currency_symbol' => $operator['senderCurrencySymbol'],
                            'destination_currency_code' => $operator['destinationCurrencyCode'],
                            'destination_currency_symbol' => $operator['destinationCurrencySymbol'],
                            'commission' => $operator['commission'],
                            'international_discount' => $operator['internationalDiscount'],
                            'local_discount' => $operator['localDiscount'],
                            'most_popular_amount' => $operator['mostPopularAmount'],
                            'min_amount' => $operator['minAmount'],
                            'local_min_amount' => $operator['localMinAmount'],
                            'max_amount' => $operator['maxAmount'],
                            'local_max_amount' => $operator['localMaxAmount'],
                            'fx_rate' => $operator['fx']['rate'],
                            'logo_urls' => $operator['logoUrls'],
                            'fixed_amounts' => $operator['fixedAmounts'],
                            'fixed_amounts_descriptions' => $operator['fixedAmountsDescriptions'],
                            'local_fixed_amounts' => $operator['localFixedAmounts'],
                            'local_fixed_amounts_descriptions' => $operator['localFixedAmountsDescriptions'],
                            'suggested_amounts' => $operator['suggestedAmounts'],
                            'suggested_amounts_map' => $operator['suggestedAmountsMap'],
                            'geographical_recharge_plans' => $operator['geographicalRechargePlans']
                        ]
                    );
                }
            }
            $this->info("Sync Completed For ".count($response['content'])." Operators");
        }while($response['totalPages'] >= $page);
        $this->line("****************************************************************");
        $this->info("All Operators Synced !!! ");
        $this->line("****************************************************************");

        $this->line("Re-Syncing Reseller User's Operator Rates");
        $role = UserRole::where('name','RESELLER')->first();
        $resellers = User::where('user_role_id',$role['id'])->get();
        foreach ($resellers as $reseller){
            $exists = $reseller->operators()->pluck('id');
            $operators = Operator::whereNotIn('id',$exists)->pluck('id');
            $reseller->operators()->syncWithoutDetaching($operators);
            $userOperators = $reseller->operators()->whereIn('id',$operators)->get();
            foreach ($userOperators as $operator){
                $operator->pivot->international_discount = $operator['discount']['international_percentage'] * (Setting::get('reseller_discount') / 100);
                $operator->pivot->local_discount = $operator['discount']['local_percentage'] * (Setting::get('reseller_discount') / 100);
                $operator->pivot->save();
            }
        }

        $this->line("****************************************************************");
        $this->info("All Done !!! ");
        $this->line("****************************************************************");
        $this->line("");
    }
}
