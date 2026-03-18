<?php

namespace Gametech\LogUser\Http\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Jaybizzle\LaravelCrawlerDetect\Facades\LaravelCrawlerDetect as Crawler;

trait ActivityLoggerUser
{
    /**
     * Laravel Logger Log Activity.
     *
     * @param null $description
     * @param null $details
     * @param null $user
     *
     * @return void
     */
    public static function activity($description = null, $details = null, $user = null)
    {
        $userType = trans('LaravelLoggerUser::laravel-logger.userTypes.guest');
        if($user){
            $userType = trans('LaravelLoggerUser::laravel-logger.userTypes.registered');
        }
        $userId = $user;

        if (Auth::guard('customer')->check()) {
            $userType = trans('LaravelLoggerUser::laravel-logger.userTypes.registered');
            $userIdField = config('LaravelLoggerUser.defaultUserIDField');
            $userId = Request::user('customer')->{$userIdField};
        }

        if (Crawler::isCrawler()) {
            $userType = trans('LaravelLoggerUser::laravel-logger.userTypes.crawler');
            if (is_null($description)) {
                $description = $userType.' '.trans('LaravelLoggerUser::laravel-logger.verbTypes.crawled').' '.Request::fullUrl();
            }
        }

        if (!$description) {
            switch (strtolower(Request::method())) {
                case 'post':
                    $verb = trans('LaravelLoggerUser::laravel-logger.verbTypes.created');
                    break;

                case 'patch':
                case 'put':
                    $verb = trans('LaravelLoggerUser::laravel-logger.verbTypes.edited');
                    break;

                case 'delete':
                    $verb = trans('LaravelLoggerUser::laravel-logger.verbTypes.deleted');
                    break;

                case 'get':
                default:
                    $verb = trans('LaravelLoggerUser::laravel-logger.verbTypes.viewed');
                    break;
            }

            $description = $verb.' '.Request::path();
        }

        $data = [
            'description'   => $description,
            'details'       => $details,
            'userType'      => $userType,
            'userId'        => $userId,
            'route'         => Request::fullUrl(),
            'ipAddress'     => Request::ip(),
            'userAgent'     => Request::header('user-agent'),
            'locale'        => Request::header('accept-language'),
            'referer'       => Request::header('referer'),
            'methodType'    => Request::method(),
        ];

        // Validation Instance
        $validator = Validator::make($data, config('LaravelLoggerUser.defaultActivityModel')::rules());
        if ($validator->fails()) {
            $errors = self::prepareErrorMessage($validator->errors(), $data);
            if (config('LaravelLoggerUser.logDBActivityLogFailuresToFile')) {
                Log::error('Failed to record activity event. Failed Validation: '.$errors);
            }
        } else {
            self::storeActivity($data);
        }
    }

    /**
     * Store activity entry to database.
     *
     * @param array $data
     *
     * @return void
     */
    private static function storeActivity(array $data)
    {
        config('LaravelLoggerUser.defaultActivityModel')::create([
            'description'   => $data['description'],
            'details'       => $data['details'],
            'userType'      => $data['userType'],
            'userId'        => $data['userId'],
            'route'         => $data['route'],
            'ipAddress'     => $data['ipAddress'],
            'userAgent'     => $data['userAgent'],
            'locale'        => $data['locale'],
            'referer'       => $data['referer'],
            'methodType'    => $data['methodType'],
        ]);
    }

    /**
     * Prepare Error Message (add the actual value of the error field).
     *
     * @param $validatorErrors
     * @param $data
     *
     * @return string
     */
    private static function prepareErrorMessage($validatorErrors, $data): string
    {
        $errors = json_decode(json_encode($validatorErrors, true));
        array_walk($errors, function (&$value, $key) use ($data) {
            array_push($value, "Value: $data[$key]");
        });

        return json_encode($errors, true);
    }
}
