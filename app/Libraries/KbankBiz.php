<?php

namespace App\Libraries;

use Carbon\Carbon;

class KbankBiz
{
    private $username = null;
    private $password = null;
    private $accnum = null;
    private $ch = null;

    public $balance = null;
    public $showbalance = null;

    public $cookiefilename = ".kbizcookie";
    public $parafilename = '.kbizpara';
    public $ownidfilename = '.kbizownid';
    public $datarssofilename = '.kbizdatarsso';
    public $companyfilename = '.kbizcompany';

    private $X_SESSION_TOKEN = '';
    private $OWNERID = ''; // ibId (user)
    private $COMPANYID = ''; // companyId (ใช้เป็น ownerId จริง)
    private $PATH = '';

    // === logging options ===
    private bool $maskSensitive = true; // เปิด mask token/user/pass ใน log
    private int $logBodyPreviewLen = 1500;
    private ?string $logFile = null;
    private array $lastRequestHeaders = [];

    // === UA เดียวทั้งระบบ ===
    private string $UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

    // === proxy options ===
    private bool $useProxy = false;
    private string $proxyHost = '';
    private int $proxyPort = 0;
    private ?string $proxyUser = null;
    private ?string $proxyPass = null;

    // multi-port
    private array $proxyPorts = [];           // เช่น [8001,8002,8003] หรือ [60000,...]
    private string $proxyStrategy = 'random'; // 'random' | 'round_robin'
    private int $proxyIndex = 0;              // ใช้กับ round-robin

    private bool $logEnabled = true;

    private bool $randSleepMs = true;

    private int $rowPerPage = 30;

    private bool $outBoundIP = true;

    public function __construct()
    {
        // seed index ให้กระจายเวลาเริ่ม (กันทุก worker เริ่มที่ 0 เหมือนกัน)
        $this->proxyIndex = mt_rand(0, 1000);
    }

    /** เลือกพอร์ตตาม strategy */
    private function pickProxyPort(): int
    {
        if (!empty($this->proxyPorts)) {
            if ($this->proxyStrategy === 'round_robin') {
                $port = $this->proxyPorts[$this->proxyIndex % count($this->proxyPorts)];
                $this->proxyIndex++;
                return (int)$port;
            }
            return (int)$this->proxyPorts[array_rand($this->proxyPorts)];
        }
        return (int)$this->proxyPort;
    }

    /** setter สำหรับ strategy */
    public function setProxyStrategy(string $strategy = 'random'): void
    {
        $strategy = strtolower($strategy);
        $this->proxyStrategy = in_array($strategy, ['random', 'round_robin'], true) ? $strategy : 'random';
    }

    /** ปิด proxy */
    public function disableProxy(): void
    {
        $this->useProxy = false;
    }

    /** เปิดใช้งาน Web Unblocker/DC proxy (รับได้ทั้งพอร์ตเดี่ยวหรือหลายพอร์ต) */
    public function useWebUnblocker(string $user, string $pass, string $host = 'unblock.oxylabs.io', $port = 60000): void
    {
        $this->useProxy = true;
        $this->proxyHost = $host;
        $this->proxyUser = $user;
        $this->proxyPass = $pass;

        if (is_array($port)) {
            $this->proxyPorts = array_values(array_map('intval', $port));
            $this->proxyPort = $this->proxyPorts[0] ?? 60000;
        } else {
            $this->proxyPort = (int)$port;
            $this->proxyPorts = [];
        }
    }

    /** โหลดค่า proxy จาก config('proxy.web_unblocker') */
    private function applyProxyFromConfig(): void
    {
        $cfg = config('proxy.web_unblocker');

        $this->logEnabled = (bool)($cfg['log_enabled'] ?? true);
        $this->randSleepMs = (bool)($cfg['sleep_enabled'] ?? true);
        $this->outBoundIP = (bool)($cfg['outbound_enabled'] ?? true);
        $this->rowPerPage = (int)($cfg['row_per_page'] ?? $this->rowPerPage);

        if (!$cfg || empty($cfg['enabled'])) {
            $this->useProxy = false;
            return;
        }

        $user = $cfg['user'] ?? null;
        $pass = $cfg['pass'] ?? null;
        $host = $cfg['host'] ?? 'unblock.oxylabs.io';

        // รองรับทั้ง array และ string CSV
        $ports = [];
        if (!empty($cfg['ports'])) {
            if (is_array($cfg['ports'])) {
                $ports = array_values(array_map('intval', $cfg['ports']));
            } elseif (is_string($cfg['ports'])) {
                $ports = array_values(array_map('intval', preg_split('/\s*,\s*/', $cfg['ports'])));
            }
            $ports = array_values(array_filter($ports, fn($p) => $p > 0));
        }
        $port = (int)($cfg['port'] ?? 60000);

        // strategy
        $this->setProxyStrategy($cfg['strategy'] ?? 'random');

        if ($user && $pass) {
            if (!empty($ports)) {
                $this->useWebUnblocker($user, $pass, $host, $ports);
            } else {
                $this->useWebUnblocker($user, $pass, $host, $port);
            }
        } else {
            $this->useProxy = false;
        }
    }

