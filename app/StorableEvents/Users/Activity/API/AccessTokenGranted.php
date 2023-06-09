<?php

namespace App\StorableEvents\Users\Activity\API;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AccessTokenGranted extends ShouldBeStored
{
    public function __construct(public $user)
    {
    }
}
