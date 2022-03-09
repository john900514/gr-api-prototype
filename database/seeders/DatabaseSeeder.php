<?php

namespace Database\Seeders;

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
        // This is where we will assign API access tokens to worthy users
        VarDumper::dump('Setting the initial app state');
        $this->call(APITokenSeeder::class);
    }
}
