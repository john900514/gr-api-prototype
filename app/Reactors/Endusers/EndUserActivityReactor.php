<?php

namespace App\Reactors\Endusers;

use App\Actions\Sms\Twilio\FireTwilioMsg;
use App\Mail\EndUser\EmailFromRep;
use App\Models\Endusers\Lead;
use App\Models\Endusers\LeadDetails;
use App\Models\Utility\AppState;
use App\StorableEvents\Endusers\LeadCreated;
use App\StorableEvents\Endusers\LeadWasEmailedByRep;
use App\StorableEvents\Endusers\LeadWasTextMessagedByRep;
use App\StorableEvents\Endusers\NewLeadMade;
use App\StorableEvents\Endusers\SubscribedToAudience;
use App\StorableEvents\Endusers\UpdateLead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class EndUserActivityReactor extends Reactor implements ShouldQueue
{
    public function onSubscribedToAudience(SubscribedToAudience $event)
    {
        // @todo - check the Campaigns the audience is attached to
        // @todo - if so, then trigger it here and its aggregate will deal
        // @todo - with whatever is supposed to happen.
    }

    public function onLeadCreated(LeadCreated $event)
    {
        if(array_key_exists('profile_picture', $event->data)){
            $file = $event->data['profile_picture'];
            $destKey = "{$event->data['client_id']}/{$file['uuid']}";
            Storage::disk('s3')->move($file['key'], $destKey);
            $file['key'] = $destKey;
            $file['url'] = "https://{$file['bucket']}.s3.amazonaws.com/{$file['key']}";

            LeadDetails::create([
                    'lead_id' => $event->data['id'],
                    'client_id' => $event->data['client_id'],
                    'field' => 'profile_picture',
                    'misc' => $file
                ]
            );
        }


        // @todo - dispatch a queued job that will apply that to all tracking reporting aggies.

    }
/*
    public function onNewLeadMade(NewLeadMade $event)
    {
        if(array_key_exists('profile_picture', $event->lead)){
            $file = $event->lead['profile_picture'];
            $destKey = "{$event->lead['client_id']}/{$file['uuid']}";
            Storage::disk('s3')->move($file['key'], $destKey);
            $file['key'] = $destKey;
            $file['url'] = "https://{$file['bucket']}.s3.amazonaws.com/{$file['key']}";

            LeadDetails::create([
                    'lead_id' => $event->lead['id'],
                    'client_id' => $event->lead['client_id'],
                    'field' => 'profile_picture',
                    'misc' => $file
                ]
            );
        }


        // @todo - dispatch a queued job that will apply that to all tracking reporting aggies.

    }
*/
    public function onUpdateLead(UpdateLead $event)
    {
        if(array_key_exists('profile_picture', $event->lead) && $event->lead['profile_picture'] !== null){
            $file = $event->lead['profile_picture'];
            $destKey = "{$event->lead['client_id']}/{$file['uuid']}";
            Storage::disk('s3')->move($file['key'], $destKey);
            $file['key'] = $destKey;
            $file['url'] = "https://{$file['bucket']}.s3.amazonaws.com/{$file['key']}";
            $profile_picture = LeadDetails::firstOrCreate([
                'lead_id' => $event->id,
                'client_id' => $event->lead['client_id'],
                'field' => 'profile_picture',
            ]);
            $profile_picture->misc =  $file;
            $profile_picture->save();
        }
    }
}
