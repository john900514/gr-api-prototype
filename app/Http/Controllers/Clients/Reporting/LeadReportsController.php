<?php

namespace App\Http\Controllers\Clients\Reporting;

use App\Actions\Clients\Reporting\Leads\GetTotalDailyLeadsReport;
use App\Actions\Clients\Reporting\Leads\GetTotalDailyLeadsReportByLocation;
use App\Actions\Clients\Reporting\Leads\GetTotalDailyOrganicLeadsReport;
use App\Actions\Clients\Reporting\Leads\GetTotalDailyUTMLeadsReport;
use App\Actions\Clients\Reporting\Leads\GetTotalUniqueLeadsReport;
use App\Actions\Clients\Reporting\Leads\GetTotalUniqueLeadsReportByLocation;
use App\Http\Controllers\Controller;
use App\Models\Clients\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeadReportsController extends Controller
{
    public function __construct()
    {

    }

    public function total_unique_leads() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            $detailed = request()->get('detailed') == 'true' ?? false;
            $report = GetTotalUniqueLeadsReport::run($user, $client, $detailed);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }
    public function total_unique_leads_by_location() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            $detailed = request()->get('detailed') == 'true' ?? false;
            $report = GetTotalUniqueLeadsReportByLocation::run($user, $client, $detailed);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }

    public function total_daily_leads() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            // If no dates are passed in, use today's date
            $start_date = request()->get('start_date') ?? date('Y-m-d');
            $end_date = request()->get('end_date') ?? request()->get('start_date') ?? date('Y-m-d');
            // validate that the $end_date is not a date that comes before the $start_date or pass a 500

            if(($start_date != $end_date) && (strtotime($end_date) < strtotime($start_date)))
            {
                $results['error'] = 'Invalid End Time';
                return response($results, 500);
            }

            $detailed = request()->get('detailed') == 'true' ?? false;
            $report = GetTotalDailyLeadsReport::run($user, $client, $start_date, $end_date, $detailed);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }
    public function total_daily_leads_by_location() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            // If no dates are passed in, use today's date
            $start_date = request()->get('start_date') ?? date('Y-m-d');
            $end_date = request()->get('end_date') ?? request()->get('start_date') ?? date('Y-m-d');
            // validate that the $end_date is not a date that comes before the $start_date or pass a 500

            if(($start_date != $end_date) && (strtotime($end_date) < strtotime($start_date)))
            {
                $results['error'] = 'Invalid End Time';
                return response($results, 500);
            }

            $detailed = request()->get('detailed') == 'true' ?? false;
            $report = GetTotalDailyLeadsReportByLocation::run($user, $client, $start_date, $end_date, $detailed);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }

    public function total_organic_leads() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            // If no dates are passed in, use today's date
            $start_date = request()->get('start_date') ?? date('Y-m-d');
            $end_date = request()->get('end_date') ?? request()->get('start_date') ?? date('Y-m-d');
            // validate that the $end_date is not a date that comes before the $start_date or pass a 500

            if(($start_date != $end_date) && (strtotime($end_date) < strtotime($start_date)))
            {
                $results['error'] = 'Invalid End Time';
                return response($results, 500);
            }

            $detailed = request()->get('detailed') == 'true' ?? false;
            $report = GetTotalDailyOrganicLeadsReport::run($user, $client, $start_date, $end_date, $detailed);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }

    public function total_utm_leads() : Response
    {
        $results = [];
        $code = 204;

        $user = request()->user();
        $client_detail = $user->associated_client()->first();
        if(!is_null($client_detail))
        {
            $client = Client::find($client_detail->value);
        }

        if((is_null($client_detail)) && (!request()->has('account')))
        {
            $results['error'] = 'Missing Account';
            return response($results, 500);
        }

        if((is_null($client_detail)) && request()->has('account'))
        {
            $client = Client::find(request()->get('account'));
        }

        if(!is_null($client))
        {
            // If no dates are passed in, use today's date
            $start_date = request()->get('start_date') ?? date('Y-m-d');
            $end_date = request()->get('end_date') ?? request()->get('start_date') ?? date('Y-m-d');
            // validate that the $end_date is not a date that comes before the $start_date or pass a 500

            if(($start_date != $end_date) && (strtotime($end_date) < strtotime($start_date)))
            {
                $results['error'] = 'Invalid End Time';
                return response($results, 500);
            }

            $report = GetTotalDailyUTMLeadsReport::run($user, $client, $start_date, $end_date);
        }
        else
        {
            $results['error'] = 'Invalid Account';
            return response($results, 500);
        }

        if($report)
        {
            $code = 200;
            $results = $report;
        }

        return response($results, $code);
    }
}
