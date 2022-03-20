<?php

namespace App\Jobs\Clients\Reporting;

use App\Aggregates\Clients\Reporting\Leads\ClientLeadReportingAggregate;
use App\Aggregates\Endusers\EndUserActivityAggregate;
use App\Exceptions\Clients\ClientReportingException;
use App\Models\Clients\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssimilateLeadIntoReporting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected string $client_id,
                                protected string $lead_id,
                                protected string $gr_club_id,
                                protected string $lead_source_id,
                                protected string $lead_type_id,
                                protected array  $utm = [])
    {
        // When you declare class variables in the arguments of the __construct method #php8
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $clients_model)
    {
        $client = $clients_model->whereId($this->client_id)->with('lead_reports_uuid')->first();

        if(!is_null($client))
        {
            if(!is_null($client->lead_reports_uuid))
            {
                $lead_aggy = EndUserActivityAggregate::retrieve($this->lead_id);
                $lead_email = $lead_aggy->getLeadData()['email'] ?? null;

                if(is_null($lead_email))
                {
                    // @todo - get rid of the dd && throw an exception
                    dd($lead_aggy->getLeadData());
                }

                $date = date('Y-m-d');
                $client_lead_reporting_uuid = $client->lead_reports_uuid->value;

                try {
                    $aggy = ClientLeadReportingAggregate::retrieve($client_lead_reporting_uuid);
                }
                catch(\Exception $e)
                {
                    dd('dafaq - '.$e->getMessage(), $e);
                }

                // Unique Lead
                try { $aggy = $aggy->addUniqueLead($this->lead_id, $lead_email, $date); }catch(ClientReportingException $e) {  }
                try { $aggy = $aggy->addUniqueLeadViaLocation($this->lead_id, $lead_email, $date, $this->gr_club_id); }catch(ClientReportingException $e) {  }
                // Daily Lead
                try { $aggy = $aggy->addDailyLead($this->lead_id, $lead_email, $date);  }catch(ClientReportingException $e) {  }
                try { $aggy = $aggy->addDailyLeadViaLocation($this->lead_id, $lead_email, $date, $this->gr_club_id);  }catch(ClientReportingException $e) {  }
                if(1 == 2)
                {
                    // UTM Lead
                    // @todo - get the UTMs for the lead based on the session
                    $utms = [];
                    try { $aggy = $aggy->addUTMLead($this->lead_id, $lead_email, $date, $utms); }catch(ClientReportingException $e) {}
                }
                else
                {
                    // Organic Lead
                    try { $aggy = $aggy->addOrganicLead($this->lead_id, $lead_email, $date); }catch(ClientReportingException $e) {  }
                }

                // @todo - add other reports
                $aggy->persist();
        }
            else
            {
                // @todo - throw an exception
            }
        }
        else
        {
            // @todo - throw an exception
        }

    }
}
