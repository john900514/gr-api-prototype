<?php

namespace App\Actions\Clients\Reporting\Leads;

use App\Aggregates\Clients\Reporting\Leads\ClientLeadReportingAggregate;
use App\Models\Clients\Client;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class GetTotalUniqueLeadsReport
{
    use AsAction;

    public function handle(User $user, Client $client, bool $detailed = false)
    {
        $results = false;

        $lead_report_uuid_record = $client->lead_reports_uuid()->first();

        if(!is_null($lead_report_uuid_record))
        {
            $aggy = ClientLeadReportingAggregate::retrieve($lead_report_uuid_record->value);

            $results = $detailed
                ? $aggy->getTotalUniqueLeadsDetailed()
                : $aggy->getTotalUniqueLeads();
        }
        else
        {
            // @todo throw an exception
            // false is okay for now
        }

        return $results;
    }
}
