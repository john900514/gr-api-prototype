<?php

namespace Database\Seeders\Users;

use App\Aggregates\Users\UserAggregate;
use App\Exceptions\Users\UserException;
use Bouncer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Symfony\Component\VarDumper\VarDumper;

class APITokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // This is where we will assign API access tokens to worthy users
        $users = User::all();
        if(count($users) > 0)
        {
            foreach($users as $user)
            {
                try {
                    VarDumper::dump($user->name.'- Verifying.');

                    UserAggregate::retrieve($user->id)
                        ->grantAccessToken()
                        ->persist();

                    VarDumper::dump($user->name.'- Access Granted.');
                }
                catch(UserException $e)
                {
                    VarDumper::dump('User Access Denied. Skipping.');
                }
            }
        }
        else
        {
            VarDumper::dump('No users. Cannot make tokens. Sorry!');
        }
    }
}
