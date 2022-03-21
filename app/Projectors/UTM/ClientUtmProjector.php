<?php

namespace App\Projectors\UTM;

use App\Models\Note;
use App\Models\UtmTemplates;
use App\StorableEvents\Clients\UTM\UtmTemplateCreated;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class ClientUtmProjector extends Projector
{
    public function onNewLeadMade(UtmTemplateCreated $event)
    {
        UtmTemplates::create(array_merge($event->payload, [
            'client_id' => $event->aggregateRootUuid(),
            'created_by_user' => $event->user
        ]));
    }
}
