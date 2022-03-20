<?php

namespace App\Aggregates\ClientUTMs;

use App\Models\Clients\Client;
use App\StorableEvents\Users\Activity\Impersonation\UserImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedBeingImpersonated;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserWasImpersonated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ClientUTMAggregate extends AggregateRoot{

}
