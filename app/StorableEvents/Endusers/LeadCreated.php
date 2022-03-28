<?php

namespace App\StorableEvents\Endusers;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class LeadCreated extends ShouldBeStored
{
    public $id,  $lead;

    public function __construct(string $id,  array $lead)
    {
        $this->id = $id;
        $this->lead = $lead;

    }


}