    public function setLogin($user, $pass, $showbalance = true)
    {
        $this->username = $user;
        $this->password = $pass;
        $this->showbalance = $showbalance;
        $this->PATH = storage_path('cookies');

        date_default_timezone_set('Asia/Bangkok');

        // เปิดใช้ proxy ตาม config อัตโนมัติ (ถ้าตั้งค่าไว้)
        $this->applyProxyFromConfig();

        // log แบบปลอดภัย (ไม่ dump cfg ตรง ๆ)
        $this->logStep('proxy.cfg', [
            'enabled' => (bool)(config('proxy.web_unblocker.enabled') ?? false),
            'host' => $this->proxyHost ?: null,
            'port' => $this->proxyPort ?: null,
            'ports' => !empty($this->proxyPorts) ? $this->proxyPorts : null,
            'strategy' => $this->proxyStrategy,
            'useProxy' => $this->useProxy,
            'user_set' => (bool)$this->proxyUser,
        ]);
    }

    public function setAccountNumber($accnum)
    {
        if (!is_string($accnum)) exit("Account number must be string.");
        if (strlen($accnum) !== 10) exit("Account number must be 10 digits.");

        $this->accnum = $accnum;
        $this->cookiefilename .= $accnum;
        $this->parafilename .= $accnum;
        $this->ownidfilename .= $accnum;
        $this->datarssofilename .= $accnum;
        $this->companyfilename .= $accnum;

        $logDir = storage_path('logs/kbank');
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/' . $this->accnum . '_' . date('Y_m_d') . '.log';
    }

    // ===== Utilities =====

    private function reqId(): string
    {
        return date('YmdHis') . sprintf('%06d', mt_rand(0, 999999));
    }

    private function isSecurityPage(string $bodyOrHeaders): bool
    {
        if (stripos($bodyOrHeaders, 'HTTP/1.1 555') !== false) return true;
        if (stripos($bodyOrHeaders, '555 Security') !== false) return true;
        if (stripos($bodyOrHeaders, '_event_transid') !== false) return true;
        if (stripos($bodyOrHeaders, '<html') !== false && stripos($bodyOrHeaders, 'text/html') !== false) return true;
        return false;
    }

    private function warmUpForWaf(): void
    {
        curl_setopt($this->ch, CURLOPT_URL, 'https://kbiz.kasikornbank.com/menu/account/account/recent-transaction');
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_ENCODING, "");
        $this->setCurlHeaders([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'User-Agent: ' . $this->UA,
            'Referer: https://kbiz.kasikornbank.com/',
            'Accept-Language: th,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'DNT: 1',
            'Host: kbiz.kasikornbank.com',
            'Referer: https://kbiz.kasikornbank.com/authen/ib/redirectToIB.jsp',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'sec-ch-ua: "Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ]);
        $resp = curl_exec($this->ch);
        $hsize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers = $this->get_headers_from_curl_response(substr($resp, 0, $hsize));
        $body = substr($resp, $hsize);
        $this->logStep('waf.warmup', [
            'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'resp_headers' => $headers,
            'body_preview' => $this->sampleBody($body),
        ]);
    }

    private function randSleepMs(int $min = 300, int $max = 800): void
    {
        if (!$this->randSleepMs) {
            return;
        }
        usleep(mt_rand($min * 1000, $max * 1000));
    }

    private function logStep(string $step, array $data = []): void
    {
        if (!$this->logEnabled) {
            return;
        }
        if (!$this->logFile) return;
        if ($this->maskSensitive) $data = $this->maskArray($data);
        $row = [
            'ts' => date('Y-m-d H:i:s'),
            'step' => $step,
            'acc' => $this->accnum,
            'data' => $data,
        ];
        @file_put_contents($this->logFile, json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function maskArray(array $arr): array
    {
        $keys = [
            'Authorization', 'authorization', 'X-SESSION-TOKEN', 'x-session-token', 'X_SESSION_TOKEN',
            'OWNERID', 'ownerId', 'COMPANYID', 'companyId', 'dataRsso', 'tokenId',
            'user', 'pass', 'password', 'proxyUser', 'proxyPass', 'PROXY_USER', 'PROXY_PASS'
        ];
        $masked = $arr;
        array_walk_recursive($masked, function (&$v, $k) use ($keys) {
            if (in_array($k, $keys, true) && is_string($v) && strlen($v) > 2) {
                $v = substr($v, 0, 2) . '***' . substr($v, -2);
            }
        });
        return $masked;
    }

    private function sampleBody(string $body): string
    {
        if ($this->logBodyPreviewLen <= 0) return '';
        $b = mb_substr($body, 0, $this->logBodyPreviewLen, 'UTF-8');
        if (strlen($body) > strlen($b)) $b .= "\n...[truncated]...";
        return $b;
    }

    public function get_headers_from_curl_response($raw)
    {
        $raw = (string)$raw;
        $raw = rtrim($raw, "\r\n");
        if ($raw === '') return [];

        $blocks = preg_split("/\r\n\r\n/", $raw);
        $block = '';
        for ($i = count($blocks) - 1; $i >= 0; $i--) {
            $b = trim($blocks[$i]);
            if ($b !== '') {
                $block = $b;
                break;
            }
        }
        if ($block === '') return [];

        $headers = [];
        $lines = explode("\r\n", $block);
        foreach ($lines as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
                continue;
            }
            $pos = strpos($line, ':');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            if (isset($headers[$key])) {
                if (is_array($headers[$key])) {
                    $headers[$key][] = $val;
                } else {
                    $headers[$key] = [$headers[$key], $val];
                }
            } else {
                $headers[$key] = $val;
            }
            $lkey = strtolower($key);
            if (!isset($headers[$lkey])) $headers[$lkey] = $headers[$key];
        }
        return $headers;
    }

    private function readFile($path)
    {
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return "";
    }

    private function curlInit()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->PATH . '/' . $this->cookiefilename);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->PATH . '/' . $this->cookiefilename);

