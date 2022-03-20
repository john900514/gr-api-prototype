<?php

namespace App\Aggregates\Clients\Reporting\Leads\Partials\Unique;

use App\Exceptions\Clients\ClientReportingException;
use App\Models\Endusers\Lead;
use App\StorableEvents\Clients\Reporting\Leads\UniqueLeadAddedToClientReporting;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class TotalUniqueClientLeadsByLeadSource extends AggregatePartial
{
    protected array $all_lead_uuids = [];
    protected array $all_lead_uuids_by_date = [];

    public function applyUniqueLeadAddedToClientReporting(UniqueLeadAddedToClientReporting $event)
    {
        $this->all_lead_uuids[$event->lead] = ['email' => $event->email];
        if(!array_key_exists($event->date, $this->all_lead_uuids_by_date))
        {
            $this->all_lead_uuids_by_date[$event->date] = [];
        }
        $this->all_lead_uuids_by_date[$event->date][$event->lead] = ['email' => $event->email];
    }

    public function addLead(string $lead_id, string $email, string $date)
    {
        // Check if the UUID does not exist in $all_lead_uuids or throw exception
        if(array_key_exists($lead_id, $this->all_lead_uuids))
        {
            throw ClientReportingException::cannotAddUniqueLead($email);
        }

        // Check if email address does not exist in $all_lead_uuids or throw exception
        $check_email = collect($this->all_lead_uuids)->where('email', '=', $email)->first();
        if(!is_null($check_email))
        {
            throw ClientReportingException::cannotAddUniqueLead($email);
        }

        // Lastly make sure the client id associated with the lead is the client here
        $lead = Lead::whereId($lead_id)->whereClientId($this->aggregateRoot->getClientId())->first();
        if(!is_null($lead))
        {
            throw ClientReportingException::invalidLead($email);
        }

        $this->recordThat(new UniqueLeadAddedToClientReporting($this->aggregateRoot->getClientId(), $lead_id, $email, $date));
        return $this;
    }

    public function getReportCount()
    {

    }

    public function getDetailedReport()
    {

    }

    public function getReportCountByDate()
    {

    }

    public function getDetailedReportByDate()
    {

    }

    public function getReportCountByDateRange()
    {

    }

    public function getDetailedReportByDateRange()
    {

    }
}
