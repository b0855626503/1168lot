<?php

namespace Gametech\LineOA\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use InfyOm\Generator\Utils\ResponseUtil;

/**
 * @SWG\Swagger(
 *   basePath="/api/v1",
 *   @SWG\Info(
 *     title="Laravel Generator APIs",
 *     version="1.0.0",
 *   )
 * )
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public static function numberDisplay($number = 0): string
    {
        return number_format($number, 2, '.', ',');
    }

    public static function betweenDate($datenow, $start, $stop)
    {

    }

    public function sendResponse($result, $message): JsonResponse
    {
        return Response::json(ResponseUtil::makeResponse($message, $result), 200);
    }

    public static function makeResponse($message, $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
    }

    public function sendResponseNew($result, $message, $code = 200): JsonResponse
    {
        $result['success'] = true;
        $result['message'] = $message;
        return Response::json($result, $code);
    }

    public function sendResponseFail($result, $message, $code = 200): JsonResponse
    {
        $result['success'] = false;
        $result['message'] = $message;
        return Response::json($result, $code);
    }

    public function sendError($error, $code = 404): JsonResponse
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    public function sendSuccess($message): JsonResponse
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ], 200);
    }
}
