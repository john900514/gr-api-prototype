<?php

namespace App\Aggregates\Clients\Reporting\Leads\Partials\Daily;

use App\Exceptions\Clients\ClientReportingException;
use App\Models\Endusers\Lead;
use App\StorableEvents\Clients\Reporting\Leads\DailyLeadAddedToClientReporting;
use App\StorableEvents\Clients\Reporting\Leads\DailyLeadByLocationAddedToClientReporting;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class TotalDailyClientLeadsByLocation extends AggregatePartial
{
    protected array $all_client_locations_by_date = [];

    public function applyDailyLeadByLocationAddedToClientReporting(DailyLeadByLocationAddedToClientReporting $event)
    {
        if(!array_key_exists($event->club, $this->all_client_locations_by_date))
        {
            $this->all_client_locations_by_date[$event->club] = [];
        }

        if(!array_key_exists($event->date, $this->all_client_locations_by_date[$event->club]))
        {
            $this->all_client_locations_by_date[$event->club][$event->date] = [];
        }

        $this->all_client_locations_by_date[$event->club][$event->date][$event->lead] = ['email' => $event->email];
    }

    public function addLead(string $lead_id, string $email, string $date, string $gr_club_id)
    {
        // Check if the club is set in $all_client_locations
        if(array_key_exists($gr_club_id, $this->all_client_locations_by_date))
        {
            if(array_key_exists($date, $this->all_client_locations_by_date[$gr_club_id]))
            {
                if(array_key_exists($lead_id, $this->all_client_locations_by_date[$gr_club_id][$date]))
                {
                    throw ClientReportingException::dailyLeadAlreadyAdded($email);
                }

                // Check if email address does not exist in $all_lead_uuids or throw exception
                $check_email = collect($this->all_client_locations_by_date[$gr_club_id][$date])->where('email', '=', $email)->first();
                if(!is_null($check_email))
                {
                    throw ClientReportingException::dailyLeadAlreadyAdded($email);
                }
            }
        }

        // Lastly make sure the client id associated with the lead is the client here
        $lead = Lead::whereId($lead_id)->whereClientId($this->aggregateRoot->getClientId())->first();
        if(is_null($lead))
        {
            throw ClientReportingException::invalidLead($email);
        }

        $this->recordThat(new DailyLeadByLocationAddedToClientReporting($this->aggregateRoot->getClientId(), $lead_id, $email, $date, $gr_club_id));
        return $this;

    }

    public function getReportCountByDate(string $start_date, string $end_date) : array
    {
        $results = [
            'totals' => [],
            'dates' => [],
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $cur_date = $start_date;
        do
        {
            foreach($this->all_client_locations_by_date as $club_id => $date_lead_set)
            {
                if(!array_key_exists($club_id, $results['totals']))
                {
                    $results['totals'][$club_id] = 0;
                }

                if(!array_key_exists($cur_date, $results['dates']))
                {
                    $results['dates'][$cur_date] = [];
                }

                if(!array_key_exists($club_id, $results['dates'][$cur_date]))
                {
                    $results['dates'][$cur_date][$club_id] = 0;
                }


                if(array_key_exists($cur_date, $date_lead_set))
                {
                    $lead_set = $date_lead_set[$cur_date];
                    $results['totals'][$club_id] += count($lead_set);
                    $results['dates'][$cur_date][$club_id] += count($lead_set);
                }
                else
                {
                    $results['dates'][$cur_date] = [];
                    $results['dates'][$cur_date][$club_id] = 0;
                }
            }

            $cur_date = date('Y-m-d', strtotime("$cur_date +1DAY"));
        } while($cur_date != date('Y-m-d', strtotime("$end_date +1DAY")));

        return $results;
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
