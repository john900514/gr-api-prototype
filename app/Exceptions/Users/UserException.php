<?php

namespace App\Exceptions\Users;

use DomainException;

class UserException extends DomainException
{
    public static function accessTokenPermissionDenied()
    {
        return new self("This user does not have permission to be granted an access token");
    }
}
