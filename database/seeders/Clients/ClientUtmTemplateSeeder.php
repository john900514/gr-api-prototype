<?php

namespace Database\Seeders\Clients;

use App\Actions\Customers\UTM\CreateUtmTemplate;
use App\Aggregates\Clients\Reporting\Leads\ClientLeadReportingAggregate;
use App\Exceptions\Clients\ClientReportingException;
use App\Models\Clients\Client;
use App\Models\Clients\ClientDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\VarDumper\VarDumper;

class ClientUtmTemplateSeeder extends Seeder
{
    private $templates = [
        [
            'utm_source' => 'facebook',
            'utm_campaign' => 'facebook',
            'utm_medium' => 'test-promo1'
        ],
        [
            'utm_source' => 'instagram',
            'utm_campaign' => 'social2',
            'utm_medium' => 'test-promo2'
        ],
        [
            'utm_source' => 'snapchat',
            'utm_campaign' => 'snap-campaign',
            'utm_medium' => 'test-promo3'
        ],
        [
            'utm_source' => 'tiktok',
            'utm_campaign' => 'social4',
            'utm_medium' => 'test-promo4'
        ],
        [
            'utm_source' => 'pinterest',
            'utm_campaign' => 'social5',
            'utm_medium' => 'test-promo5'
        ],
//        [
//            'utm_source' => '',
//            'utm_campaign' => '',
//            'utm_medium' => ''
//        ]
    ];

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

        foreach ($all_clients as $client) {
            VarDumper::dump("Creating UTM Templates for $client->name");
            foreach ($this->templates as $template) {
                CreateUtmTemplate::run(array_merge($template, ['client_id' => $client->id]));
            }
        }
    }
}
