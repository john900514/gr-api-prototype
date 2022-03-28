<?php

namespace App\Actions\Endusers;

use App\Actions\Customers\Leads\AdditionalIntakeToExistingLead;
use App\Aggregates\Endusers\EndUserActivityAggregate;
use App\Helpers\Uuid;
use App\Models\Endusers\Lead;
use Illuminate\Support\Facades\Redirect;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Prologue\Alerts\Facades\Alert;


class CreateLead
{
    use AsAction;

    /**
     * Get the validation rules that apply to the action.
     *
     * @return array
     */

 /*
    public function rules()
    {
        return [
            'first_name'                => ['required', 'max:50'],
            'middle_name'               => [],
            'last_name'                 => ['required', 'max:30'],
            'email'                     => ['required', 'email:rfc,dns'],
            'primary_phone'             => ['sometimes'],
            'alternate_phone'           => ['sometimes'],
            'gr_location_id'            => ['required', 'exists:locations,gymrevenue_id'],
            'lead_source_id'            => ['required', 'exists:lead_sources,id'],
            'lead_type_id'              => ['required', 'exists:lead_types,id'],
            'client_id'                 => 'required',
            'profile_picture'           => 'sometimes',
            'profile_picture.uuid'      => 'sometimes|required',
            'profile_picture.key'       => 'sometimes|required',
            'profile_picture.extension' => 'sometimes|required',
            'profile_picture.bucket'    => 'sometimes|required',
            'gender'                    => 'sometimes|required',
            'dob'                       => 'sometimes|required',
            'opportunity'               => 'sometimes|required',
            'lead_owner'                => 'sometimes|required|exists:users,id',
            'lead_status'               => 'sometimes|required|exists:lead_statuses,id',
            'notes'                     => 'nullable|string',

            'misc'  => 'sometimes',
            'owner_id'  => 'sometimes',
            'ip_address'  => 'sometimes',
        ];
    }

*/






    public function handle(string $client_id,$prospect_data)
    {

        $results = false;
        // @todo - check if the lead already exists. If so, return the UpdateExistingLead action instead
        $existing_lead = Lead::whereClientId($client_id)->whereEmail($prospect_data['email'])->first();
        if(!is_null($existing_lead))
        {
            return AdditionalIntakeToExistingLead::run($client_id, $existing_lead->id, $prospect_data);
        }

        $new_lead_id = Uuid::new();//we should use uuid here

//var_dump('what is '.$new_lead_id);
//die;
        $payload = [
            'client_id' => $client_id,
            'first_name' => $prospect_data['first_name'],
            'last_name' => $prospect_data['last_name'],
            'gender' => $prospect_data['gender'] ?? null,
            'dob' => $prospect_data['dob'] ?? null,
            'middle_name' => $prospect_data['middle_name'] ?? null,
            'misc' => $prospect_data['misc'] ?? null,
            'owner_id' => $prospect_data['owner_id'] ?? null,
            'email' => $prospect_data['email'],
            'primary_phone' => $prospect_data['phone'] ?? null,
            'alternate_phone' => $prospect_data['alt_phone'] ?? null,
            'gr_location_id' => $prospect_data['club_id'],
            'ip_address' => $prospect_data['ip'] ?? null,
            'lead_type_id' => $prospect_data['type_id'],
            'lead_source_id' => $prospect_data['source_id']
        ];

        // Call the EndUserActivityAggregate and persists create New Lead
        try {
            $aggy = EndUserActivityAggregate::retrieve($new_lead_id)
                ->createLead($payload);

            if(array_key_exists('utm', $prospect_data)){
                $aggy->processLeadUtms($prospect_data['utm'], $client_id);
            }

            $aggy = $aggy->joinAudience('prospects', $payload['client_id'], Lead::class);
            // @todo - insert trial membership if lead is a guest pass or free trial lead type
            //$aggy->addTrialMembership($client->id, $trial_id, $date_started);
            // @todo - if there is an owner attached - do the claimed lead event and logic
            $aggy->persist();
            $results = $new_lead_id;
        }
        catch(\Exception $e)
        {
            // @todo - perhaps send the issue to Sentry? or replace with Aggregate type exceptions
        }

        return $results;




/*



        $id = Uuid::new();//we should use uuid here
        $data['id'] = $id;
        $aggy = EndUserActivityAggregate::retrieve($data['id']);
        $aggy->createLead( $data, $current_user->id ?? 'Auto Generated');
        $aggy->joinAudience('leads', $data['client_id'], Lead::class);
        if($current_user){
            $aggy->claimLead($current_user->id, $data['client_id']);
        }
        $aggy->persist();
        return Lead::findOrFail($id);
 */

    }
/*
    public function authorize(ActionRequest $request): bool
    {
        $current_user = $request->user();
        return $current_user->can('leads.create', $current_user->currentTeam()->first());
    }

    public function asController(ActionRequest $request)
    {

        $lead = $this->handle(
            $request->validated(),
            $request->user(),
        );

  //      Alert::success("Lead '{$lead->name}' was created")->flash();

  //      return Redirect::route('data.leads.edit', $lead->id);
    }
*/
}
