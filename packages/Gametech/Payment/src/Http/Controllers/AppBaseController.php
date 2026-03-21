<?php

namespace Gametech\Payment\Http\Controllers;

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

    /**
     * อ่าน config ครั้งเดียวต่อ request เพื่อหลีกเลี่ยง query ซ้ำใน callback/controllers ที่ถูกเรียกบ่อย
     */
    protected function getCoreConfig()
    {
        if (app()->bound('request')) {
            $request = app('request');
            $cacheKey = '_payment_controller.core_config';

            if ($request->attributes->has($cacheKey)) {
                return $request->attributes->get($cacheKey);
            }

            $config = core()->getConfigData();
            $request->attributes->set($cacheKey, $config);

            return $config;
        }

        return core()->getConfigData();
    }
}
