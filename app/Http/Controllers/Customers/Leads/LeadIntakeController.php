<?php

namespace App\Http\Controllers\Customers\Leads;

use App\Actions\Customers\Leads\CreateNewLead;
use App\Models\Clients\Location;
use App\Models\Endusers\LeadSource;
use App\Models\Endusers\LeadType;
use App\Models\UserDetails;
use Bouncer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadIntakeController extends Controller
{
    public function __construct()
    {

    }

    public function create(UserDetails $details, Location $club, LeadSource $source, LeadType $type)
    {
        $results = ['success' => false];
        $code = 500;

        $rules = [
            'account'             => 'bail|required|exists:clients,id',
            'prospect'            => 'bail|required|array',
            'prospect.first_name' => 'bail|required',
            'prospect.middle_name'=> 'sometimes|required',
            'prospect.misc'       =>  'sometimes',
            'prospect.last_name'  => 'bail|required',
            'prospect.email'      => 'bail|required',
            'prospect.phone'      => 'sometimes|required',
            'prospect.alt_phone'  => 'sometimes',
            'prospect.address1'   => 'sometimes|required',
            'prospect.address2'   => 'sometimes',
            'prospect.gender'     => 'sometimes',
            'prospect.dob'        => 'sometimes',
            'prospect.ip'         => 'sometimes',
            'prospect.club_id'    => 'bail|required|exists:locations,gymrevenue_id',
            'prospect.source_id'  => 'bail|required|exists:lead_sources,id',
            'prospect.type_id'    => 'bail|required|exists:lead_types,id',
            'prospect.owner_id'   => 'sometimes|required|exists:users,id',
            'utm'                 => 'sometimes|required|array',
        ];

        // Validate the request or fail on the bad key
        $validated = Validator::make(request()->all(), $rules);

        if ($validated->fails())
        {
            foreach($validated->errors()->toArray() as $idx => $error_msg)
            {
                $results['reason'] = $error_msg[0];
                $code = 400;
                break;
            }
        }
        else
        {
            $user = request()->user();
            // Make sure the user is either an Admin, or User's associated client and passed in account must match
            $associated_client = $user->associated_client()->first() ?? $details;
            $valid = Bouncer::is($user)->an('Admin') || ($associated_client->value == request()->get('account'));

            if($valid)
            {
                // Make sure the club_id matches the gymrevenue_id of a location record belonging to the valid client_id
                $client_id = request()->get('account');
                $club_record = $club->whereGymrevenueId(request()->get('prospect')['club_id'])
                    ->whereClientId($client_id)
                    ->first();

                if(!is_null($club_record))
                {
                    // Make sure the source_id matches the lead_sources.id of a lead source record belonging to the valid client_id
                    $source_record = $source->whereId(request()->get('prospect')['source_id'])
                        ->whereClientId($client_id)
                        ->first();

                    if(!is_null($source_record))
                    {
                        // Make sure the type_id matches the lead_types.id of a lead type record belonging to the valid client_id
                        $type_record = $type->whereId(request()->get('prospect')['type_id'])
                            ->whereClientId($client_id)
                            ->first();

                        if(!is_null($type_record))
                        {
                            if(array_key_exists('owner_id',request()->get('prospect') ))
                            {
                                // @todo - Make sure the owner_id matches the users.id of the shmuck's associated-client details record belonging to the valid client_id
                                $owner = $user->whereId(request()->get('prospect')['owner_id'])
                                    ->with('associated_client')->first();

                                if((!is_null($owner->associated_client)) && ($owner->associated_client->value == request()->get('account')))
                                {
                                    // Once everything is all good and well, Run processValidLead and return its results
                                    return  $this->processValidLead(request()->all());

                                }
                                else
                                {
                                    $results['reason'] = 'Invalid Owner';
                                }
                            }
                            else
                            {

                                // Once everything is all good and well, Run processValidLead and return its results
                                return $this->processValidLead(request()->all());
                            }
                        }
                        else
                        {
                            $results['reason'] = 'Invalid Lead Source';
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Invalid Lead Source';
                    }
                }
                else
                {
                    $results['reason'] = 'Invalid Location';
                }
            }
            else
            {
                $results['reason'] = 'Invalid Account';
            }

        }

        return response($results, $code);
    }

    private function processValidLead(array $data)
    {
        // Run CreateNewLead and return its results
        $results = ['success' => false];
        $code = 500;


        //If it returns a string, send back the string and 200
        if ($new_lead_uuid = CreateNewLead::run($data['account'], $data['prospect']))
        {

            $results = ['success' => true, 'lead' => $new_lead_uuid];
            $code = 200;
        }
        else
        {
            // Else, make an error and return 404
            $results['reason'] = 'Could not create new lead';
        }

        return response($results, $code);

    }
}
