<?php

namespace Gametech\Core\Exceptions;

use App\Exceptions\Handler as AppExceptionHandler;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends AppExceptionHandler
{
    protected $jsonErrorMessages = [
        400 => 'Connection Problem',
        404 => 'Resource not found',
        403 => '403 forbidden Error',
        401 => 'Unauthenticated',
        500 => '500 Internal Server Error',
        408 => 'Request Timeout',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Throwable $exception
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        $path = $this->isAdminUri() ? 'admin' : 'wallet';


        if ($exception instanceof HttpException) {
            $statusCode = in_array($exception->getStatusCode(), [400 , 401, 403, 404, 503]) ? $exception->getStatusCode() : 500;

            return $this->response($path, $statusCode);
        } elseif ($exception instanceof ModelNotFoundException) {
            return $this->response($path, 404);
        } elseif ($exception instanceof PDOException) {
            return $this->response($path, 500);
        } elseif ($exception instanceof ConnectionException) {
            return $this->response($path, 408);
        } elseif ($exception instanceof RequestException) {
            return $this->response($path, 400);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param AuthenticationException $exception
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {


        if ($request->expectsJson()) {
            return response()->json(['error' => $this->jsonErrorMessages[401]], 401);
        }

        if($this->isAdminUri()){
            return redirect()->guest(route('admin.session.index'));
        }else{
            return redirect()->guest(route('customer.session.index'));
        }

    }

    private function isAdminUri()
    {
//        $admin = Request::routeIs('admin.*');
        return Request::routeIs('admin.*');
//        $return  = Request::routeIs('admin.*');
//        dd($return);
//        return strpos(Request::path(), 'admin') !== false ? true : false;
    }

    private function response($path, $statusCode)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => isset($this->jsonErrorMessages[$statusCode])
                    ? $this->jsonErrorMessages[$statusCode]
                    : 'Something went wrong, please try again later.'
            ], $statusCode);

//            return response()->json([
//                'msg' => $this->jsonErrorMessages[$statusCode] ?? 'Something went wrong, please try again later.',
//                'success' => false
//            ], 200);
        }

        return response()->view("{$path}::errors.{$statusCode}", [], $statusCode);
    }
}
