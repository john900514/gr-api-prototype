<?php

namespace App\Exceptions\Clients;

use DomainException;

class ClientReportingException extends DomainException
{
    public static function missingClientID()
    {
        return new self("Aggregate must be initialized with a Client UUID before adding data to it.");
    }

    public static function cannotChangeClientOnReporting()
    {
        return new self("You cannot change a client ID associated with a line of reporting.");
    }

    public static function cannotAddUniqueLead(string $email)
    {
        return new self("Lead {$email} already exists in the unique list.");
    }

    public static function invalidLead(string $email)
    {
        return new self("Lead {$email} does not exist.");
    }

    public static function dailyLeadAlreadyAdded(string $email)
    {
        return new self("Lead {$email} already exists in the daily list for the current day.");
    }

    public static function dailyOrganicLeadAlreadyAdded(string $email)
    {
        return new self("Lead {$email} already exists in the organic list for the current day.");
    }

    public static function dailyUTMLeadAlreadyAdded(string $email)
    {
        return new self("Lead {$email} already exists in the UTMs list for the current day.");
    }
}