        if (defined('CURL_HTTP_VERSION_1_1')) {
            curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }

        if ($this->useProxy && $this->proxyHost) {
            $chosenPort = $this->pickProxyPort();

            curl_setopt($this->ch, CURLOPT_PROXY, $this->proxyHost);
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $chosenPort);
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // DC: HTTP proxy / Web Unblocker ก็โอเค
            curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, true);    // CONNECT สำหรับ HTTPS
            curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->proxyUser . ':' . $this->proxyPass);

            $this->logStep('proxy.applied', [
                'host' => $this->proxyHost,
                'port' => $chosenPort,
                'strategy' => $this->proxyStrategy,
                'ports_all' => $this->proxyPorts ?: [$this->proxyPort],
                'user' => $this->proxyUser ? 'set' : 'empty',
            ]);
        }
    }

    private function setCurlHeaders(array $headers)
    {
        $this->lastRequestHeaders = $headers;
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    }

    private function isHtmlOrNotJson(string $body, array $respHeaders): bool
    {
        $ct = '';
        foreach ($respHeaders as $k => $v) {
            if (strtolower($k) === 'content-type') {
                $ct = strtolower($v);
                break;
            }
        }
        if ($ct && strpos($ct, 'application/json') !== false) return false;
        $lead = ltrim($body);
        if ($lead !== '' && isset($lead[0]) && $lead[0] === '<') return true;
        $decoded = json_decode($body, true);
        return $decoded === null;
    }

    private function jsonDecodeSafe(string $body)
    {
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) return null;
        return $data;
    }

    private function buildKbizHeadersMinimal(array $extras = []): array
    {
        $h = [
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json',
            'User-Agent: ' . $this->UA,
            'X-APP-ID: KBIZWEB',
            'Accept-Language: th,en;q=0.9',
            'DNT: 1',
        ];
        if (!empty($this->OWNERID)) {
            $h[] = 'X-IB-ID: ' . $this->OWNERID;
            $h[] = 'X-SESSION-IBID: ' . $this->OWNERID;
        }
        if (!empty($this->X_SESSION_TOKEN)) {
            $h[] = 'Authorization: ' . $this->X_SESSION_TOKEN;
        }
        $h[] = 'X-REQUEST-ID: ' . $this->reqId();

        foreach ($extras as $line) {
            $h[] = $line;
        }
        return $h;
    }

    private function retryOnceIfChallenged(callable $fn)
    {
        $res = $fn();
        $status = $res['status'] ?? 0;
        $headers = $res['headers'] ?? [];
        $body = $res['body'] ?? '';

        $isUnauthorized = ($status === 401) || (stripos($body, 'UNAUTHORIZED') !== false);
        $isHtmlOrWaf = $this->isHtmlOrNotJson($body, $headers);

        if ($isUnauthorized) {
            $this->logStep('retry.trigger', ['status' => $status, 'reason' => '401']);
            $this->X_SESSION_TOKEN = '';
            $this->deleteFile();
            if (!$this->login()) return $res;
            $res2 = $fn();
            $this->logStep('retry.result', [
                'status' => $res2['status'] ?? 0,
                'body_preview' => $this->sampleBody($res2['body'] ?? '')
            ]);
            return $res2;
        }

        if ($isHtmlOrWaf) {
            $this->logStep('retry.trigger', ['status' => $status, 'reason' => 'waf/html']);
            $this->warmUpForWaf();
            $res2 = $fn();
            $this->logStep('retry.result', [
                'status' => $res2['status'] ?? 0,
                'body_preview' => $this->sampleBody($res2['body'] ?? '')
            ]);
            return $res2;
        }

        return $res;
    }

    private function retryOnceIfChallenged_(callable $fn)
    {
        $res = $fn();
        $status = $res['status'] ?? 0;
        $headers = $res['headers'] ?? [];
        $body = $res['body'] ?? '';

        if ($status === 401 || stripos($body, 'UNAUTHORIZED') !== false || $this->isHtmlOrNotJson($body, $headers)) {
            $this->logStep('retry.trigger', ['status' => $status, 'reason' => 'challenge_or_unauth']);
            $this->X_SESSION_TOKEN = '';
            $this->deleteFile();
            if (!$this->login()) {
                return $res;
            }
            $res2 = $fn();
            $this->logStep('retry.result', [
                'status' => $res2['status'] ?? 0,
                'body_preview' => $this->sampleBody($res2['body'] ?? '')
            ]);
            return $res2;
        }
        return $res;
    }

    // === [WAF-Retry] หมุน proxy หนึ่งครั้งบนแฮนด์เดิม ===
    private function rotateProxyOnce(): bool
    {
        if (!($this->useProxy && $this->proxyHost)) {
            $this->logStep('proxy.rotate.skip', ['reason' => 'no_proxy']);
            return false;
        }
        $newPort = $this->pickProxyPort();
        curl_setopt($this->ch, CURLOPT_PROXYPORT, $newPort);
        $this->logStep('proxy.rotate', [
            'host' => $this->proxyHost,
            'new_port' => $newPort,
            'strategy' => $this->proxyStrategy,
        ]);
        return true;
    }

    // === [WAF-Retry] ล้างไฟล์ + login ใหม่ตั้งแต่ต้น ===
    private function reloginFromScratch(): bool
    {
        $this->logStep('relogin.begin', []);
        $this->X_SESSION_TOKEN = '';
        $this->OWNERID = '';
        $this->COMPANYID = '';
        $this->deleteFile();
        $this->curlInit();
        $ok = $this->login();
        $this->logStep('relogin.result', ['ok' => $ok]);
        return $ok;
    }

    // ===== Flow =====

    public function login()
    {
        date_default_timezone_set('Asia/Bangkok');
        $this->curlInit();

        $this->X_SESSION_TOKEN = $this->readFile($this->PATH . '/' . $this->parafilename);
        $this->OWNERID = $this->readFile($this->PATH . '/' . $this->ownidfilename);
        $dataRsso = $this->readFile($this->PATH . '/' . $this->datarssofilename);
        $this->COMPANYID = $this->readFile($this->PATH . '/' . $this->companyfilename);

        $this->logStep('init.load_files', [
            'cookieFile' => $this->PATH . '/' . $this->cookiefilename,
            'paraFile' => $this->PATH . '/' . $this->parafilename,
            'ownidFile' => $this->PATH . '/' . $this->ownidfilename,
            'rssoFile' => $this->PATH . '/' . $this->datarssofilename,
            'companyFile' => $this->PATH . '/' . $this->companyfilename,
            'X_SESSION_TOKEN' => $this->X_SESSION_TOKEN ? '[set]' : '',
            'OWNERID' => $this->OWNERID ? '[set]' : '',
            'COMPANYID' => $this->COMPANYID ? '[set]' : '',
            'dataRsso_len' => strlen($dataRsso),
        ]);

        $this->logStep('proxy.cfg.login', [
            'enabled' => (bool)(config('proxy.web_unblocker.enabled') ?? false),
            'host' => $this->proxyHost ?: null,
            'port' => $this->proxyPort ?: null,
            'ports' => !empty($this->proxyPorts) ? $this->proxyPorts : null,
            'strategy' => $this->proxyStrategy,
            'useProxy' => $this->useProxy,
            'user_set' => (bool)$this->proxyUser,
        ]);

        $ip = $this->testOutboundIp();
        $this->logStep('chk.out_ip', [
            'out_ip' => $ip,
        ]);

        if ($this->X_SESSION_TOKEN !== '' && $this->OWNERID !== '') {
            $this->logStep('login.fastpath', ['reason' => 'reuse_existing_token']);
            return true;
        }

        $this->deleteFile();
        $this->curlInit();

        // 1) GET login.jsp
        curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/authen/login.jsp?lang=en");
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        $this->setCurlHeaders([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: https://kbiz.kasikornbank.com/authen/login.jsp?lang=en',
            'User-Agent: ' . $this->UA,
            'Accept-Language: th,en;q=0.9',
        ]);
        $resp1 = curl_exec($this->ch);
        $hsize1 = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers1 = $this->get_headers_from_curl_response(substr($resp1, 0, $hsize1));
        $body1 = substr($resp1, $hsize1);
        $this->logStep('login.jsp.GET', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'resp_headers' => $headers1,
            'body_preview' => $this->sampleBody($body1),
        ]);

        $html = str_replace(array("\r", "\t", "\n"), "", $body1);
        preg_match('/(<input type="hidden" name="tokenId" id="tokenId" value=")(.*?)("\/>)/', $html, $m);
        $tokenid = $m[2] ?? '';
        $this->logStep('login.jsp.token', ['tokenId' => $this->maskSensitive && $tokenid ? substr($tokenid, 0, 2) . '***' . substr($tokenid, -2) : $tokenid]);

        if ($tokenid == '') {
            $this->logStep('login.invalid_tokenId', []);
            return false;
        }

        // 2) POST login.do
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://kbiz.kasikornbank.com/authen/login.jsp?lang=en');
        curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/authen/login.do");
        curl_setopt($this->ch, CURLOPT_POST, 1);
        $post2 = "tokenId=" . $tokenid . "&userName=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&cmd=authenticate&locale=en&captcha=&app=0";
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post2);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        $this->setCurlHeaders([
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: https://kbiz.kasikornbank.com/authen/login.jsp?lang=en',
            'User-Agent: ' . $this->UA,
            'Accept-Language: th,en;q=0.9',
        ]);
        $resp2 = curl_exec($this->ch);
        $hsize2 = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers2 = $this->get_headers_from_curl_response(substr($resp2, 0, $hsize2));
        $body2 = substr($resp2, $hsize2);
        $this->logStep('login.do.POST', [
            'request_headers' => $this->lastRequestHeaders,
            'post_string' => $this->maskSensitive ? '[masked]' : $post2,
            'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'resp_headers' => $headers2,
            'body_preview' => $this->sampleBody($body2),
        ]);

        // 3) GET redirectToIB.jsp
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://kbiz.kasikornbank.com/authen/login.do');
        curl_setopt($this->ch, CURLOPT_URL, 'https://kbiz.kasikornbank.com/authen/ib/redirectToIB.jsp');
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        $this->setCurlHeaders([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: https://kbiz.kasikornbank.com/authen/login.do',
            'User-Agent: ' . $this->UA,
            'Accept-Language: th,en;q=0.9',
        ]);
        $resp3 = curl_exec($this->ch);
        $hsize3 = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers3 = $this->get_headers_from_curl_response(substr($resp3, 0, $hsize3));
        $body3 = substr($resp3, $hsize3);

        if (preg_match("/.*?Invalid User ID or Password.*?/", $body3)) {
            $this->logStep('login.invalid_credential', ['body_preview' => $this->sampleBody($body3)]);
            return false;
        }
        if (preg_match("/.*?Unsuccessful Login.*?/", $body1)) {
            $this->logStep('login.unsuccessful', ['body_preview' => $this->sampleBody($body1)]);
            return false;
        }

        $redirect_to = $this->cutString($body3, 'window.top.location.href = "', '";');
        $this->logStep('redirectToIB.jsp.GET', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'resp_headers' => $headers3,
            'redirect_to' => $redirect_to,
            'body_preview' => $this->sampleBody($body3),
        ]);

        // 4) follow redirect (เพื่อรับ cookie WAF/AlteonP)
        curl_setopt($this->ch, CURLOPT_URL, $redirect_to);
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        $this->setCurlHeaders([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'User-Agent: ' . $this->UA,
            'Referer: https://kbiz.kasikornbank.com/authen/ib/redirectToIB.jsp',
            'Accept-Language: th,en;q=0.9',
        ]);
        $resp4 = curl_exec($this->ch);
        $hsize4 = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headers4 = $this->get_headers_from_curl_response(substr($resp4, 0, $hsize4));
        $body4 = substr($resp4, $hsize4);
        $this->logStep('ib.redirect.follow', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
            'resp_headers' => $headers4,
            'body_preview' => $this->sampleBody($body4),
        ]);

        // เก็บ dataRsso จากหน้า redirect
        $dataRsso = $this->cutString($body3, 'dataRsso=', '";');
        file_put_contents($this->PATH . '/' . $this->datarssofilename, $dataRsso);
        $this->logStep('login.rsso_saved', ['dataRsso_len' => strlen($dataRsso)]);

        // validateSession เพื่อรับ token + โปรไฟล์ (ibId + companyId)
        $this->validateSession($dataRsso);

        if ($this->X_SESSION_TOKEN === '' || $this->OWNERID === '') {
            $this->logStep('login.failed_no_token_owner', []);
            return false;
        }

        $this->logStep('login.success', [
            'OWNERID' => $this->OWNERID ? '[set]' : '',
            'X_SESSION_TOKEN' => $this->X_SESSION_TOKEN ? '[set]' : '',
        ]);
        return true;
    }

    private function validateSession($dataRsso)
    {
        if (empty($dataRsso)) {
            $dataRsso = trim($this->readFile($this->PATH . '/' . $this->datarssofilename));
            if ($dataRsso === '') {
                $this->logStep('validateSession.missing_rsso', []);
                return false;
            }
        }

        $post_fields = ['dataRsso' => $dataRsso];

        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        $headers = $this->buildKbizHeadersMinimal();
        $this->setCurlHeaders($headers);

        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/services/api/authentication/validateSession");

        $response = curl_exec($this->ch);
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $headersResp = $this->get_headers_from_curl_response($rawHeaders);

        $newToken = $headersResp['x-session-token'] ?? ($headersResp['X-SESSION-TOKEN'] ?? '');

        $body_array = $this->jsonDecodeSafe($body) ?? [];
        $this->OWNERID = $body_array['data']['userProfiles'][0]['ibId'] ?? $this->OWNERID;
        $this->COMPANYID = $body_array['data']['userProfiles'][0]['companyId'] ?? $this->COMPANYID;

        $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200 && !empty($newToken)) {
            $this->X_SESSION_TOKEN = $newToken;
            $this->setToken();
        }

        $this->logStep('validateSession.POST', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => $httpCode,
            'resp_headers' => $headersResp,
            'OWNERID' => $this->OWNERID ? '[set]' : '',
            'COMPANYID' => $this->COMPANYID ? '[set]' : '',
            'X_SESSION_TOKEN' => $this->X_SESSION_TOKEN ? '[set]' : '',
            'body_preview' => $this->sampleBody($body),
        ]);
    }

    private function setToken()
    {
        if (!empty($this->X_SESSION_TOKEN)) {
            file_put_contents($this->PATH . '/' . $this->parafilename, $this->X_SESSION_TOKEN);
        }
        file_put_contents($this->PATH . '/' . $this->ownidfilename, $this->OWNERID ?? '');
        file_put_contents($this->PATH . '/' . $this->companyfilename, $this->COMPANYID ?? '');

        $this->logStep('token.persist', [
            'OWNERID' => $this->OWNERID ? '[set]' : '',
            'COMPANYID' => $this->COMPANYID ? '[set]' : '',
            'X_SESSION_TOKEN' => $this->X_SESSION_TOKEN ? '[set]' : '',
        ]);
    }

    private function refreshSession(): bool
    {
        $dataRsso = $this->readFile($this->PATH . '/' . $this->datarssofilename);
        if ($dataRsso === '') {
            $this->logStep('refresh.no_rsso', []);
            return false;
        }

        if (!$this->ch) $this->curlInit();
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_URL, 'https://kbiz.kasikornbank.com/services/api/authentication/validateSession');
        curl_setopt($this->ch, CURLOPT_POST, 1);
        $headers = $this->buildKbizHeadersMinimal();
        $this->setCurlHeaders($headers);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode(['dataRsso' => $dataRsso]));

        $response = curl_exec($this->ch);

        if (curl_errno($this->ch)) {
            $this->logStep('refresh.curl_error', ['errno' => curl_errno($this->ch)]);
            return false;
        }

        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headersResp = $this->get_headers_from_curl_response(substr($response, 0, $header_size));
        $body = substr($response, $header_size);

        $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $newToken = $headersResp['x-session-token'] ?? ($headersResp['X-SESSION-TOKEN'] ?? '');

        $bodyArr = $this->jsonDecodeSafe($body) ?? [];
        $this->OWNERID = $bodyArr['data']['userProfiles'][0]['ibId'] ?? $this->OWNERID;
        $this->COMPANYID = $bodyArr['data']['userProfiles'][0]['companyId'] ?? $this->COMPANYID;

        if ($httpCode === 200 && !empty($newToken)) {
            $this->X_SESSION_TOKEN = $newToken;
            $this->setToken();
        }

        $this->logStep('refresh.POST', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => $httpCode,
            'resp_headers' => $headersResp,
            'body_preview' => $this->sampleBody($body),
        ]);

        return ($httpCode === 200);
    }

    private function refreshSessionQuick(array $ctxHeaders = []): bool
    {
        if ($this->X_SESSION_TOKEN === '' || $this->OWNERID === '') {
            $this->logStep('refresh.quick.skip_no_token_owner', []);
            return false;
        }

        if (!$this->ch) $this->curlInit();
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_URL, 'https://kbiz.kasikornbank.com/services/api/refreshSession');
        curl_setopt($this->ch, CURLOPT_POST, 1);

        $headers = $this->buildKbizHeadersMinimal(array_merge([
            'X-URL: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
            'Referer: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
            'X-RE-FRESH: Y',
            'X-VERIFY: Y',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Connection: keep-alive',
            'Content-Length: 2',
            'Host: kbiz.kasikornbank.com',
            'Origin: https://kbiz.kasikornbank.com',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'X-LATITUDE: undefined',
            'X-LONGITUDE: undefined',
            'sec-ch-ua: "Google Chrome";v="141", "Not?A_Brand";v="8","Chromium";v="141"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ], $ctxHeaders));

        $this->setCurlHeaders($headers);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, '{}');

        $resp = curl_exec($this->ch);
        $hsize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headersResp = $this->get_headers_from_curl_response(substr($resp, 0, $hsize));
        $body = substr($resp, $hsize);
        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        if (isset($headersResp['x-session-token'])) $this->X_SESSION_TOKEN = $headersResp['x-session-token'];
        if (isset($headersResp['X-SESSION-TOKEN'])) $this->X_SESSION_TOKEN = $headersResp['X-SESSION-TOKEN'];
        $this->setToken();

        $this->logStep('refresh.quick.POST', [
            'request_headers' => $this->lastRequestHeaders,
            'status' => $code,
            'resp_headers' => $headersResp,
            'body_preview' => $this->sampleBody($body),
        ]);

        if ($code === 401 || stripos($body, 'UNAUTHORIZED') !== false) {
            $this->deleteFile();
            return false;
        }
        return ($code >= 200 && $code < 300);
    }

    public function getBalance()
    {
        if ($this->X_SESSION_TOKEN == '' || $this->OWNERID == '') {
            $this->logStep('balance.skip_no_token_owner', []);
            return false;
        }

        $call = function () {
            curl_setopt($this->ch, CURLOPT_HEADER, 1);
            $headers = $this->buildKbizHeadersMinimal([
                'X-URL: https://kbiz.kasikornbank.com/menu/account/account-summary',
                'Referer: https://kbiz.kasikornbank.com/menu/account/account-summary',
            ]);
            $this->setCurlHeaders($headers);

            curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/services/api/bankaccountget/getOwnBankAccountFromList");
            curl_setopt($this->ch, CURLOPT_POST, 1);

            $formdata = [
                'language' => 'th',
                'acctNo' => $this->accnum,
                'accountType' => "CA,FD,SA",
                'checkBalance' => "Y",
                'custType' => "IX",
                'ownerId' => !empty($this->COMPANYID) ? $this->COMPANYID : $this->OWNERID,
                'ownerType' => 'Company',
                'nicknameType' => 'OWNAC',
            ];
            $payload = json_encode($formdata);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $payload);

            $resp = curl_exec($this->ch);
            $hsize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
            $headersResp = $this->get_headers_from_curl_response(substr($resp, 0, $hsize));
            $body = substr($resp, $hsize);

            $this->logStep('balance.POST', [
                'request_headers' => $this->lastRequestHeaders,
                'post_json' => $payload,
                'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
                'resp_headers' => $headersResp,
                'body_preview' => $this->sampleBody($body),
            ]);

            return [
                'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
                'headers' => $headersResp,
                'body' => $body,
            ];
        };

        $res = $this->retryOnceIfChallenged($call);
        if (($res['status'] ?? 0) >= 400) return false;

        $row = $this->jsonDecodeSafe($res['body']);
        if (!$row) return false;

        $this->balance = $this->cutString($res['body'], '"availableBalance":"', '","');
        return $this->balance;
    }

    public function DateDiffMin($start)
    {
        $datenow = now()->toDateTimeString();
        $date = new Carbon($start, config('app.timezone'));

        return $date->floatDiffInMinutes($datenow, false);
    }

    public function getTransaction()
    {
        if ($this->X_SESSION_TOKEN == '' || $this->OWNERID == '') {
            $this->logStep('tx.skip_no_token_owner', []);
            return [];
        }

        $this->warmUpForWaf();
        $this->randSleepMs();

        $time = strtotime('-1 hours');  // ย้อนเวลาไป 2 ชั่วโมง
        $startdate = date('d/m/Y', $time);
        $enddate = date('d/m/Y');
        $formdata = [
            'acctNo' => $this->accnum,
            'acctType' => 'SA',
            'custType' => 'IX',
            'endDate' => $enddate,
            'ownerId' => !empty($this->COMPANYID) ? $this->COMPANYID : $this->OWNERID,
            'ownerType' => 'Company',
            'pageNo' => '1',
            'refKey' => '',
            'rowPerPage' => $this->rowPerPage,
            'startDate' => $startdate,
        ];
        $payload = json_encode($formdata);

        $buildHeaders = function () use ($payload) {
            return $this->buildKbizHeadersMinimal([
                'X-URL: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
                'Referer: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-ch-ua: "Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Dest: empty',
                'Origin: https://kbiz.kasikornbank.com',
                'Host: kbiz.kasikornbank.com',
                'Connection: keep-alive',
                'X-VERIFY: Y',
                'X-RE-FRESH: N'
            ]);
        };

        // === ยิงครั้งหลัก ===
        $doRequest = function () use ($payload, $buildHeaders) {
            $headers = $buildHeaders();
            $this->setCurlHeaders($headers);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/services/api/accountsummary/getRecentTransactionList");
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_HEADER, true);
            curl_setopt($this->ch, CURLOPT_ENCODING, "");
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $payload);

            $resp = curl_exec($this->ch);
            $hsize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
            $headersResp = $this->get_headers_from_curl_response(substr($resp, 0, $hsize));
            $body = substr($resp, $hsize);
            $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

            $this->logStep('tx.POST', [
                'attempt' => ($this->lastRequestHeaders ? 1 : 1), // แค่ log ช่วยอ่าน
                'request_headers' => $this->lastRequestHeaders,
                'post_json' => $payload,
                'status' => $code,
                'resp_headers' => $headersResp,
                'body_preview' => $this->sampleBody($body),
            ]);

            return [$code, $headersResp, $body];
        };

        [$code, $headersResp, $body] = $doRequest();

        // === [WAF-Retry] เงื่อนไขติด WAF/HTML ===
        $isWaf = ($code == 555) || $this->isHtmlOrNotJson($body, $headersResp);

        if ($isWaf) {
            $this->logStep('tx.waf_detected', ['status' => $code]);

            // 1) หมุน proxy แล้วลองใหม่ 1 รอบ
            $rotated = $this->rotateProxyOnce();
            if ($rotated) {
                $this->randSleepMs(500, 1200);
                [$code, $headersResp, $body] = $doRequest();
                $stillWaf = ($code == 555) || $this->isHtmlOrNotJson($body, $headersResp);
            } else {
                $stillWaf = true; // ไม่มี proxy ให้หมุน ถือว่ายัง WAF
            }

            // 2) ถ้ายัง WAF ต่อ → re-login ตั้งแต่ต้น แล้วลองอีก 1 รอบ
            if ($stillWaf) {
                $this->logStep('tx.waf_persist_relogin', []);
                if ($this->reloginFromScratch()) {
                    $this->randSleepMs(600, 1500);
                    [$code, $headersResp, $body] = $doRequest();
                } else {
                    $this->logStep('tx.relogin_failed', []);
                    return [];
                }
            }
        }

        // === เคส Unauthorized ชัดเจน
        if (stripos($body, 'UNAUTHORIZED') !== false || $code == 401) {
            $this->logStep('tx.UNAUTHORIZED', []);
            $this->deleteFile();
            return [];
        }

        // === ประมวลผลเมื่อสำเร็จ
        if ($code >= 200 && $code < 300) {
            $data = [];
            $row = json_decode($body);
            if (isset($row->data) && isset($row->data->recentTransactionList)) {
                $index = 0;
                foreach ($row->data->recentTransactionList as $item) {
                    if (($item->debitCreditIndicator ?? '') === 'DR') continue;
                    if ($this->DateDiffMin($item->transDate) > 30) continue;
                    if (empty($item->toAccountNumber)) {
                        $detail = $this->getDetail($item);
                        $data[$index]["report_id"] = $detail['frombank'] ?? '';
                        $item->toAccountNumber = $detail['fromacc'] ?? '';
                        $data[$index]["channel"] = $detail['frombank'] ?? '';
                        $titles = explode(' ', $detail['fromname'] ?? '');
                    } else {
                        $data[$index]["report_id"] = '';
                        $data[$index]["channel"] = $item->channelTh ?? '';
                        $titles = explode(' ', $item->fromAccountNameTh ?? '');
                    }
                    $title = $titles[1] ?? '';
                    $data[$index]["accno"] = $item->toAccountNumber ?? '';
                    $data[$index]["date"] = $item->transDate ?? '';
                    $data[$index]["out"] = $item->withdrawAmount ?? null;
                    $data[$index]["in"] = $item->depositAmount ?? null;
                    $data[$index]["fee"] = 0;
                    $data[$index]["fromaccno"] = str_replace(["x", "-"], ["", ""], $item->toAccountNumber ?? '');
                    if ($data[$index]["report_id"] != '') {
                        $data[$index]["info"] = ($item->transNameTh ?? '') . ' ' . ($detail['fromname'] ?? '') . ' / ' . $data[$index]["fromaccno"];
                    } else {
                        $data[$index]["info"] = ($item->transNameTh ?? '') . ' ' . ($item->fromAccountNameTh ?? '') . ' / X' . $data[$index]["fromaccno"];
                    }
                    $data[$index]["title"] = $title;
                    $index++;
                }
            }
            $this->refreshSessionQuick();
            $this->logStep('tx.parsed', ['count' => isset($data[0]) ? count($data) : 0]);
            return isset($data[0]) ? array_reverse($data) : [];
        }

        // ไม่สำเร็จหลังจากวนทั้งหมด
