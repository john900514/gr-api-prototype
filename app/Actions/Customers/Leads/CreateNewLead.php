<?php

namespace App\Actions\Customers\Leads;

use App\Aggregates\Endusers\EndUserActivityAggregate;
use App\Models\Endusers\Lead;
use Lorisleiva\Actions\Concerns\AsAction;
use Ramsey\Uuid\Uuid;

class CreateNewLead
{
    use AsAction;

    public function handle(string $client_id, array $prospect_data)
    {
        $results = false;
        // @todo - check if the lead already exists. If so, return the UpdateExistingLead action instead
        $existing_lead = Lead::whereClientId($client_id)->whereEmail($prospect_data['email'])->first();
        if(!is_null($existing_lead))
        {
            return AdditionalIntakeToExistingLead::run($client_id, $existing_lead->id, $prospect_data);
        }

        // Generate a UUID4
        $new_lead_id = Uuid::uuid4()->toString();

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
                ->createNewLead($payload);

            if(array_key_exists('utm', $prospect_data)){
                $aggy->processLeadUtms($prospect_data['utm'], $client_id);
            }

            // @todo - if there is an owner attached - do the claimed lead event and logic

            $aggy->persist();
            $results = $new_lead_id;
        }
        catch(\Exception $e)
        {
            // @todo - perhaps send the issue to Sentry? or replace with Aggregate type exceptions
        }

        return $results;
    }
}
