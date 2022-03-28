<?php

namespace App\Console\Commands;

use Bouncer;
use App\Aggregates\Endusers\EndUserActivityAggregate;
use App\Models\Clients\Client;
use App\Models\Endusers\Lead;
use App\Models\Endusers\LeadDetails;
use App\Models\Endusers\LeadSource;
use App\Models\User;
use App\Models\UserDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\VarDumper\VarDumper;

class PostIntoCRM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:random';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Random dummy Lead for a random client. Schedule to run frequently to "simulate" real-world activity';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Pick a # between 1 and ten, that's now many times we're gonna do this
        $times_to_run_through_simulation = rand(1, 10);
        do
        {
            // Get a random client
            $rando_clientrissian = Client::orderBy(DB::raw('RAND()'))->first();
            $this->warn(' Today\'s Rando - '. $rando_clientrissian->name);

            // From that client, get a random lead source
            do {
                $random_lead_source = $rando_clientrissian->lead_sources()->orderBy(DB::raw('RAND()'))->first();
                // (custom generated lead sources will be retried)
            }while($random_lead_source->name == 'Custom');
            $this->info('Generated Lead Source - '. $random_lead_source->name);

            // From that client get a random lead type
            $random_lead_type = $rando_clientrissian->lead_types()->orderBy(DB::raw('RAND()'))->first();
            $this->info('Generated Lead Type - '. $random_lead_type->name);

            //  From that client, get a random location
            $random_location = $rando_clientrissian->locations()->orderBy(DB::raw('RAND()'))->first();
            $this->info('Generated Club - '. $random_location->name);

            // @todo - Flip a coin, if true, attach a UTM, from a template attached to that client
            if(rand(0,1) == 1)
            {
                $this->error('UTM Processing Not Ready!');
            }

            // @todo - Flip a coin, if true, attach a lead owner attached to that client
            if(rand(0,1) == 1)
            {
                $this->error('Lead Owner association Not Ready!');
            }

            // @todo - Flip a coin, if true, attach factory secondary details about the lead
            if(rand(0,1) == 1)
            {
                $this->error('Skipping Secondary Details because frankly my dear, i just don\'t feel like it!');
            }

            // Get a user who is an Account Owner for the client
            if($user = $this->getAuthorizedUser($rando_clientrissian))
            {
                $this->info('User whose token we are using - '. $user->name);

                // Get that user's API Access Token
                $its_pat = $user->api_token()->first();

                if($its_pat)
                {

                    // Factory a Random First Last Email and Phone
                    $prospect = Lead::factory()->count(1)
                        // over ride the client id and gr id from the factory
                        ->client_id($rando_clientrissian->id)
                        ->gr_location_id($random_location->gymrevenue_id)
                        ->make()->first();

                    // Prepare the Payload
                    $payload = [
                        'account'  => $rando_clientrissian->id,
                        'prospect' => [
                            'first_name' => $prospect->first_name,
                            //'middle_name'=> 'sometimes|required',
                            //'misc'       =>  'sometimes',
                            'last_name'  => $prospect->last_name,
                            'email'      => $prospect->email,
                            'phone'      => $prospect->primary_phone,
                            //'alt_phone'  => 'sometimes',
                            //'address1'   => 'sometimes|required',
                            //'address2'   => 'sometimes',
                            'gender'     => $prospect->gender,
                            //'dob'        => 'sometimes',
                            'ip'         => $prospect->ip_address,
                            'club_id'    => $random_location->gymrevenue_id,
                            'source_id'  => $random_lead_source->id,
                            'type_id'    => $random_lead_type->id,
                            //'prospect.owner_id'   => 'sometimes|required|exists:users,id',
                        ]
                        //'utm'                 => 'sometimes|required|array',
                    ];

                    $headers = [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer ".base64_decode($its_pat->value)
                    ];

                    try {
                   //     $url = env('APP_URL').'/api/customers/leads';
                        $url = 'http://127.0.0.1:8000/api/customers/leads';
                        $this->info('sending to '.$url);
                        // Use Laravel's Built in HTTP to call the lead intake endpoint.
                        Http::withHeaders($headers)->post($url, $payload);
                        //All Done!
                    }
                    catch(\Exception $e)
                    {
                        $this->warn('Peep  this error - '. $e->getMessage());
                    }
                }
                else
                {
                    $this->error('This user does not have an API access token for some reason. Ever left your wallet in the car with a big line waiting on you? It\'s a bit like that.');
                }
            }
            else
            {
                $this->error("Could not find an authorized user for {$rando_clientrissian->name}. It's like they skipped our turn in Uno.");
            }

            // Counting down the iterations till we're done.
            $times_to_run_through_simulation--;
        } while($times_to_run_through_simulation > 0);



        /*
        // Get all the Clients
        $clients = Client::whereActive(1)
            ->with('locations')
            ->with('lead_types')
            ->with('lead_sources')
            ->with('trial_membership_types')
            ->get();

        //       var_dump($clients);
        if (count($clients) > 0) {
            foreach ($clients as $client) {
                // For each client, get all the locations
                if (count($client->locations) > 0) {
                    foreach ($client->locations as $idx => $location) {
                        // For each location, MAKE 25 users, don't create
                        $prospects = Lead::factory()->count(1)
                            // over ride the client id and gr id from the factory
                            ->client_id($client->id)
                            ->gr_location_id($location->gymrevenue_id ?? '')
                            ->make();


                        foreach ($prospects as $prospect) {
                            $prospect->lead_type_id = $client->lead_types[random_int(1, count($client->lead_types) - 1)]->id;
                            $prospect->membership_type_id = $client->membership_types[random_int(1, count($client->membership_types) - 1)]->id;
                            $prospect->lead_source_id = $client->lead_sources[random_int(1, count($client->lead_sources) - 1)]->id;
                            // @todo - if the lead_source_id is connected to custom, redo it.
                            // @todo - no custom status in the seeder allowed...yet.

                            // For each fake user, run them through the EnduserActivityAggregate
                            $aggy = EndUserActivityAggregate::retrieve($prospect->id);
                            $prospect_data = $prospect->toArray();
                            $date_range = mt_rand(1262055681, 1262215681);
                            //generate details
                            $prospect_data['details'] = [
                                'opportunity' => ['Low', 'Medium', 'High'][rand(0, 2)],
                                'dob' => date("Y-m-d H:i:s", $date_range),
                            ];
                            $aggy->createNewLead($prospect_data)
                                ->joinAudience('leads', $client->id, Lead::class)
                                ->persist();

                            $lead_type_free_trial_id = $client->lead_types->keyBy('name')['free_trial']->id;

                            if ($prospect->lead_type_id === $lead_type_free_trial_id) {
                                $trial_id = $client->trial_membership_types[random_int(0, count($client->trial_membership_types) - 1)]->id;
                                $num_days_ago_trial_started = random_int(-9, -5);
                                $date_started = Carbon::now()->addDays($num_days_ago_trial_started);
                                $aggy->addTrialMembership($client->id, $trial_id, $date_started);
                                $num_times_trial_used = random_int(1, 3);
                                $date_used = $date_started;
                                for ($i = 1; $i <= $num_times_trial_used; $i++) {
                                    $num_days = random_int(1, 3);
                                    $num_hours = random_int(5, 14);
                                    $date_used->addDays($num_days)->addHours($num_hours);
                                    $aggy->useTrialMembership($client->id, $trial_id, $date_used);
                                }
                                $aggy->persist();
                            }

                            if (env('SEED_LEAD_DETAILS', false)) {
                                //only for seeding mass comm lead details for ui dev
                                LeadDetails::factory()->count(random_int(0, 20))->lead_id($prospect->id)->client_id($prospect->client_id)->create();
                            }
                        }

                    }
                }
            }
        }
        */
    }

    private function getAuthorizedUser(Client $client) : User|false
    {
        $results = false;

        // Grabbing users associated with the client
        $user_details_of_associated_client = UserDetails::where('name', '=', 'associated_client')
            ->whereValue($client->id)->get();

        if(count($user_details_of_associated_client) > 0)
        {
            foreach ($user_details_of_associated_client as $detail_record)
            {
                $user = User::find($detail_record->user_id);
                if(!is_null($user))
                {
                    if(Bouncer::is($user)->an('Account Owner'))
                    {
                        $results = $user;
                        break;
                    }
                }
            }

        }
        else
        {
            $this->warn("No users attached to client {$client->name}. This is the equivalent of losing a life in Mario.");
        }

        return $results;
    }
}
