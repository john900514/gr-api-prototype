<?php

namespace App\Projectors\Endusers;

use App\Jobs\Clients\Reporting\AssimilateLeadIntoReporting;
use App\Models\Clients\Features\CommAudience;
use App\Models\Clients\Features\Memberships\TrialMembershipType;
use App\Models\Endusers\AudienceMember;
use App\Models\Endusers\Lead;
use App\Models\Endusers\LeadDetails;
use App\Models\Endusers\TrialMembership;
use App\Models\Note;
use App\Models\User;
use App\Models\Utms;
use App\Models\UtmTemplates;
use App\StorableEvents\Endusers\AdditionalLeadIntakeCaptured;
use App\StorableEvents\Endusers\AgreementNumberCreatedForLead;
use App\StorableEvents\Endusers\LeadClaimedByRep;
use App\StorableEvents\Endusers\LeadDetailUpdated;
use App\StorableEvents\Endusers\LeadUtmsProcessed;
use App\StorableEvents\Endusers\LeadWasCalledByRep;
use App\StorableEvents\Endusers\LeadWasDeleted;
use App\StorableEvents\Endusers\LeadWasEmailedByRep;
use App\StorableEvents\Endusers\LeadWasTextMessagedByRep;
use App\StorableEvents\Endusers\ManualLeadMade;
use App\StorableEvents\Endusers\NewLeadMade;
use App\StorableEvents\Endusers\LeadCreated;

