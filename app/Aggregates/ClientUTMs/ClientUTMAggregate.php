<?php

namespace App\Aggregates\ClientUTMs;

use App\Models\Clients\Client;
use App\StorableEvents\Clients\UTM\UtmTemplateCreated;
use App\StorableEvents\Users\Activity\Impersonation\UserImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedBeingImpersonated;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserWasImpersonated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ClientUTMAggregate extends AggregateRoot
{

    public function createUtmTemplate(array $utmTemplate, string $user = null)
    {
        $this->recordThat(new UtmTemplateCreated($this->uuid(), $utmTemplate, $user ?? 'Auto Generated'));

        return $this;
    }

}
