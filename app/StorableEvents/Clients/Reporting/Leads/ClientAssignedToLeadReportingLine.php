<?php

namespace App\StorableEvents\Clients\Reporting\Leads;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ClientAssignedToLeadReportingLine extends ShouldBeStored
{
    public function __construct(public string $client)
    {
    }
}
