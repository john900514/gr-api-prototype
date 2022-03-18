<?php

namespace App\Actions\Customers\Leads;

use App\Aggregates\Endusers\EndUserActivityAggregate;
use App\Models\Endusers\Lead;
use Lorisleiva\Actions\Concerns\AsAction;

class AdditionalIntakeToExistingLead
{
    use AsAction;

    public function handle(string $client_id, string $lead_id, array $prospect_data)
    {
        $results = false;

        $existing_lead = Lead::whereClientId($client_id)->whereEmail($prospect_data['email'])->first();

        if(!is_null($existing_lead))
        {
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

            // @todo - insert UTM, if in the request, here
            // Call the EndUserActivityAggregate and persists create New Lead
            try {
                $aggy = EndUserActivityAggregate::retrieve($lead_id)
                    ->addAdditionalLeadIntakeActivity($payload);

                // @todo - insert additional events to log here.
                $aggy->persist();
                $results = $lead_id;
            }
            catch(\Exception $e)
            {
                // @todo - perhaps send the issue to Sentry? or replace with Aggregate type exceptions
            }
        }

        return $results;
    }
}
