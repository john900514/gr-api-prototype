<?php

namespace App\Aggregates\Users;

use Bouncer;
use App\Exceptions\Users\UserException;
use App\Models\Clients\Client;
use App\Models\User;
use App\StorableEvents\Users\Activity\API\AccessTokenGranted;
use App\StorableEvents\Users\Activity\Impersonation\UserImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedBeingImpersonated;
use App\StorableEvents\Users\Activity\Impersonation\UserStoppedImpersonatedAnother;
use App\StorableEvents\Users\Activity\Impersonation\UserWasImpersonated;
use App\StorableEvents\Users\Activity\Email\UserReceivedEmail;
use App\StorableEvents\Users\Activity\SMS\UserReceivedTextMsg;
use App\StorableEvents\Users\UserAddedToTeam;
use App\StorableEvents\Users\UserCreated;
use App\StorableEvents\Users\UserDeleted;
use App\StorableEvents\Users\UserUpdated;
use App\StorableEvents\Users\WelcomeEmailSent;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class UserAggregate extends AggregateRoot
{
    protected $client_id = '';
    protected $teams = [];
    protected array $activity_history = [];
    protected $phone_number = '';
    protected string $name = '';
    protected string $first_name = '';
    protected string $last_name = '';
    protected string $email = '';
    protected string $alt_email = '';
    protected string $address1 = '';
    protected string $address2 = '';
    protected string $city = '';
    protected string $state = '';
    protected string $zip = '';
    protected string $job_title = '';
    protected string $notes = '';
    protected string $start_date = '';
    protected string $end_date = '';
    protected string $termination_date = '';

    public function grantAccessToken()
    {
        $me = User::find($this->uuid());

        if(Bouncer::is($me)->an('Admin', 'Account Owner'))
        {
            $this->recordThat(new AccessTokenGranted($this->uuid()));
        }
        else
        {
            throw UserException::accessTokenPermissionDenied();
        }

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    public function getEmailAddress()
    {
        return $this->email;
    }

    public function getProperty(string $prop)
    {
        switch($prop)
        {
            case 'name':
                return $this->getName();
                break;

            default:
                return false;
        }
    }
}
