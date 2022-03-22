<?php

namespace App\Aggregates\Clients\UTMs;

use App\StorableEvents\Clients\UTM\UtmTemplateCreated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ClientUTMAggregate extends AggregateRoot
{
    protected array $templates = [];

    public function applyUtmTemplateCreated(UtmTemplateCreated $event) : void
    {
        $this->templates[$event->payload['id']]  = $event->payload;
    }

    public function createUtmTemplate(array $utmTemplate, string $user = null) : self
    {
        $this->recordThat(new UtmTemplateCreated($this->uuid(), $utmTemplate, $user ?? 'Auto Generated'));

        return $this;
    }

}
