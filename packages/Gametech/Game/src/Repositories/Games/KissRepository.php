<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;
use Throwable;

class KissRepository extends Repository
{
    protected $responses;

    protected $method;

    protected $debug;

    protected $url;

    protected $agent;

    protected $agentPass;

    protected $passkey;

    protected $secretkey;

    protected $login;

    protected $auth;

    public function __construct($method, $debug, App $app)
    {
        $game = 'kiss';

        $this->method = $method;
        $this->debug = $debug;

        $this->url = config($this->method.'.'.$game.'.apiurl');
        $this->agent = config($this->method.'.'.$game.'.agent');
        $this->agentPass = config($this->method.'.'.$game.'.agent_pass');
        $this->login = config($this->method.'.'.$game.'.login');
        $this->auth = config($this->method.'.'.$game.'.auth');
        $this->passkey = config($this->method.'.'.$game.'.passkey');
        $this->secretkey = config($this->method.'.'.$game.'.secretkey');

        $this->responses = [];

        parent::__construct($app);
    }

    /**
     * Normalize return schema to avoid undefined keys and non-json responses.
     */
    protected function normalizeResult($result, bool $main, string $fallbackMsg = ''): array
    {
        if (! is_array($result)) {
            $result = [];
        }

        // keep original keys if exist, but guarantee stable schema
        $result['main'] = $main;

        if (! array_key_exists('success', $result)) {
            // Some endpoints may not return success; keep false to be safe
            $result['success'] = false;
        }

        if (! array_key_exists('msg', $result) || $result['msg'] === null) {
            $result['msg'] = $fallbackMsg !== '' ? $fallbackMsg : ($main ? 'Complete' : 'เกิดข้อผิดพลาดจากระบบเกม');
        }

        return $result;
    }

    public function Debug($response, $custom = false)
    {
        $return = [];

        if (! $custom) {
            // Guard: $response may be null or not an HTTP Response object
            if (is_object($response) && method_exists($response, 'body')) {
                $return['body'] = $response->body();
                $return['json'] = method_exists($response, 'json') ? $response->json() : null;
                $return['successful'] = method_exists($response, 'successful') ? $response->successful() : null;
                $return['failed'] = method_exists($response, 'failed') ? $response->failed() : null;
                $return['clientError'] = method_exists($response, 'clientError') ? $response->clientError() : null;
                $return['serverError'] = method_exists($response, 'serverError') ? $response->serverError() : null;
            } else {
                $return['body'] = is_scalar($response) ? (string) $response : json_encode($response);
                $return['json'] = null;
                $return['successful'] = null;
                $return['failed'] = null;
                $return['clientError'] = null;
                $return['serverError'] = null;
            }
        } else {
            $return['body'] = json_encode($response);
            $return['json'] = $response;
            $return['successful'] = 1;
            $return['failed'] = 1;
            $return['clientError'] = 1;
            $return['serverError'] = 1;
        }

        $this->responses[] = $return;
    }

    public function GameCurl($param, $action)
    {
        $url = $this->url.$action;

        $response = null;
        $throwable = null;

        try {
            $response = Http::timeout(30)->asForm()->post($url, $param);
        } catch (Throwable $e) {
            $throwable = $e;
            // Some exceptions may contain response; otherwise it's null.
            $response = method_exists($e, 'response') ? $e->response : null;
        }

        if ($this->debug) {
            // Log both response and exception if exist
            if ($response !== null) {
                $this->Debug($response);
            } else {
                $this->Debug([
                    'exception' => $throwable ? get_class($throwable) : null,
                    'message' => $throwable ? $throwable->getMessage() : 'Unknown error',
                    'url' => $url,
                    'param' => $param,
                ], true);
            }
        }

        // If no response object at all -> connection/timeout hard failure
        if ($response === null) {
            return $this->normalizeResult([
                'success' => false,
                'msg' => 'เชื่อมต่อไม่ได้',
                'raw_body' => $throwable ? $throwable->getMessage() : '',
            ], false, 'เชื่อมต่อไม่ได้');
        }

        // Try json first; may return null if not json
        $json = null;
        $rawBody = '';
        try {
            $rawBody = method_exists($response, 'body') ? (string) $response->body() : '';
            $json = method_exists($response, 'json') ? $response->json() : null;
        } catch (Throwable $e) {
            $json = null;
        }

        // When response is not json, keep empty array but preserve raw body for debug
        $result = is_array($json) ? $json : [];

        $main = (is_object($response) && method_exists($response, 'successful'))
            ? (bool) $response->successful()
            : false;

        // Provide fallback msg from raw body if server returns text/html
        $fallbackMsg = '';
        if (! empty($rawBody)) {
            // keep short message; do not explode logs
            $fallbackMsg = mb_substr(trim($rawBody), 0, 500);
        }

        $result['raw_body'] = $rawBody;

        return $this->normalizeResult($result, $main, $fallbackMsg);
    }

