<?php

namespace App\Actions\Customers\UTM;

use App\Aggregates\Clients\UTMs\ClientUTMAggregate;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
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

        //return UtmTemplates::findOrFail($id);
        return $data['id'];
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(
            $request->validated(),
            $request->user()
        );
    }

    public function jsonResponse($result)
    {

    }

}
