<?php

namespace App\Projectors\Users;

use App\Models\User;
use App\Models\UserDetails;
use App\StorableEvents\Users\Activity\API\AccessTokenGranted;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class UserAPIProjector extends Projector
{
    public function onAccessTokenGranted(AccessTokenGranted $event)
    {
        $user = User::find($event->user);
        $user->tokens()->delete();
        $token = $user->createToken($user->email)->plainTextToken;
        $deet = UserDetails::firstOrCreate([
            'user_id' => $user->id,
            'name' => 'api-token',
            'active' => 1
        ]);

        $deet->value = base64_encode($token);
        $deet->save();
    }
}
