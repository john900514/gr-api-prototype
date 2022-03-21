<?php

namespace App\Actions\Customers\UTM;

use App\Aggregates\Clients\CalendarAggregate;
use App\Aggregates\ClientUTMs\ClientUTMAggregate;
use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Models\UtmTemplates;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Prologue\Alerts\Facades\Alert;
use Ramsey\Uuid\Uuid;


class CreateUtmTemplate
{
    use AsAction;

    /**
     * Get the validation rules that apply to the action.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'utm_source' =>['required', 'string'],
            'utm_medium' => ['required', 'string'],
            'utm_campaign' => ['required', 'string'],
            'utm_term' => ['nullable', 'string'],
            'utm_content' => ['nullable', 'string'],
        ];
    }

    public function handle($data, $user = null)
    {
        if(!array_key_exists('id', $data)){
            $id = Uuid::uuid4()->toString();
            $data['id'] = $id;
        }

        ClientUTMAggregate::retrieve($data['client_id'])
            ->createUtmTemplate($data, $user)
            ->persist();

        return UtmTemplates::findOrFail($id);
    }

    public function asController(ActionRequest $request)
    {

        return $this->handle(
            $request->validated(),
            $request->user()
        );
    }

}