    public function addGameAccount($data): array
    {
        $result = $this->newUser();

        if (($result['success'] ?? false) === true) {
            $account = $result['account'] ?? '';
            $result = $this->addUser($account, $data);
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $result;
    }

    public function newUser(): array
    {
        $return = [
            'success' => false,
        ];

        $time = round(microtime(true) * 1000);
        $sign = strtoupper(md5(strtolower($this->login.$this->auth.$this->agent.$time.$this->secretkey)));
        $param = [
            'action' => 'RandomAccount',
            'userName' => $this->agent,
            'loginUser' => $this->login,
            'UserAreaId' => '2',
            'authcode' => $this->auth,
            'time' => $time,
            'sign' => $sign,
        ];

        $response = $this->GameCurl($param, 'ashx/account/account.ashx');

        if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
            $return['success'] = true;
            $return['account'] = $response['account'] ?? '';
        } else {
            $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
            $return['success'] = false;
        }

        return $return;
    }

    public function addUser($username, $data): array
    {
        $return = [
            'success' => false,
        ];

        $user_pass = 'Aa'.rand(100000, 999999);
        $time = round(microtime(true) * 1000);
        $sign = strtoupper(md5(strtolower($this->auth.$username.$time.$this->secretkey)));
        $param = [
            'action' => 'AddUser',
            'UserType' => 1,
            'PassWd' => $user_pass,
            'pwdtype' => 1,
            'userName' => $username,
            'Name' => $data['name'],
            'UserAreaId' => '2',
            'Tel' => 'N/A',
            'Memo' => 'N/A',
            'agent' => $this->agent,
            'authcode' => $this->auth,
            'time' => $time,
            'sign' => $sign,
        ];

        $response = $this->GameCurl($param, 'ashx/account/account.ashx');

        if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $user_pass;
        } else {
            $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
            $return['success'] = false;
        }

        return $return;
    }

    public function changePass($data): array
    {
        $return = [
            'success' => false,
        ];

        $time = round(microtime(true) * 1000);
        $sign = strtoupper(md5(strtolower($this->auth.$data['user_name'].$time.$this->secretkey)));

        $param = [
            'action' => 'editUser2',
            'UserType' => 1,
            'PassWd' => $data['user_pass'],
            'pwdtype' => 1,
            'userName' => $data['user_name'],
            'Name' => $data['name'],
            'Flag' => 1,
            'Tel' => 'N/A',
            'Memo' => 'N/A',
            'agent' => $this->agent,
            'authcode' => $this->auth,
            'time' => $time,
            'sign' => $sign,
        ];

        $response = $this->GameCurl($param, 'ashx/account/account.ashx');

        if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
            $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
            $return['success'] = true;
        } else {
            $return['success'] = false;
            $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function viewBalance($username): array
    {
        $return = [
            'success' => false,
            'score' => 0,
        ];

        $time = round(microtime(true) * 1000);
        $sign = strtoupper(md5(strtolower($this->auth.$username.$time.$this->secretkey)));

        $param = [
            'action' => 'getUserInfo',
            'userName' => $username,
            'authcode' => $this->auth,
            'time' => $time,
            'sign' => $sign,
        ];

        $response = $this->GameCurl($param, 'ashx/account/account.ashx');

        if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $score = (float) ($response['ScoreNum'] ?? 0) * 10;
            $return['score'] = $score;
        } else {
            $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
            $return['success'] = false;
            $return['connect'] = true;
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function deposit($username, $amount): array
    {
        $return = [
            'success' => false,
        ];

        $ip = request()->ip();
        $score = $amount / 10;

        if ($score < 1) {
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $time = round(microtime(true) * 1000);
            $sign = strtoupper(md5(strtolower($this->auth.$username.$time.$this->secretkey)));

            $param = [
                'action' => 'setServerScore',
                'userName' => $username,
                'scoreNum' => $score,
                'ActionUser' => $username,
                'ActionIp' => $ip,
                'authcode' => $this->auth,
                'time' => $time,
                'sign' => $sign,
            ];

            $response = $this->GameCurl($param, 'ashx/account/setScore.ashx');

            if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
                $return['success'] = true;
                $return['ref_id'] = $response['acc'] ?? null;

                $after = (float) ($response['money'] ?? 0) * 10;
                $return['after'] = $after;
                $return['before'] = ($after - $amount);
            } else {
                $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
                $return['success'] = false;
            }
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function withdraw($username, $amount): array
    {
        $return = [
            'success' => false,
        ];

        $ip = request()->ip();
        $score = $amount / 10;

        if ($score < 1) {
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $score = $score * -1;
            $time = round(microtime(true) * 1000);
            $sign = strtoupper(md5(strtolower($this->auth.$username.$time.$this->secretkey)));

            $param = [
                'action' => 'setServerScore',
                'userName' => $username,
                'scoreNum' => $score,
                'ActionUser' => $username,
                'ActionIp' => $ip,
                'authcode' => $this->auth,
                'time' => $time,
                'sign' => $sign,
            ];

            $response = $this->GameCurl($param, 'ashx/account/setScore.ashx');

            if (($response['main'] ?? false) === true && ($response['success'] ?? false) === true) {
                $return['success'] = true;
                $return['ref_id'] = $response['acc'] ?? null;

                $after = (float) ($response['money'] ?? 0) * 10;
                $return['after'] = $after;
                $return['before'] = ($after + $amount);
            } else {
                $return['msg'] = (string) ($response['msg'] ?? 'เกิดข้อผิดพลาดจากระบบเกม');
                $return['success'] = false;
            }
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model(): string
    {
        return \Gametech\Game\Models\User::class;
    }
}
