<?php

namespace App\Aggregates\Clients\Reporting\Leads\Partials\Unique;

use App\Exceptions\Clients\ClientReportingException;
use App\Models\Endusers\Lead;
use App\StorableEvents\Clients\Reporting\Leads\UniqueLeadAddedToClientReporting;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class TotalUniqueClientLeads extends AggregatePartial
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

    public function addLead(string $lead_id, string $email, string $date) : self
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
        if(is_null($lead))
        {
            throw ClientReportingException::invalidLead($email);
        }

        $this->recordThat(new UniqueLeadAddedToClientReporting($this->aggregateRoot->getClientId(), $lead_id, $email, $date));
        return $this;
    }

    public function getReportCount() : array
    {
        $results = [
            'total' => count($this->all_lead_uuids),
            'start_date' => '',
            'end_date' => ''
        ];

        $idx = 0;
        foreach($this->all_lead_uuids_by_date as $date => $lead_set)
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
        $results = [
            'total' => count($this->all_lead_uuids),
            'leads' => [],
            'start_date' => '',
            'end_date' => ''
        ];

        $idx = 0;
        foreach($this->all_lead_uuids_by_date as $date => $lead_set)
        {
            if($idx == 0)
            {
                $results['start_date'] = $date;
                $results['leads'][$date] = [];
            }

            $lead_uuids = [];
            foreach($lead_set as $lead_uuid => $lead_details)
            {
                $lead_uuids[] = $lead_uuid;
            }
            $client_id = $this->aggregateRoot->getClientId();
            $lead_records = Lead::whereIn('id', $lead_uuids)
                ->whereClientId($client_id)->get();

            foreach($lead_records as $lead_record)
            {
                $results['leads'][$date][$lead_record->id] = [
                    'first_name' => $lead_record->first_name,
                    'last_name' => $lead_record->last_name,
                    'email' => $lead_record->email,
                    'location' => $lead_record->gr_location_id,
                ];
            }
            // This will change until the last date is run
            $results['end_date'] = $date;
        }

        return $results;
    }

    public function getReportCountByDate(string $start_date, string $end_date) : array
    {
        $results = [
            'total' => 0,
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

    public function getDetailedReportByDate(string $start_date, string $end_date) : array
    {
        $results = [
            'total' => 0,
            'dates' => [],
            'leads' => [],
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
                $results['leads'][$cur_date] = [];

                $lead_uuids = [];
                foreach($lead_set as $lead_uuid => $lead_details)
                {
                    $lead_uuids[] = $lead_uuid;
                }
                $client_id = $this->aggregateRoot->getClientId();
                $lead_records = Lead::whereIn('id', $lead_uuids)
                    ->whereClientId($client_id)->get();

                foreach($lead_records as $lead_record)
                {
                    $results['leads'][$cur_date][$lead_record->id] = [
                        'first_name' => $lead_record->first_name,
                        'last_name' => $lead_record->last_name,
                        'email' => $lead_record->email,
                        'location' => $lead_record->gr_location_id,
                    ];
                }
            }
            else
            {
                $results['dates'][$cur_date] = 0;
                $results['leads'][$cur_date] = [];
            }

            $cur_date = date('Y-m-d', strtotime("$cur_date +1DAY"));
        } while($cur_date != date('Y-m-d', strtotime("$end_date +1DAY")));

        return $results;
    }
}
