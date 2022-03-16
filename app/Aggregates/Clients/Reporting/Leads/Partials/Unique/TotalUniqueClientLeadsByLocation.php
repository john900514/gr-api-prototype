<?php

namespace App\Aggregates\Clients\Reporting\Leads\Partials\Unique;

use App\Exceptions\Clients\ClientReportingException;
use App\Models\Endusers\Lead;
use App\StorableEvents\Clients\Reporting\Leads\UniqueLeadAddedToClientReporting;
use App\StorableEvents\Clients\Reporting\Leads\UniqueLeadByLocationAddedToClientReporting;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class TotalUniqueClientLeadsByLocation extends AggregatePartial
{
    protected array $all_client_locations = [];
    protected array $all_client_locations_by_date = [];

    public function applyUniqueLeadByLocationAddedToClientReporting(UniqueLeadByLocationAddedToClientReporting $event)
    {
        if(!array_key_exists($event->club, $this->all_client_locations))
        {
            $this->all_client_locations[$event->club] = [];
        }

        $this->all_client_locations[$event->club][$event->lead] = ['email' => $event->email];

        if(!array_key_exists($event->date, $this->all_client_locations_by_date))
        {
            $this->all_client_locations_by_date[$event->date] = [];
        }

        if(!array_key_exists($event->club, $this->all_client_locations_by_date[$event->date]))
        {
            $this->all_client_locations_by_date[$event->date][$event->club] = [];
        }
        $this->all_client_locations_by_date[$event->date][$event->club][$event->lead] = ['email' => $event->email];
    }

    public function addLead(string $lead_id, string $email, string $date, string $gr_club_id)
    {
        // Check if the club is set in $all_client_locations
        if(array_key_exists($gr_club_id, $this->all_client_locations))
        {
            // Check if the UUID does not exist in $all_lead_uuids or throw exception
            if(array_key_exists($lead_id, $this->all_client_locations[$gr_club_id]))
            {
                throw ClientReportingException::cannotAddUniqueLead($email);
            }

            // Check if email address does not exist in $all_lead_uuids or throw exception
            $check_email = collect($this->all_client_locations[$gr_club_id])->where('email', '=', $email)->first();
            if(!is_null($check_email))
            {
                throw ClientReportingException::cannotAddUniqueLead($email);
            }
        }

        // Lastly make sure the client id associated with the lead is the client here
        $lead = Lead::whereId($lead_id)->whereClientId($this->aggregateRoot->getClientId())->first();
        if(is_null($lead))
        {
            throw ClientReportingException::invalidLead($email);
        }

        $this->recordThat(new UniqueLeadByLocationAddedToClientReporting($this->aggregateRoot->getClientId(), $lead_id, $email, $date, $gr_club_id));
        return $this;
    }

    public function getReportCount() : array
    {
        $results = [
            'totals' => [],
            'start_date' => '',
            'end_date' => ''
        ];

        foreach ($this->all_client_locations as $location => $lead_set)
        {
            $results['totals'][$location] = count($lead_set);
        }

        $idx = 0;
        foreach($this->all_client_locations_by_date as $date => $lead_set)
        {
            if($idx == 0)
            {
                $results['start_date'] = $date;
            }

            // This will change until the last date is run
            $results['end_date'] = $date;
        }

        return $results;
    }

    public function getDetailedReport() : array
    {
        return [];
    }

    public function getReportCountByDate()
    {

    }

    public function getDetailedReportByDate()
    {

    }
}
