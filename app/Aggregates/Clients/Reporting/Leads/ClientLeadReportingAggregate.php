<?php

namespace App\Aggregates\Clients\Reporting\Leads;

use App\Aggregates\Clients\Reporting\Leads\Partials\Daily\TotalDailyClientLeads;
use App\Aggregates\Clients\Reporting\Leads\Partials\Daily\TotalDailyClientLeadsByLeadSource;
use App\Aggregates\Clients\Reporting\Leads\Partials\Daily\TotalDailyClientLeadsByLeadType;
use App\Aggregates\Clients\Reporting\Leads\Partials\Daily\TotalDailyClientLeadsByLocation;
use App\Aggregates\Clients\Reporting\Leads\Partials\Organic\TotalDailyClientOrganicLeads;
use App\Aggregates\Clients\Reporting\Leads\Partials\Organic\TotalDailyClientOrganicLeadsByLeadSource;
use App\Aggregates\Clients\Reporting\Leads\Partials\Organic\TotalDailyClientOrganicLeadsByLeadType;
use App\Aggregates\Clients\Reporting\Leads\Partials\Organic\TotalDailyClientOrganicLeadsByLocation;
use App\Aggregates\Clients\Reporting\Leads\Partials\Unique\TotalUniqueClientLeads;
use App\Aggregates\Clients\Reporting\Leads\Partials\Unique\TotalUniqueClientLeadsByLeadSource;
use App\Aggregates\Clients\Reporting\Leads\Partials\Unique\TotalUniqueClientLeadsByLeadType;
use App\Aggregates\Clients\Reporting\Leads\Partials\Unique\TotalUniqueClientLeadsByLocation;
use App\Aggregates\Clients\Reporting\Leads\Partials\Utm\TotalDailyClientUTMLeads;
use App\Aggregates\Clients\Reporting\Leads\Partials\Utm\TotalDailyClientUTMLeadsByLeadSource;
use App\Aggregates\Clients\Reporting\Leads\Partials\Utm\TotalDailyClientUTMLeadsByLeadType;
use App\Aggregates\Clients\Reporting\Leads\Partials\Utm\TotalDailyClientUTMLeadsByLocation;
use App\Exceptions\Clients\ClientReportingException;
use App\StorableEvents\Clients\Reporting\Leads\ClientAssignedToLeadReportingLine;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ClientLeadReportingAggregate extends AggregateRoot
{
    protected string $client_id = '';

    // Unique Leads
    protected TotalUniqueClientLeads $total_unique_client_leads;
    protected TotalUniqueClientLeadsByLocation $total_unique_client_leads_by_location;
    //protected TotalUniqueClientLeadsByLeadSource $total_unique_client_leads_by_lead_source;
    //protected TotalUniqueClientLeadsByLeadType $total_unique_client_leads_by_lead_type;
    // Daily Leads
    protected TotalDailyClientLeads $total_daily_client_leads;
    protected TotalDailyClientLeadsByLocation $total_daily_client_leads_by_location;
    //protected TotalDailyClientLeadsByLeadSource $total_daily_client_leads_by_lead_source;
    //protected TotalDailyClientLeadsByLeadType $total_daily_client_leads_by_lead_type;
    // Organic (non-utm) Leads
    protected TotalDailyClientOrganicLeads $total_daily_client_organic_leads;
    //protected TotalDailyClientOrganicLeadsByLocation $total_daily_client_organic_leads_by_location;
    //protected TotalDailyClientOrganicLeadsByLeadSource $total_daily_client_organic_leads_by_lead_source;
    //protected TotalDailyClientOrganicLeadsByLeadType $total_daily_client_organic_leads_by_lead_type;
    // UTM Leads
    protected TotalDailyClientUTMLeads $total_daily_client_utm_leads;
    //protected TotalDailyClientUTMLeadsByLocation $total_daily_client_utm_leads_by_location;
    //protected TotalDailyClientUTMLeadsByLeadSource $total_daily_client_utm_leads_by_lead_source;
    //protected TotalDailyClientUTMLeadsByLeadType $total_daily_client_utm_leads_by_lead_type;

    public function __construct()
    {
        // Unique Leads
        $this->total_unique_client_leads = new TotalUniqueClientLeads($this);
        $this->total_unique_client_leads_by_location = new TotalUniqueClientLeadsByLocation($this);
        //$this->total_unique_client_leads_by_lead_source = new TotalUniqueClientLeadsByLeadSource($this);
        //$this->total_unique_client_leads_by_lead_type = new TotalUniqueClientLeadsByLeadType($this);

        // Daily Leads
        $this->total_daily_client_leads  = new TotalDailyClientLeads($this);
        $this->total_daily_client_leads_by_location  = new TotalDailyClientLeadsByLocation($this);
        //$this->total_daily_client_leads_by_lead_source  = new TotalDailyClientLeadsByLeadSource($this);
        //$this->total_daily_client_leads_by_lead_type  = new TotalDailyClientLeadsByLeadType($this);

        // Organic (non-utm) Leads
        $this->total_daily_client_organic_leads = new TotalDailyClientOrganicLeads($this);
        //$this->total_daily_client_organic_leads_by_location = new TotalDailyClientOrganicLeadsByLocation($this);
        //$this->total_daily_client_organic_leads_by_lead_source = new TotalDailyClientOrganicLeadsByLeadSource($this);
        //$this->total_daily_client_organic_leads_by_lead_type = new TotalDailyClientOrganicLeadsByLeadType($this);

        // UTM Leads
        $this->total_daily_client_utm_leads = new TotalDailyClientUTMLeads($this);
        //$this->total_daily_client_utm_leads_by_location = new TotalDailyClientUTMLeadsByLocation($this);
        //$this->total_daily_client_utm_leads_by_lead_source = new TotalDailyClientUTMLeadsByLeadSource($this);
        //$this->total_daily_client_utm_leads_by_lead_type = new TotalDailyClientUTMLeadsByLeadType($this);
    }

    public function applyClientAssignedToLeadReportingLine(ClientAssignedToLeadReportingLine $event)
    {
        $this->client_id = $event->client;
    }

    public function setClientId(string $client_id) : self
    {
        if($this->client_id != '')
        {
            throw ClientReportingException::cannotChangeClientOnReporting();
        }

        $this->recordThat(new ClientAssignedToLeadReportingLine($client_id));

        return $this;
    }

    // Unique Lead
    public function addUniqueLead(string $lead_id, string $email, string $date) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_unique_client_leads->addLead($lead_id, $email, $date);
        return $this;
    }
    public function addUniqueLeadViaLocation(string $lead_id, string $email, string $date, string $gr_club_id) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_unique_client_leads_by_location->addLead($lead_id, $email, $date, $gr_club_id);
        return $this;
    }
    // Daily Lead
    public function addDailyLead(string $lead_id, string $email, string $date) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_daily_client_leads->addLead($lead_id, $email, $date);
        return $this;
    }
    public function addDailyLeadViaLocation(string $lead_id, string $email, string $date, string $gr_club_id) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_daily_client_leads_by_location->addLead($lead_id, $email, $date, $gr_club_id);
        return $this;
    }
    // Organic Lead
    public function addOrganicLead(string $lead_id, string $email, string $date) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_daily_client_organic_leads->addLead($lead_id, $email, $date);
        return $this;
    }
    // UTM Lead
    public function addUTMLead(string $lead_id, string $email, string $date, array $utms) : self
    {
        if($this->client_id == '')
        {
            throw ClientReportingException::missingClientID();
        }

        $this->total_daily_client_utm_leads->addLead($lead_id, $email, $date, $utms);
        return $this;
    }

    public function getClientId() : string
    {
        return $this->client_id;
    }

    // Unique Lead Reports
    public function getTotalUniqueLeads() : array
    {
        return $this->total_unique_client_leads->getReportCount();
    }
    public function getTotalUniqueLeadsDetailed() : array
    {
        return $this->total_unique_client_leads->getDetailedReport();
    }
    public function getTotalUniqueLeadsByLocation() : array
    {
        return $this->total_unique_client_leads_by_location->getReportCount();
    }
    public function getTotalUniqueLeadsByLocationDetailed() : array
    {
        return $this->total_unique_client_leads_by_location->getDetailedReport();
    }

    // Daily Lead Reports
    public function getTotalDailyLeads(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_leads->getReportCountByDate($start_date, $end_date);
    }
    public function getTotalDailyLeadsDetailed(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_leads->getDetailedReportByDate($start_date, $end_date);
    }
    public function getTotalDailyLeadsByLocation(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_leads_by_location->getReportCountByDate($start_date, $end_date);
    }
    public function getTotalDailyLeadsByLocationDetailed(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_leads_by_location->getReportCountByDate($start_date, $end_date);
    }

    // Organic Lead Reports
    public function getTotalDailyOrganicLeads(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_organic_leads->getReportCountByDate($start_date, $end_date);
    }
    public function getTotalDailyOrganicLeadsDetailed(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_organic_leads->getDetailedReportByDate($start_date, $end_date);
    }

    // UTM Lead Reports
    public function getTotalDailyUTMLeads(string $start_date, string $end_date) : array
    {
        return $this->total_daily_client_utm_leads->getReportCountByDate($start_date, $end_date);
    }
}
