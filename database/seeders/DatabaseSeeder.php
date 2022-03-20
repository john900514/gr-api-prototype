<?php

namespace Database\Seeders;

use Database\Seeders\Clients\ClientUtmTemplateSeeder;
use Database\Seeders\Users\APITokenSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Symfony\Component\VarDumper\VarDumper;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        VarDumper::dump('Seeding the DB with API-level dataz');
        $this->call(APITokenSeeder::class);
        $this->call(ClientUtmTemplateSeeder::class);
    }
}
