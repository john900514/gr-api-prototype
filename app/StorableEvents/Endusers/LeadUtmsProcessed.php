<?php

namespace App\StorableEvents\Endusers;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class LeadUtmsProcessed extends ShouldBeStored
{
    public $payload, $client;

    public function __construct($payload, $client)
    {
        $this->payload = $payload;
        $this->client = $client;
    }
}
