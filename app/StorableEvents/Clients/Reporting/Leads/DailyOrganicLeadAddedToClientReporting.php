<?php

namespace App\StorableEvents\Clients\Reporting\Leads;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class DailyOrganicLeadAddedToClientReporting extends ShouldBeStored
{
    public function __construct(public $client,
                                public $lead,
                                public $email,
                                public $date)
    {
    }
}