//        $this->refreshSessionQuick();
        $this->logStep('tx.fail_after_retry', ['final_status' => $code]);
        return [];
    }

    public function getDetail($data)
    {
        if ($this->X_SESSION_TOKEN == '' || $this->OWNERID == '') {
            $this->logStep('detail.skip_no_token_owner', []);
            return array();
        }

        $trandate = explode(' ', $data->transDate ?? '');

        $call = function () use ($data, $trandate) {
            curl_setopt($this->ch, CURLOPT_HEADER, 1);
            $headers = $this->buildKbizHeadersMinimal([
                'X-URL: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
                'Referer: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
            ]);
            $this->setCurlHeaders($headers);

            curl_setopt($this->ch, CURLOPT_URL, "https://kbiz.kasikornbank.com/services/api/accountsummary/getRecentTransactionDetail");
            curl_setopt($this->ch, CURLOPT_POST, 1);

            $formdata = [];
            $formdata['acctNo'] = $this->accnum;
            $formdata['debitCreditIndicator'] = 'CR';
            $formdata['custType'] = 'IX';
            $formdata['origRqUid'] = $data->origRqUid ?? '';
            $formdata['originalSourceId'] = $data->originalSourceId ?? '';
            $formdata['ownerId'] = !empty($this->COMPANYID) ? $this->COMPANYID : $this->OWNERID;
            $formdata['ownerType'] = 'Company';
            $formdata['transCode'] = $data->transCode ?? '';
            $formdata['transDate'] = $trandate[0] ?? '';
            $formdata['transType'] = $data->transType ?? '';

            $payload = json_encode($formdata);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $payload);

            $resp = curl_exec($this->ch);
            $hsize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
            $headersResp = $this->get_headers_from_curl_response(substr($resp, 0, $hsize));
            $body = substr($resp, $hsize);

            $this->logStep('detail.POST', [
                'request_headers' => $this->lastRequestHeaders,
                'post_json' => $payload,
                'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
                'resp_headers' => $headersResp,
                'body_preview' => $this->sampleBody($body),
            ]);

            return [
                'status' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE),
                'headers' => $headersResp,
                'body' => $body,
            ];
        };

        $res = $this->retryOnceIfChallenged($call);
        if (($res['status'] ?? 0) >= 400) return [
            'frombank' => 'KBANK',
            'fromacc' => '555',
            'fromname' => 'BlockMan Sorry',
        ];

        $row = $this->jsonDecodeSafe($res['body']);
        $fromacc = [];
        if (isset($row['data'])) {
            $fromacc['frombank'] = $row['data']['bankNameEn'] ?? '';
            $fromacc['fromacc'] = $row['data']['toAccountNo'] ?? '';
            $fromacc['fromname'] = $row['data']['toAccountNameTh'] ?? '';
        }

        return $fromacc;
    }

    private function deleteFile()
    {
        @unlink($this->PATH . '/' . $this->cookiefilename);
        @unlink($this->PATH . '/' . $this->parafilename);
        @unlink($this->PATH . '/' . $this->ownidfilename);
        @unlink($this->PATH . '/' . $this->datarssofilename);
        @unlink($this->PATH . '/' . $this->companyfilename);

        $this->logStep('files.deleted', [
            'cookie' => $this->PATH . '/' . $this->cookiefilename,
            'para' => $this->PATH . '/' . $this->parafilename,
            'ownid' => $this->PATH . '/' . $this->ownidfilename,
            'rsso' => $this->PATH . '/' . $this->datarssofilename,
            'company' => $this->PATH . '/' . $this->companyfilename,
        ]);
    }

    // ==== helpers (เดิม) ====

    public function cutString($content, $text1, $text2)
    {
        $fcontents2 = stristr($content, $text1);
        if ($fcontents2 === false) return '';
        $rest2 = substr($fcontents2, strlen($text1));
        $extra2 = stristr($fcontents2, $text2);
        if ($extra2 === false) return '';
        $titlelen2 = strlen($rest2) - strlen($extra2);
        return trim(substr($rest2, 0, $titlelen2));
    }

    private function get_str_between($str, $starting_word, $ending_word)
    {
        $subtring_start = strpos($str, $starting_word);
        if ($subtring_start === false) return '';
        $subtring_start += strlen($starting_word);
        $end = strpos($str, $ending_word, $subtring_start);
        if ($end === false) return '';
        return substr($str, $subtring_start, $end - $subtring_start);
    }

    public function generate_header($data)
    {
        $base = $this->buildKbizHeadersMinimal([
            'X-URL: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
            'Referer: https://kbiz.kasikornbank.com/menu/account/account/recent-transaction',
        ]);
        return $base;
    }

    /** ออกผ่าน proxy (ดีสำหรับตรวจว่าตั้ง proxy สำเร็จไหม) */
    public function testOutboundIp(): ?string
    {
        if (!$this->outBoundIP) {
            return null;
        }

        $this->curlInit();
        curl_setopt($this->ch, CURLOPT_URL, 'https://api.ipify.org');
        curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
        $resp = curl_exec($this->ch);
        if (curl_errno($this->ch)) return null;
        return trim((string)$resp);
    }

    /** ออกตรงไม่ผ่าน proxy (เอาไว้เทียบ IP จริงของเซิร์ฟเวอร์) */
    public function testOutboundIpDirect(): ?string
    {
        $ch = curl_init('https://api.ipify.org');
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp ? trim($resp) : null;
    }

}
