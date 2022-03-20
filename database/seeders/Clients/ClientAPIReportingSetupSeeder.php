<?php

namespace Database\Seeders\Clients;

use App\Aggregates\Clients\Reporting\Leads\ClientLeadReportingAggregate;
use App\Exceptions\Clients\ClientReportingException;
use App\Models\Clients\Client;
use App\Models\Clients\ClientDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\VarDumper\VarDumper;

class ClientAPIReportingSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all clients
        $all_clients = Client::with('lead_reports_uuid')
            // @todo - add other future required relations here
            ->get();

        foreach($all_clients as $client)
        {
            VarDumper::dump($client->name);

            // Set clients with a firstOrCreate on report-uuid
            $lead_reports_uuid = Uuid::uuid4()->toString();
            $lead_reports_uuid_record = ClientDetail::firstOrCreate([
                'client_id' => $client->id,
                'detail' => 'lead-reports-uuid'
            ]);

            if(is_null($lead_reports_uuid_record->value))
            {
                $lead_reports_uuid_record->value = $lead_reports_uuid;
                $lead_reports_uuid_record->active = 1;
                $lead_reports_uuid_record->save();
                try {
                    ClientLeadReportingAggregate::retrieve($lead_reports_uuid)
                        ->setClientId($client->id)
                        ->persist();
                }
                catch(ClientReportingException $e)
                {
                    VarDumper::dump("Ooops - {$e->getMessage()} - Skipping..");
                }

            }
            else
            {
                VarDumper::dump('Lead Reporting added Previously');
            }
        }
    }
}
