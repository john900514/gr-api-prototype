<?php

namespace Database\Seeders\Data;

use Bouncer;
use App\Models\Clients\Client;
use App\Models\Endusers\Lead;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\VarDumper\VarDumper;

class LeadProspectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        VarDumper::dump('Getting Clients');

        $clients = Client::whereActive(1)
            ->with('locations')
            ->with('lead_types')
            ->with('lead_sources')
            ->with('trial_membership_types')
            ->get();

        if (count($clients) > 0)
        {
            foreach ($clients as $client)
            {
                VarDumper::dump($client->name);
                // For each client, get all the locations
                if (count($client->locations) > 0)
                {
                    // For each client location, factory up some leads
                    foreach ($client->locations as $idx => $location)
                    {
                        $prospects = Lead::factory()->count(50)
                            ->client_id($client->id)
                            ->gr_location_id($location->gymrevenue_id)
                            ->make();

                        VarDumper::dump("Generating Leads for {$client->name} @ {$location->name}!");

                        foreach ($prospects as $prospect)
                        {
                            VarDumper::dump('Random Lead - '.$prospect->email);
                            // From that lead source get a random lead type
                            $random_lead_type = $client->lead_types()->orderBy(DB::raw('RAND()'))->first();
                            do {
                                $random_lead_source = $client->lead_sources()->orderBy(DB::raw('RAND()'))->first();
                                // (custom generated lead sources will be retried)
                            }while($random_lead_source->name == 'Custom');

                            // @todo - Flip a coin, if true, attach a UTM, from a template attached to that client
                            if(rand(0,1) == 1)
                            {
                                // @todo - implement UTM addition to creation logic here
                            }

                            // @dep - may not need these.
                            $start_window = strtotime(date('Y-m-d H:i:s', strtotime('NOW -6DAY')));
                            $end_window = strtotime(date('Y-m-d H:i:s', strtotime('NOW')));
                            $date_range = mt_rand($start_window,$end_window);

                            // @todo - throw in dob
                            if($user = $this->getAuthorizedUser($client))
                            {
                                // Get that user's API Access Token
                                $its_pat = $user->api_token()->first();
                                if($its_pat)
                                {
                                    $payload = [
                                        'account'  => $client->id,
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
                                            'club_id'    => $location->gymrevenue_id,
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
                                        $url = env('APP_URL').'/api/customers/leads';
                                        // Use Laravel's Built in HTTP to call the lead intake endpoint.
                                        Http::withHeaders($headers)->post($url, $payload);

                                        // @todo - retrieve the lead and continue running processing on it.
                                        // @todo - if free trial lead, run sim on the lead using their pass a few times.
                                    }
                                    catch(\Exception $e)
                                    {
                                        $this->warn('Peep  this error - '. $e->getMessage());
                                    }
                                }
                                else
                                {
                                    VarDumper::dump("This user does not have an API access token for some reason");
                                }
                                // Prepare the Payload

                            }
                            else
                            {
                                VarDumper::dump("Could not find an authorized user for {$client->name}");
                            }
                        }
                    }
                }
                else
                {
                    VarDumper::dump($client->name.' Missing Locations. Skipping.');
                }
            }
        }
        else
        {
            VarDumper::dump('No clients to work with. Ending');
        }
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
