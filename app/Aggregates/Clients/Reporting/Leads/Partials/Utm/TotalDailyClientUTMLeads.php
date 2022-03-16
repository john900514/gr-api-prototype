<?php

namespace App\Aggregates\Clients\Reporting\Leads\Partials\Utm;

use App\Exceptions\Clients\ClientReportingException;
use App\Models\Endusers\Lead;
use App\StorableEvents\Clients\Reporting\Leads\DailyLeadAddedToClientReporting;
use App\StorableEvents\Clients\Reporting\Leads\DailyUTMLeadAddedToClientReporting;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class TotalDailyClientUTMLeads extends AggregatePartial
{
    protected array $all_lead_uuids_by_date = [];

    public function applyDailyUTMLeadAddedToClientReporting(DailyUTMLeadAddedToClientReporting $event)
    {
        if(!array_key_exists($event->date, $this->all_lead_uuids_by_date))
        {
            $this->all_lead_uuids_by_date[$event->date] = [];
        }
        $this->all_lead_uuids_by_date[$event->date][$event->lead] = ['email' => $event->email];
    }

    public function addLead(string $lead_id, string $email, string $date, array $utms) : self
    {
        // Check if the UUID does not exist in $all_lead_uuids or throw exception
        if(array_key_exists($date, $this->all_lead_uuids_by_date))
        {
            if(array_key_exists($lead_id, $this->all_lead_uuids_by_date[$date]))
            {
                throw ClientReportingException::dailyUTMLeadAlreadyAdded($email);
            }

            // Check if email address does not exist in $all_lead_uuids or throw exception
            $check_email = collect($this->all_lead_uuids_by_date[$date])->where('email', '=', $email)->first();
            if(!is_null($check_email))
            {
                throw ClientReportingException::dailyUTMLeadAlreadyAdded($email);
            }

        }

        // Lastly make sure the client id associated with the lead is the client here
        $lead = Lead::whereId($lead_id)->whereClientId($this->aggregateRoot->getClientId())->first();
        if(is_null($lead))
        {
            throw ClientReportingException::invalidLead($email);
        }

        $this->recordThat(new DailyUTMLeadAddedToClientReporting($this->aggregateRoot->getClientId(), $lead_id, $email, $date, $utms));
        return $this;

    }

    public function getReportCountByDate(string $start_date, string $end_date) : array
    {
        $results = [
            'total' => 0,
            'campaigns' => [],
            'dates' => [],
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $cur_date = $start_date;
        do
        {
            if(array_key_exists($cur_date, $this->all_lead_uuids_by_date))
            {
                $lead_set = $this->all_lead_uuids_by_date[$cur_date];
                $results['total'] += count($lead_set);
                $results['dates'][$cur_date] = count($lead_set);
            }
            else
            {
                $results['dates'][$cur_date] = 0;
            }

            $cur_date = date('Y-m-d', strtotime("$cur_date +1DAY"));
        } while($cur_date != date('Y-m-d', strtotime("$end_date +1DAY")));

        return $results;
    }

    public function getDetailedReportByDate()
    {

    }
}
