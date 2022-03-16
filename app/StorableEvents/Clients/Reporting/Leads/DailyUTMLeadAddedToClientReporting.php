<?php

namespace App\StorableEvents\Clients\Reporting\Leads;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class DailyUTMLeadAddedToClientReporting extends ShouldBeStored
{
    public function __construct(public $client,
                                public $lead,
                                public $email,
                                public $date,
                                public array $utm)
    {
    }
}