use App\StorableEvents\Endusers\SubscribedToAudience;
use App\StorableEvents\Endusers\TrialMembershipAdded;
use App\StorableEvents\Endusers\TrialMembershipUsed;
use App\StorableEvents\Endusers\UpdateLead;
use Illuminate\Support\Facades\Log;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class EndUserActivityProjector extends Projector
{

    public function onLeadCreated(LeadCreated $event)
    {
        //get only the keys we care about (the ones marked as fillable)
        $lead_table_data = array_filter($event->data, function ($key) {
            return in_array($key, (new Lead)->getFillable());
        }, ARRAY_FILTER_USE_KEY);
        $lead = Lead::create($lead_table_data);
        $lead->update(['id' => $event->user]);

        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'created',
            'value' => $lead->created_at
        ]);

        // Intake Activity is used to track if a lead was already created and was captured again later
        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'intake-activity',
            'value' => $lead->created_at,
            'misc' => $event->data
        ]);

        LeadDetails::create([
            'lead_id' => $event->user,
            'client_id' => $lead->client_id,
            'field' => 'agreement_number',
            'value' => floor(time() - 99999999),
        ]);
        if (!is_null($event->data['dob'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'dob',
                'value' => $event->data['dob'],
            ]);
        }
        if (!is_null($event->data['middle_name'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'middle_name',
                'value' => $event->data['middle_name'],
            ]);
        }
        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'opportunity',
            'value' => 'High'
        ]);


        if (!is_null($event->data['owner_id'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'claimed',
                'value' => $event->data['owner_id'],
            ]);
        }
        if (!is_null($event->data['misc'])) {
            $created = LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'misc-props',
                'value' => $event->data['misc'],
            ]);
        }


        foreach ($event->lead['details'] ?? [] as $field => $value) {
            LeadDetails::create([
                    'lead_id' => $event->aggregateRootUuid(),
                    'client_id' => $lead->client_id,
                    'field' => $field,
                    'value' => $value
                ]
            );
        }

        // @todo - deprecated, should remove soon
        foreach ($event->lead['services'] ?? [] as $service_id) {
            LeadDetails::create([
                    'lead_id' => $event->aggregateRootUuid(),
                    'client_id' => $lead->client_id,
                    'field' => 'service_id',
                    'value' => $service_id
                ]
            );
        }

        // From here we will queue and dispatch a job that will process
        // the lead with Client Reporting
        AssimilateLeadIntoReporting::dispatch(
            $lead->client_id, $lead->id, $event->data['gr_location_id'],
            $event->data['lead_source_id'], $event->data['lead_type_id'],
            $event->data['utm'] ?? []
        )->onQueue('gapi-' . env('APP_ENV') . '-jobs');
    }




    public function onNewLeadMade(NewLeadMade $event)
    {
        //get only the keys we care about (the ones marked as fillable)
        $lead_table_data = array_filter($event->lead, function ($key) {
            return in_array($key, (new Lead)->getFillable());
        }, ARRAY_FILTER_USE_KEY);
        $lead = Lead::create($lead_table_data);
        $lead->update(['id' => $event->id]);

        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'created',
            'value' => $lead->created_at
        ]);

        // Intake Activity is used to track if a lead was already created and was captured again later
        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'intake-activity',
            'value' => $lead->created_at,
            'misc' => $event->lead
        ]);

        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $lead->client_id,
            'field' => 'agreement_number',
            'value' => floor(time() - 99999999),
        ]);
        if (!is_null($event->lead['dob'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'dob',
                'value' => $event->lead['dob'],
            ]);
        }
        if (!is_null($event->lead['middle_name'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'middle_name',
                'value' => $event->lead['middle_name'],
            ]);
        }
        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'opportunity',
            'value' => 'High'
        ]);


        if (!is_null($event->lead['owner_id'])) {
            LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'claimed',
                'value' => $event->lead['owner_id'],
            ]);
        }
        if (!is_null($event->lead['misc'])) {
            $created = LeadDetails::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'field' => 'misc-props',
                'value' => $event->lead['misc'],
            ]);
        }


        foreach ($event->lead['details'] ?? [] as $field => $value) {
            LeadDetails::create([
                    'lead_id' => $event->aggregateRootUuid(),
                    'client_id' => $lead->client_id,
                    'field' => $field,
                    'value' => $value
                ]
            );
        }

        // @todo - deprecated, should remove soon
        foreach ($event->lead['services'] ?? [] as $service_id) {
            LeadDetails::create([
                    'lead_id' => $event->aggregateRootUuid(),
                    'client_id' => $lead->client_id,
                    'field' => 'service_id',
                    'value' => $service_id
                ]
            );
        }

        // From here we will queue and dispatch a job that will process
        // the lead with Client Reporting
        AssimilateLeadIntoReporting::dispatch(
            $lead->client_id, $lead->id, $event->lead['gr_location_id'],
            $event->lead['lead_source_id'], $event->lead['lead_type_id'],
            $event->lead['utm'] ?? []
        )->onQueue('gapi-' . env('APP_ENV') . '-jobs');
    }

    public function onAdditionalLeadIntakeCaptured(AdditionalLeadIntakeCaptured $event)
    {
        /**
         * NOTE! - Because this is a lead being captured again, and not a lead's data being updated,
         * it doesn't matter if the lead's data is different than in the record. Just log it and
         * send it to reporting.
         */
        $lead = Lead::find($event->id);
        // Intake Activity is used to track if a lead was already created and was captured again later
        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'intake-activity',
            'value' => $lead->created_at,
            'misc' => $event->lead
        ]);


        // From here we will queue and dispatch a job that will process
        // the lead with Client Reporting
        AssimilateLeadIntoReporting::dispatch(
            $lead->client_id, $lead->id, $event->lead['gr_location_id'],
            $event->lead['lead_source_id'], $event->lead['lead_type_id'],
            $event->lead['utm'] ?? []
        )->onQueue('gapi-' . env('APP_ENV') . '-jobs');

    }

    public function onManualLeadMade(ManualLeadMade $event)
    {
        $user = User::find($event->user);
        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $event->lead['client_id'],
            'field' => 'manual_create',
            'value' => $user->email,
        ]);
        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $event->lead['client_id'],
            'field' => 'created',
            'value' => Carbon::now()
        ]);
        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $event->lead['client_id'],
            'field' => 'agreement_number',
            'value' => floor(time() - 99999999),
        ]);

        LeadDetails::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'field' => 'dob',
            'value' => $event->dob,
            'misc' => [$event]
        ]);
    }

    public function onLeadDetailUpdated(LeadDetailUpdated $event)
    {
        $detail = LeadDetails::firstOrCreate([
            'lead_id' => $event->lead,
            'client_id' => $event->client,
            'field' => $event->key,
        ]);

        $detail->value = $event->value;
        $misc = ['user' => $event->user];
        $detail->misc = $misc;
        $detail->save();
    }

    public function onAgreementNumberCreatedForLead(AgreementNumberCreatedForLead $event)
    {
        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $event->client,
            'field' => 'agreement_number',
            'value' => $event->agreement,
        ]);
    }

    public function onUpdateLead(UpdateLead $event)
    {
        $lead = Lead::findOrFail($event->id);
        $old_data = $lead->toArray();
        $user = User::find($event->user);
        $lead->updateOrFail($event->lead);

        $notes = $event->lead['notes'] ?? false;
        if ($notes) {
            Note::create([
                'entity_id' => $event->id,
                'entity_type' => Lead::class,
                'note' => $notes,
                'created_by_user_id' => $event->user
            ]);
            LeadDetails::create([
                'lead_id' => $event->id,
                'client_id' => $lead->client_id,
                'field' => 'note_created',
                'value' => $notes,
            ]);
        }

        LeadDetails::whereLeadId($event->id)->whereField('service_id')->delete();
        /*
        foreach ($event->lead['services'] ?? [] as $service_id) {
            LeadDetails::firstOrCreate([
                    'lead_id' => $event->aggregateRootUuid(),
                    'client_id' => $lead->client_id,
                    'field' => 'service_id',
                    'value' => $service_id
                ]
            );
        }
        */

        LeadDetails::create([
            'lead_id' => $event->id,
            'client_id' => $lead->client_id,
            'field' => 'updated',
            'value' => $user->email,
            'misc' => [
                'old_data' => $old_data,
                'new_data' => $event->lead,
                'user' => $event->user
            ]
        ]);
    }

    public function onLeadClaimedByRep(LeadClaimedByRep $event)
    {
        $lead = LeadDetails::firstOrCreate([
            'client_id' => $event->client,
            'lead_id' => $event->lead,
            'field' => 'claimed',
        ]);

        $lead->value = $event->user;
        $misc = $lead->misc;
        if (!is_array($misc)) {
            $misc = [];
        }

        if (!array_key_exists('claim_date', $misc)) {
            $misc['claim_date'] = date('Y-m-d');
        }

        $user = User::find($event->user);
        $misc['user_id'] = $user->email;
        $lead->misc = $misc;
        $lead->save();
    }

    public function onLeadWasEmailedByRep(LeadWasEmailedByRep $event)
    {
        $lead = Lead::find($event->lead);
        $user = User::find($event->user);

        $misc = $event->data;
        $misc['user_email'] = $user->email;
        LeadDetails::firstOrCreate([
            'client_id' => $lead->client_id,
            'lead_id' => $event->lead,
            'field' => 'emailed_by_rep',
            'value' => $event->user,
            'misc' => $misc,
        ]);
    }

    public function onLeadWasTextMessagedByRep(LeadWasTextMessagedByRep $event)
    {
        $lead = Lead::find($event->lead);
        $user = User::find($event->user);

        $misc = $event->data;
        $misc['user_email'] = $user->email;
        LeadDetails::firstOrCreate([
            'client_id' => $lead->client_id,
            'lead_id' => $event->lead,
            'field' => 'sms_by_rep',
            'value' => $event->user,
            'misc' => $misc,
        ]);

    }

    public function onLeadWasCalledByRep(LeadWasCalledByRep $event)
    {
        $lead = Lead::find($event->lead);
        $user = User::find($event->user);

        $misc = $event->data;
        $misc['user_email'] = $user->email;
        LeadDetails::firstOrCreate([
            'client_id' => $lead->client_id,
            'lead_id' => $event->lead,
            'field' => 'called_by_rep',
            'value' => $event->user,
            'misc' => $misc,
        ]);

        $notes = $misc['notes'] ?? false;
        if ($notes) {
            Note::create([
                'entity_id' => $event->lead,
                'entity_type' => Lead::class,
                'note' => $notes,
                'created_by_user_id' => $event->user
            ]);
        }
    }

    public function onSubscribedToAudience(SubscribedToAudience $event)
    {
        $audience_record = CommAudience::whereClientId($event->client)
            ->whereSlug($event->audience)->whereActive(1)->first();

        if (!is_null($audience_record)) {
            // add a new record to audience_members
            $audience_member_record = AudienceMember::firstOrCreate([
                'client_id' => $event->client,
                'audience_id' => $audience_record->id,
                'entity_id' => $event->user,
                'entity_type' => $event->entity,
                'subscribed' => true,
                'dnc' => false
            ]);

            // add a new record to entity's details
            $entity_model = new $event->entity;
            $details_class = $entity_model::getDetailsTable();
            $details_model = new $details_class();
            $details_model->firstOrCreate([
                'client_id' => $event->client,
                $details_model->fk => $event->user,
                'field' => 'audience_subscribed',
                'value' => $audience_record->id,
                'misc' => [
                    'date' => date('Y-m-d'),
                    'audience_member_record' => $audience_member_record->id
                ],
            ]);
        }

    }

    public function onLeadWasDeleted(LeadWasDeleted $event)
    {
        $lead = Lead::findOrFail($event->lead);
        $client_id = $lead->client_id;
        $success = $lead->deleteOrFail();

        $uare = $event->user;

        LeadDetails::create([
            'client_id' => $client_id,
            'lead_id' => $event->lead,
            'field' => 'softdelete',
            'value' => $uare,
            'misc' => ['userid' => $uare]
        ]);

    }

    public function onTrialMembershipAdded(TrialMembershipAdded $event)
    {
        $lead = Lead::findOrFail($event->lead);
        $trial = TrialMembershipType::find($event->trial);
        $client_id = $lead->client_id;
        TrialMembership::create([
            'client_id' => $client_id,
            'type_id' => $event->trial,
            'lead_id' => $event->lead,
            'start_date' => $event->date,
            'expiry_date' => Carbon::instance(new \DateTime($event->date))->addDays($trial->trial_length),
            'club_id' => $lead->gr_location_id,
            'active' => 1
        ]);
        LeadDetails::create([
            'client_id' => $event->client,
            'lead_id' => $event->lead,
            'field' => 'trial-started',
            'value' => $event->date,
            'misc' => ['trial_id' => $event->trial, 'date' => $event->date, 'client' => $event->client]
        ]);
    }

    public function onTrialMembershipUsed(TrialMembershipUsed $event)
    {
        $lead = Lead::findOrFail($event->lead);

        LeadDetails::create([
            'client_id' => $event->client,
            'lead_id' => $event->lead,
            'field' => 'trial-used',
            'value' => $event->trial,
            'misc' => ['trial_id' => $event->trial, 'date' => $event->date, 'client' => $event->client]
        ]);
    }

    public function onLeadUtmsProcessed(LeadUtmsProcessed $event)
    {

        $utm_template = UtmTemplates::whereClientId($event->client)
            ->whereUtmSource($event->payload['utm_source'])
            ->whereUtmCampaign($event->payload['utm_campaign'])
            ->whereUtmMedium($event->payload['utm_medium'])
            ->first();

        $utm_template_id = null;
        $utm_template_id = $utm_template?->id;

        Log::debug('$utm_template_id');
        Log::debug($utm_template_id);


        Utms::create([
            'client_id' => $event->client,
            'entity_name' => Lead::class,
            'entity_id' => $event->aggregateRootUuid(),
            'utm_template_id' => $utm_template_id,
            'source' => $event->payload['utm_source'],
            'campaign' => $event->payload['utm_campaign'],
            'medium' => $event->payload['utm_medium'],
            'capture_date' => $event->createdAt()
        ]);

    }
}
