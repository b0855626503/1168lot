<?php

namespace Gametech\LogUser\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Gametech\LogUser\Http\Traits\IpAddressDetails;
use Gametech\LogUser\Http\Traits\UserAgentDetails;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

class LaravelLoggerController extends Controller
{

    use IpAddressDetails;
    use UserAgentDetails;

    private $_rolesEnabled;
    private $_rolesMiddlware;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('customer');

        $this->_rolesEnabled = config('LaravelLoggerUser.rolesEnabled');
        $this->_rolesMiddlware = config('LaravelLoggerUser.rolesMiddlware');

        if ($this->_rolesEnabled) {
            $this->middleware($this->_rolesMiddlware);
        }
    }

    /**
     * Add additional details to a collections.
     *
     * @param collection $collectionItems
     *
     * @return collection
     */
    private function mapAdditionalDetails($collectionItems)
    {
        $collectionItems->map(function ($collectionItem) {
            $eventTime = Carbon::parse($collectionItem->updated_at);
            $collectionItem['timePassed'] = $eventTime->diffForHumans();
            $collectionItem['userAgentDetails'] = UserAgentDetails::details($collectionItem->userAgent);
            $collectionItem['langDetails'] = UserAgentDetails::localeLang($collectionItem->locale);
            $collectionItem['userDetails'] = config('LaravelLoggerUser.defaultUserModel')::find($collectionItem->userId);

            return $collectionItem;
        });

        return $collectionItems;
    }

    /**
     * Show the activities log dashboard.
     *
     * @return Response
     */
    public function showAccessLog(Request $request)
    {
        if (config('LaravelLoggerUser.loggerPaginationEnabled')) {
            $activities = config('LaravelLoggerUser.defaultActivityModel')::orderBy('created_at', 'desc');
            if (config('LaravelLoggerUser.enableSearch')) {
                $activities = $this->searchActivityLog($activities, $request);
            }
            $activities = $activities->paginate(config('LaravelLoggerUser.loggerPaginationPerPage'));
            $totalActivities = $activities->total();
        } else {
            $activities = config('LaravelLoggerUser.defaultActivityModel')::orderBy('created_at', 'desc');

            if (config('LaravelLoggerUser.enableSearch')) {
                $activities = $this->searchActivityLog($activities, $request);
            }
            $activities = $activities->get();
            $totalActivities = $activities->count();
        }

        self::mapAdditionalDetails($activities);

        $users = config('LaravelLoggerUser.defaultUserModel')::all();

        $data = [
            'activities'        => $activities,
            'totalActivities'   => $totalActivities,
            'users'             => $users,
        ];

        return View('LaravelLoggerUser::logger.activity-log', $data);
    }

    /**
     * Show an individual activity log entry.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function showAccessLogEntry(Request $request, $id)
    {
        $activity = config('LaravelLoggerUser.defaultActivityModel')::findOrFail($id);

        $userDetails = config('LaravelLoggerUser.defaultUserModel')::find($activity->userId);
        $userAgentDetails = UserAgentDetails::details($activity->userAgent);
        $ipAddressDetails = IpAddressDetails::checkIP($activity->ipAddress);
        $langDetails = UserAgentDetails::localeLang($activity->locale);
        $eventTime = Carbon::parse($activity->created_at);
        $timePassed = $eventTime->diffForHumans();

        if (config('LaravelLoggerUser.loggerPaginationEnabled')) {
            $userActivities = config('LaravelLoggerUser.defaultActivityModel')::where('userId', $activity->userId)
            ->orderBy('created_at', 'desc')
            ->paginate(config('LaravelLoggerUser.loggerPaginationPerPage'));
            $totalUserActivities = $userActivities->total();
        } else {
            $userActivities = config('LaravelLoggerUser.defaultActivityModel')::where('userId', $activity->userId)
            ->orderBy('created_at', 'desc')
            ->get();
            $totalUserActivities = $userActivities->count();
        }

        self::mapAdditionalDetails($userActivities);

        $data = [
            'activity'              => $activity,
            'userDetails'           => $userDetails,
            'ipAddressDetails'      => $ipAddressDetails,
            'timePassed'            => $timePassed,
            'userAgentDetails'      => $userAgentDetails,
            'langDetails'           => $langDetails,
            'userActivities'        => $userActivities,
            'totalUserActivities'   => $totalUserActivities,
            'isClearedEntry'        => false,
        ];

        return View('LaravelLoggerUser::logger.activity-log-item', $data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function clearActivityLog(Request $request)
    {
        $activities = config('LaravelLoggerUser.defaultActivityModel')::all();
        foreach ($activities as $activity) {
            $activity->delete();
        }

        return redirect('log-user')->with('success', trans('LaravelLoggerUser::laravel-logger.messages.logClearedSuccessfuly'));
    }

    /**
     * Show the cleared activity log - softdeleted records.
     *
     * @return Response
     */
    public function showClearedActivityLog()
    {
        if (config('LaravelLoggerUser.loggerPaginationEnabled')) {
            $activities = config('LaravelLoggerUser.defaultActivityModel')::onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->paginate(config('LaravelLoggerUser.loggerPaginationPerPage'));
            $totalActivities = $activities->total();
        } else {
            $activities = config('LaravelLoggerUser.defaultActivityModel')::onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->get();
            $totalActivities = $activities->count();
        }

        self::mapAdditionalDetails($activities);

        $data = [
            'activities'        => $activities,
            'totalActivities'   => $totalActivities,
        ];

        return View('LaravelLoggerUser::logger.activity-log-cleared', $data);
    }

    /**
     * Show an individual cleared (soft deleted) activity log entry.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function showClearedAccessLogEntry(Request $request, $id)
    {
        $activity = self::getClearedActvity($id);

        $userDetails = config('LaravelLoggerUser.defaultUserModel')::find($activity->userId);
        $userAgentDetails = UserAgentDetails::details($activity->userAgent);
        $ipAddressDetails = IpAddressDetails::checkIP($activity->ipAddress);
        $langDetails = UserAgentDetails::localeLang($activity->locale);
        $eventTime = Carbon::parse($activity->created_at);
        $timePassed = $eventTime->diffForHumans();

        $data = [
            'activity'              => $activity,
            'userDetails'           => $userDetails,
            'ipAddressDetails'      => $ipAddressDetails,
            'timePassed'            => $timePassed,
            'userAgentDetails'      => $userAgentDetails,
            'langDetails'           => $langDetails,
            'isClearedEntry'        => true,
        ];

        return View('LaravelLoggerUser::logger.activity-log-item', $data);
    }

    /**
     * Get Cleared (Soft Deleted) Activity - Helper Method.
     *
     * @param int $id
     *
     * @return Response
     */
    private static function getClearedActvity($id)
    {
        $activity = config('LaravelLoggerUser.defaultActivityModel')::onlyTrashed()->where('id', $id)->get();
        if (count($activity) != 1) {
            return abort(404);
        }

        return $activity[0];
    }

    /**
     * Destroy the specified resource from storage.
     *
     * @param Request $request
     *
     * @return Application|RedirectResponse|Response|Redirector
     */
    public function destroyActivityLog(Request $request)
    {
        $activities = config('LaravelLoggerUser.defaultActivityModel')::onlyTrashed()->get();
        foreach ($activities as $activity) {
            $activity->forceDelete();
        }

        return redirect('log-user')->with('success', trans('LaravelLoggerUser::laravel-logger.messages.logDestroyedSuccessfuly'));
    }

    /**
     * Restore the specified resource from soft deleted storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function restoreClearedActivityLog(Request $request)
    {
        $activities = config('LaravelLoggerUser.defaultActivityModel')::onlyTrashed()->get();
        foreach ($activities as $activity) {
            $activity->restore();
        }

        return redirect('log-user')->with('success', trans('LaravelLoggerUser::laravel-logger.messages.logRestoredSuccessfuly'));
    }

    /**
     * Search the activity log according to specific criteria.
     *
     * @param query
     * @param request
     *
     * @return filtered query
     */
    public function searchActivityLog($query, $requeset)
    {
        if (in_array('description', explode(',', config('LaravelLoggerUser.searchFields'))) && $requeset->get('description')) {
            $query->where('description', 'like', '%'.$requeset->get('description').'%');
        }

        if (in_array('user', explode(',', config('LaravelLoggerUser.searchFields'))) && $requeset->get('user')) {
            $query->where('userId', '=', $requeset->get('user'));
        }

        if (in_array('method', explode(',', config('LaravelLoggerUser.searchFields'))) && $requeset->get('method')) {
            $query->where('methodType', '=', $requeset->get('method'));
        }

        if (in_array('route', explode(',', config('LaravelLoggerUser.searchFields'))) && $requeset->get('route')) {
            $query->where('route', 'like', '%'.$requeset->get('route').'%');
        }

        if (in_array('ip', explode(',', config('LaravelLoggerUser.searchFields'))) && $requeset->get('ip_address')) {
            $query->where('ipAddress', 'like', '%'.$requeset->get('ip_address').'%');
        }

        return $query;
    }
}
