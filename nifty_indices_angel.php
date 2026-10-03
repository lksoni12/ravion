<?php
/**
 * NSE Sector Indices Dashboard — Angel One SmartAPI Edition (v3 - MEMORY FIX)
 * Single-file PHP: uses Angel One SmartAPI (free) for authenticated,
 * reliable live quotes instead of fragile NSE scraping.
 *
 * FIXES in this version:
 *   v2 fixes (still included):
 *     - Quote response parsing reads response.data.fetched[] correctly
 *     - Login headers match confirmed-working manual login script
 *     - Diagnostic endpoints: ?ajax=test_login, ?ajax=test_tokens
 *   v3 fix (this version):
 *     - FATAL MEMORY ERROR FIXED: Angel One's full scrip master JSON
 *       covers NSE+BSE+NFO+MCX+CDS (100k+ instruments), and json_decode()
 *       on the whole file was exhausting PHP's default 128MB memory_limit.
 *       Fix: temporarily raise memory_limit to 512M ONLY during the
 *       once-a-day scrip master rebuild, immediately null out the large
 *       decoded array after extracting just the ~150 symbols we need, and
 *       cache that tiny filtered result so every subsequent page load
 *       reads a small JSON file instead of ever re-parsing the full master.
 *
 * Prerequisites (one-time setup):
 *   1. Angel One account with SmartAPI enabled (https://smartapi.angelbroking.com)
 *   2. API Key from SmartAPI dashboard
 *   3. Enable TOTP (2FA) → scan QR with Google Authenticator → save the
 *      BASE32 SECRET shown under the QR code (not the 6-digit code)
 *   4. Client Code (your Angel One login ID) and PIN/MPIN
 *   5. Fill the CONFIG section below
 *
 * Usage: php -S localhost:8000 nifty_indices_angel.php
 * Diagnostics:
 *   http://localhost:8000/?ajax=test_login   -> raw login response
 *   http://localhost:8000/?ajax=test_tokens  -> symbol/token resolution report
 *   http://localhost:8000/?ajax=rebuild_tokens -> force rebuild the token map
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// ════════════════════════════════════════════════════════════════════════
// CONFIG — FILL THESE IN FROM YOUR ANGEL ONE SMARTAPI DASHBOARD
// ════════════════════════════════════════════════════════════════════════
define('ANGEL_API_KEY',       'gyXIFGvz');
define('ANGEL_CLIENT_CODE',   'D59697331');
define('ANGEL_PIN',           '1985');
define('ANGEL_TOTP_SECRET',   'TESZ4K7GTBVS765WFVLBMRDYLY'); // base32 secret from QR setup screen
define('CACHE_DIR', __DIR__ . '/angel_cache');
define('TOKEN_STORE', __DIR__ . '/angel_tokens.json');
define('CACHE_TTL', 15);
define('QUOTE_CACHE_TTL', 8);
define('NSE_INDICES_CACHE_TTL', 10); // NEW (v9): cache the NSE allIndices call itself
define('HISTORICAL_DAILY_TTL', 4 * 3600);
define('HISTORICAL_INTRADAY_TTL', 180);
define('SCHEMA_VERSION', 'v9');
if (!file_exists(CACHE_DIR)) @mkdir(CACHE_DIR, 0777, true);

// ════════════════════════════════════════════════════════════════════════
// SECTOR INDEX → STOCK SYMBOL MAP
// ════════════════════════════════════════════════════════════════════════
$SECTOR_CONSTITUENTS = [
    'NIFTY 50'                  => ['RELIANCE-EQ','HDFCBANK-EQ','ICICIBANK-EQ','INFY-EQ','TCS-EQ','HINDUNILVR-EQ','ITC-EQ','SBIN-EQ','BHARTIARTL-EQ','BAJFINANCE-EQ','KOTAKBANK-EQ','LT-EQ','AXISBANK-EQ','ASIANPAINT-EQ','MARUTI-EQ','SUNPHARMA-EQ','TITAN-EQ','ULTRACEMCO-EQ','WIPRO-EQ','ONGC-EQ','NTPC-EQ','POWERGRID-EQ','NESTLEIND-EQ','TECHM-EQ','TATAMOTORS-EQ','BAJAJFINSV-EQ','HCLTECH-EQ','ADANIENT-EQ','ADANIPORTS-EQ','CIPLA-EQ','COALINDIA-EQ','GRASIM-EQ','JSWSTEEL-EQ','DRREDDY-EQ','EICHERMOT-EQ','BRITANNIA-EQ','HINDALCO-EQ','INDUSINDBK-EQ','TATASTEEL-EQ','SBILIFE-EQ','HDFCLIFE-EQ','DIVISLAB-EQ','UPL-EQ','APOLLOHOSP-EQ','BPCL-EQ','HEROMOTOCO-EQ'],
    'NIFTY BANK'                => ['HDFCBANK-EQ','ICICIBANK-EQ','KOTAKBANK-EQ','AXISBANK-EQ','SBIN-EQ','INDUSINDBK-EQ','BANDHANBNK-EQ','FEDERALBNK-EQ','PNB-EQ','BANKBARODA-EQ','IDFCFIRSTB-EQ','AUBANK-EQ'],
    'NIFTY IT'                  => ['INFY-EQ','TCS-EQ','HCLTECH-EQ','WIPRO-EQ','TECHM-EQ','LTIM-EQ','MPHASIS-EQ','PERSISTENT-EQ','COFORGE-EQ','OFSS-EQ'],
    'NIFTY AUTO'                => ['MARUTI-EQ','TATAMOTORS-EQ','EICHERMOT-EQ','HEROMOTOCO-EQ','TVSMOTOR-EQ','ASHOKLEY-EQ','MOTHERSON-EQ','BOSCHLTD-EQ','MRF-EQ','EXIDEIND-EQ','BALKRISIND-EQ'],
    'NIFTY PHARMA'              => ['SUNPHARMA-EQ','CIPLA-EQ','DRREDDY-EQ','DIVISLAB-EQ','LUPIN-EQ','TORNTPHARM-EQ','ALKEM-EQ','AUROPHARMA-EQ','ZYDUSLIFE-EQ','PFIZER-EQ','ABBOTINDIA-EQ','NATCOPHARM-EQ','IPCALAB-EQ','LAURUSLABS-EQ'],
    'NIFTY FMCG'                => ['HINDUNILVR-EQ','ITC-EQ','NESTLEIND-EQ','BRITANNIA-EQ','TATACONSUM-EQ','GODREJCP-EQ','DABUR-EQ','MARICO-EQ','COLPAL-EQ','EMAMILTD-EQ','JUBLFOOD-EQ','UNITDSPR-EQ','VBL-EQ'],
    'NIFTY METAL'               => ['TATASTEEL-EQ','JSWSTEEL-EQ','HINDALCO-EQ','VEDL-EQ','SAIL-EQ','JINDALSTEL-EQ','NMDC-EQ','NATIONALUM-EQ','MOIL-EQ'],
    'NIFTY REALTY'              => ['DLF-EQ','GODREJPROP-EQ','OBEROIRLTY-EQ','PRESTIGE-EQ','PHOENIXLTD-EQ','BRIGADE-EQ','SOBHA-EQ'],
    'NIFTY ENERGY'              => ['RELIANCE-EQ','ONGC-EQ','NTPC-EQ','POWERGRID-EQ','BPCL-EQ','IOC-EQ','GAIL-EQ','NHPC-EQ','SJVN-EQ','TORNTPOWER-EQ','ADANIGREEN-EQ','ADANIPOWER-EQ','TATAPOWER-EQ'],
    'NIFTY MEDIA'               => ['SUNTV-EQ','ZEEENT-EQ','TV18BRDCST-EQ','SAREGAMA-EQ'],
    'NIFTY PSU BANK'            => ['SBIN-EQ','PNB-EQ','BANKBARODA-EQ','CANBK-EQ','UNIONBANK-EQ','BANKINDIA-EQ','INDIANB-EQ','CENTRALBK-EQ','UCOBANK-EQ','IOB-EQ','MAHABANK-EQ'],
    'NIFTY PRIVATE BANK'        => ['HDFCBANK-EQ','ICICIBANK-EQ','KOTAKBANK-EQ','AXISBANK-EQ','INDUSINDBK-EQ','BANDHANBNK-EQ','FEDERALBNK-EQ','IDFCFIRSTB-EQ','AUBANK-EQ','RBLBANK-EQ'],
    'NIFTY FINANCIAL SERVICES'  => ['HDFCBANK-EQ','ICICIBANK-EQ','KOTAKBANK-EQ','BAJFINANCE-EQ','BAJAJFINSV-EQ','SBIN-EQ','HDFCLIFE-EQ','SBILIFE-EQ','ICICIPRULI-EQ','ICICIGI-EQ','HDFCAMC-EQ','CHOLAFIN-EQ','SHRIRAMFIN-EQ','MUTHOOTFIN-EQ'],
    'NIFTY INFRASTRUCTURE'      => ['LT-EQ','ULTRACEMCO-EQ','RELIANCE-EQ','ONGC-EQ','NTPC-EQ','POWERGRID-EQ','ADANIPORTS-EQ','TATAPOWER-EQ','JSWSTEEL-EQ','HINDALCO-EQ','NHPC-EQ','SJVN-EQ'],
    'NIFTY OIL & GAS'           => ['RELIANCE-EQ','ONGC-EQ','IOC-EQ','BPCL-EQ','GAIL-EQ','HPCL-EQ','OIL-EQ','PETRONET-EQ','IGL-EQ','MGL-EQ'],
    'NIFTY HEALTHCARE INDEX'    => ['SUNPHARMA-EQ','CIPLA-EQ','DRREDDY-EQ','DIVISLAB-EQ','APOLLOHOSP-EQ','MAXHEALTH-EQ','FORTIS-EQ','METROPOLIS-EQ','LALPATHLAB-EQ','SYNGENE-EQ'],
    'NIFTY CONSUMER DURABLES'   => ['TITAN-EQ','HAVELLS-EQ','VOLTAS-EQ','WHIRLPOOL-EQ','BLUESTARCO-EQ','CROMPTON-EQ','DIXON-EQ','KAJARIACER-EQ'],
];

// ════════════════════════════════════════════════════════════════════════
// ANGEL ONE SMARTAPI CLIENT
// ════════════════════════════════════════════════════════════════════════
class AngelSmartAPI {
    const BASE_URL = 'https://apiconnect.angelone.in';
    const LOGIN_URL = '/rest/auth/angelbroking/user/v1/loginByPassword';
    const QUOTE_URL = '/rest/secure/angelbroking/market/v1/quote/';
    const REFRESH_URL = '/rest/auth/angelbroking/user/v1/generateTokens';
    const HISTORY_URL = '/rest/secure/angelbroking/historical/v1/getCandleData';

    private $apiKey, $clientCode, $pin, $totpSecret, $tokenFile;
    private $jwtToken = null, $refreshToken = null, $feedToken = null, $jwtExpiry = 0;
    public $lastError = '';

    public function __construct($apiKey, $clientCode, $pin, $totpSecret, $tokenFile = TOKEN_STORE) {
        $this->apiKey = $apiKey;
        $this->clientCode = $clientCode;
        $this->pin = $pin;
        $this->totpSecret = $totpSecret;
        $this->tokenFile = $tokenFile;
        $this->loadTokens();
    }

    private function generateTOTP() {
        $secret = $this->base32Decode(str_replace(' ', '', $this->totpSecret));
        $timeSlice = (int) floor(time() / 30);
        $timeBytes = pack('N2', 0, $timeSlice);
        $hash = hash_hmac('sha1', $timeBytes, $secret, true);
        $offset = ord($hash[19]) & 0x0F;
        $part1 = (ord($hash[$offset]) & 0x7F) << 24;
        $part2 = (ord($hash[$offset + 1]) & 0xFF) << 16;
        $part3 = (ord($hash[$offset + 2]) & 0xFF) << 8;
        $part4 = (ord($hash[$offset + 3]) & 0xFF);
        $code = ($part1 | $part2 | $part3 | $part4) % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($base32) {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(rtrim($base32, '='));
        $bits = '';
        foreach (str_split($base32) as $char) {
            $pos = strpos($map, $char);
            if ($pos === false) continue;
            $bits .= sprintf('%05b', $pos);
        }
        $bytes = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $bytes .= chr(bindec(substr($bits, $i, 8)));
        }
        return $bytes;
    }

    private function loadTokens() {
        if (file_exists($this->tokenFile)) {
            $data = json_decode(file_get_contents($this->tokenFile), true);
            if ($data) {
                $this->jwtToken = $data['jwtToken'] ?? null;
                $this->refreshToken = $data['refreshToken'] ?? null;
                $this->feedToken = $data['feedToken'] ?? null;
                $this->jwtExpiry = $data['jwtExpiry'] ?? 0;
            }
        }
    }

    private function saveTokens() {
        file_put_contents($this->tokenFile, json_encode([
            'jwtToken' => $this->jwtToken, 'refreshToken' => $this->refreshToken,
            'feedToken' => $this->feedToken, 'jwtExpiry' => $this->jwtExpiry,
        ]));
    }

    private function buildHeaders($includeAuth = true) {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-UserType: USER',
            'X-SourceID: WEB',
            'X-ClientLocalIP: 127.0.0.1',
            'X-ClientPublicIP: 106.51.74.1',
            'X-MACAddress: 00:00:00:00:00:00',
            'X-PrivateKey: ' . $this->apiKey,
        ];
        if ($includeAuth && $this->jwtToken) {
            $headers[] = 'Authorization: Bearer ' . $this->jwtToken;
        }
        return $headers;
    }

    // NEW (v9): exposes the current auth headers so external parallel-fetch
    // helpers (curl_multi based) can reuse this session's JWT without
    // going through the class's own sequential request() method.
    public function getAuthHeaders() {
        return $this->buildHeaders(true);
    }

    private function request($method, $endpoint, $body = null, $useAuth = true) {
        $url = self::BASE_URL . $endpoint;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => ($method === 'POST'),
            CURLOPT_POSTFIELDS => $body !== null ? json_encode($body) : null,
            CURLOPT_HTTPHEADER => $this->buildHeaders($useAuth),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->lastError = "cURL error: $err";
            return ['error' => $this->lastError];
        }
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            $this->lastError = "Invalid JSON (HTTP $httpCode): " . substr($response, 0, 300);
            return ['error' => $this->lastError];
        }
        if ($httpCode >= 400 || ($decoded['status'] ?? null) === false) {
            $msg = $decoded['message'] ?? "HTTP $httpCode";
            $code = $decoded['errorcode'] ?? '';
            $this->lastError = trim("$msg" . ($code ? " (errorcode: $code)" : ''));
            $decoded['error'] = $this->lastError;
        }
        return $decoded;
    }

    public function ensureValidSession() {
        if ($this->jwtToken && time() < $this->jwtExpiry - 300) return true;
        if ($this->refreshToken) {
            if ($this->refreshSession()) return true;
        }
        return $this->login();
    }

    public function forceRelogin() {
        $this->jwtToken = null;
        $this->jwtExpiry = 0;
        return $this->login();
    }

    public function login() {
        $totp = $this->generateTOTP();
        $body = ['clientcode' => $this->clientCode, 'password' => $this->pin, 'totp' => $totp];
        $resp = $this->request('POST', self::LOGIN_URL, $body, false);
        if (isset($resp['error'])) return false;
        if (!($resp['status'] ?? false)) {
            $this->lastError = $resp['message'] ?? 'Login failed';
            return false;
        }
        $this->jwtToken = $resp['data']['jwtToken'];
        $this->refreshToken = $resp['data']['refreshToken'];
        $this->feedToken = $resp['data']['feedToken'] ?? null;
        $this->jwtExpiry = time() + (27.5 * 3600);
        $this->saveTokens();
        return true;
    }

    private function refreshSession() {
        $body = ['refreshToken' => $this->refreshToken];
        $resp = $this->request('POST', self::REFRESH_URL, $body, true);
        if (isset($resp['error']) || !($resp['status'] ?? false)) return false;
        $this->jwtToken = $resp['data']['jwtToken'];
        $this->refreshToken = $resp['data']['refreshToken'] ?? $this->refreshToken;
        $this->feedToken = $resp['data']['feedToken'] ?? $this->feedToken;
        $this->jwtExpiry = time() + (27.5 * 3600);
        $this->saveTokens();
        return true;
    }

    public function testLogin() {
        $totp = $this->generateTOTP();
        $body = ['clientcode' => $this->clientCode, 'password' => $this->pin, 'totp' => $totp];
        $resp = $this->request('POST', self::LOGIN_URL, $body, false);
        $resp['_generated_totp'] = $totp;
        return $resp;
    }

    public function getSingleQuote($token, $alreadyRelogged = false) {
        if (!$this->ensureValidSession()) return ['error' => 'Session invalid: ' . $this->lastError];
        $body = ['mode' => 'FULL', 'exchangeTokens' => ['NSE' => [(string)$token]]];
        $resp = $this->request('POST', self::QUOTE_URL, $body, true);
        if (isset($resp['error']) || !($resp['status'] ?? false)) {
            if (!$alreadyRelogged && $this->forceRelogin()) {
                return $this->getSingleQuote($token, true);
            }
            return ['error' => $resp['error'] ?? ($resp['message'] ?? 'Quote request failed')];
        }
        $fetched = $resp['data']['fetched'] ?? [];
        if (empty($fetched)) {
            if (!$alreadyRelogged && $this->forceRelogin()) {
                return $this->getSingleQuote($token, true);
            }
            $unfetched = $resp['data']['unfetched'] ?? [];
            return ['error' => 'Invalid or unfetchable token', 'unfetched' => $unfetched];
        }
        return ['data' => $fetched[0]];
    }

    private function fetchBatchWithFallback($chunk, $alreadyRelogged = false) {
        $body = ['mode' => 'FULL', 'exchangeTokens' => ['NSE' => $chunk]];
        $resp = $this->request('POST', self::QUOTE_URL, $body, true);

        if (!isset($resp['error']) && ($resp['status'] ?? false)) {
            return [
                'fetched' => $resp['data']['fetched'] ?? [],
                'unfetched' => $resp['data']['unfetched'] ?? [],
                'badTokens' => [],
                'sessionWasRefreshed' => $alreadyRelogged,
            ];
        }

        if (!$alreadyRelogged) {
            $reloginOk = $this->forceRelogin();
            if ($reloginOk) {
                return $this->fetchBatchWithFallback($chunk, true);
            }
        }

        $fetched = [];
        $unfetched = [];
        $badTokens = [];
        foreach ($chunk as $token) {
            $single = $this->getSingleQuote($token, true);
            if (isset($single['data'])) {
                $fetched[] = $single['data'];
            } else {
                $badTokens[] = $token;
                $unfetched[] = ['token' => $token, 'reason' => $single['error'] ?? 'unknown'];
            }
        }
        return ['fetched' => $fetched, 'unfetched' => $unfetched, 'badTokens' => $badTokens, 'sessionWasRefreshed' => true];
    }

    public function getQuotes($symbols) {
        if (!$this->ensureValidSession()) return ['error' => 'Session invalid: ' . $this->lastError];
        if (empty($symbols)) return ['data' => []];

        global $SYMBOL_TOKEN_MAP;
        $tokens = [];
        foreach ($symbols as $sym) {
            if (isset($SYMBOL_TOKEN_MAP[$sym])) $tokens[] = (string) $SYMBOL_TOKEN_MAP[$sym];
        }
        if (empty($tokens)) return ['error' => 'No valid tokens found. Run ?ajax=test_tokens to diagnose.'];

        $allFetched = [];
        $allUnfetched = [];
        $allBadTokens = [];
        $sessionRefreshed = false;
        foreach (array_chunk($tokens, 50) as $chunk) {
            $result = $this->fetchBatchWithFallback($chunk);
            $allFetched = array_merge($allFetched, $result['fetched']);
            $allUnfetched = array_merge($allUnfetched, $result['unfetched']);
            $allBadTokens = array_merge($allBadTokens, $result['badTokens']);
            if (!empty($result['sessionWasRefreshed'])) $sessionRefreshed = true;
        }

        if (empty($allFetched) && !empty($allUnfetched)) {
            return ['error' => 'All symbols unfetched: ' . json_encode(array_slice($allUnfetched, 0, 3))];
        }

        $out = ['data' => $allFetched];
        if (!empty($allBadTokens)) $out['badTokens'] = $allBadTokens;
        if ($sessionRefreshed) $out['sessionWasRefreshed'] = true;
        return $out;
    }

    public function getCandles($symbolToken, $interval, $fromDate, $toDate) {
        if (!$this->ensureValidSession()) return ['error' => 'Session invalid: ' . $this->lastError];
        $body = [
            'exchange' => 'NSE',
            'symboltoken' => (string)$symbolToken,
            'interval' => $interval,
            'fromdate' => $fromDate,
            'todate' => $toDate,
        ];
        $resp = $this->request('POST', self::HISTORY_URL, $body, true);
        if (isset($resp['error'])) return $resp;
        if (!($resp['status'] ?? false)) return ['error' => $resp['message'] ?? 'Historical data request failed'];
        return ['data' => $resp['data'] ?? []];
    }
}

// ════════════════════════════════════════════════════════════════════════
// SYMBOL → TOKEN MAP — memory-safe build from Angel One's public scrip master
// ════════════════════════════════════════════════════════════════════════
define('SCRIP_MASTER_URL', 'https://margincalculator.angelone.in/OpenAPI_File/files/OpenAPIScripMaster.json');
define('FILTERED_MAP_CACHE', __DIR__ . '/angel_symbol_token_map.json');
define('SCRIP_MASTER_TTL', 86400);

function build_symbol_token_map($neededSymbols) {
    if (file_exists(FILTERED_MAP_CACHE) && (time() - filemtime(FILTERED_MAP_CACHE) < SCRIP_MASTER_TTL)) {
        $cached = json_decode(file_get_contents(FILTERED_MAP_CACHE), true);
        if ($cached && count($cached) > 0) return $cached;
    }
    $previousLimit = ini_get('memory_limit');
    ini_set('memory_limit', '512M');
    $neededSet = array_flip($neededSymbols);
    $map = [];
    $ch = curl_init(SCRIP_MASTER_URL);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_ENCODING => '']);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp !== false && $code === 200) {
        $decoded = json_decode($resp, true);
        $resp = null;
        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (($entry['exch_seg'] ?? '') !== 'NSE') continue;
                $sym = $entry['symbol'] ?? '';
                if (isset($neededSet[$sym])) $map[$sym] = (string)($entry['token'] ?? '');
            }
        }
        $decoded = null;
    }
    ini_set('memory_limit', $previousLimit);
    if (!empty($map)) { file_put_contents(FILTERED_MAP_CACHE, json_encode($map)); return $map; }
    if (file_exists(FILTERED_MAP_CACHE)) {
        $cached = json_decode(file_get_contents(FILTERED_MAP_CACHE), true);
        if ($cached) return $cached;
    }
    return [];
}

$ALL_NEEDED_SYMBOLS = [];
foreach ($SECTOR_CONSTITUENTS as $syms) { foreach ($syms as $s) $ALL_NEEDED_SYMBOLS[$s] = true; }
$SYMBOL_TOKEN_MAP = build_symbol_token_map(array_keys($ALL_NEEDED_SYMBOLS));

// ════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════
function cached_fetch($cacheKey, $fetchFn, $ttl = CACHE_TTL) {
    $file = CACHE_DIR . '/' . SCHEMA_VERSION . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cacheKey) . '.json';
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        $cached = json_decode(file_get_contents($file), true);
        if ($cached && isset($cached['data'])) return $cached;
    }
    $result = $fetchFn();
    if (isset($result['data'])) { file_put_contents($file, json_encode($result)); return $result; }
    if (file_exists($file)) {
        $cached = json_decode(file_get_contents($file), true);
        if ($cached && isset($cached['data'])) {
            $cached['stale'] = true;
            $cached['error'] = $result['error'] ?? 'Live fetch failed, showing cached data.';
            return $cached;
        }
    }
    return $result;
}

// UPDATED (v9): now wrapped in cached_fetch() with a short TTL. Previously
// this hit NSE fresh on EVERY extreme_stocks call (up to every 15s), even
// though the underlying data barely changes that fast.
function fetch_nse_all_indices() {
    return cached_fetch('nse_all_indices_raw', function () {
        $cookieFile = sys_get_temp_dir() . '/nse_cookies_indices.txt';
        if (!file_exists($cookieFile)) touch($cookieFile);
        $ch = curl_init('https://www.nseindia.com/');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_ENCODING => '', CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept: text/html']]);
        curl_exec($ch); curl_close($ch);
        $ch = curl_init('https://www.nseindia.com/api/allIndices');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_ENCODING => '', CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept: application/json', 'Referer: https://www.nseindia.com/market-data/live-market-indices']]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code !== 200) return ['error' => "NSE indices fetch failed (HTTP $code)"];
        $data = json_decode($resp, true);
        if (!$data || !isset($data['data'])) return ['error' => 'Unexpected NSE response'];
        return ['data' => $data['data']];
    }, NSE_INDICES_CACHE_TTL);
}

// ────────────────────────────────────────────────────────────────────────
// OPTIMIZATION (v9): PARALLEL HISTORICAL DATA FETCHING
// ────────────────────────────────────────────────────────────────────────
// The old analyze_stock_breakouts() made 3 sequential HTTP calls PER STOCK
// (daily candles, 5m candles, 15m candles). For a 44-stock index like
// NIFTY 50, that is up to 132 sequential round-trips on a cache miss --
// 15-25+ seconds. This version batches ALL candle requests for a whole
// list of stocks into ONE curl_multi wave, firing every request
// concurrently instead of one-after-another. Network latency dominates
// these calls, not compute, so running them in parallel turns a ~20s
// worst case into roughly 1-3 seconds.

class AngelBatchFetcher {
    private $angel;
    public function __construct($angel) { $this->angel = $angel; }

    // Fires many getCandles() requests concurrently via curl_multi.
    // $requests: array of ['key' => ..., 'symbolToken' => ..., 'interval' => ..., 'from' => ..., 'to' => ...]
    // Returns: ['key' => ['data' => [...]] or ['error' => ...], ...]
    public function fetchCandlesParallel($requests) {
        if (empty($requests)) return [];

        $mh = curl_multi_init();
        $handles = [];
        $headers = $this->angel->getAuthHeaders();

        foreach ($requests as $key => $req) {
            $body = json_encode([
                'exchange' => 'NSE',
                'symboltoken' => (string)$req['symbolToken'],
                'interval' => $req['interval'],
                'fromdate' => $req['from'],
                'todate' => $req['to'],
            ]);
            $ch = curl_init('https://apiconnect.angelone.in/rest/secure/angelbroking/historical/v1/getCandleData');
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            $decoded = $response ? json_decode($response, true) : null;
            if ($httpCode === 200 && $decoded && ($decoded['status'] ?? false)) {
                $results[$key] = ['data' => $decoded['data'] ?? []];
            } else {
                $msg = $decoded['message'] ?? "HTTP $httpCode";
                $results[$key] = ['error' => $msg];
            }
        }
        curl_multi_close($mh);
        return $results;
    }
}

// Runs breakout analysis for MULTIPLE stocks at once, using parallel HTTP
// requests instead of the old one-stock-at-a-time sequential loop.
// $stocks: array of ['symbol' => ..., 'symbolToken' => ..., 'lastPrice' => ..., 'totalTradedVolume' => ...]
// Returns the same array with breakout status fields merged in.
function analyze_stocks_breakouts_parallel($angel, $stocks) {
    if (empty($stocks)) return $stocks;

    // ensure session is valid ONCE before firing the parallel batch
    // (curl_multi requests can't individually trigger a relogin mid-flight)
    if (!$angel->ensureValidSession()) {
        foreach ($stocks as &$s) {
            $s['prevWeekStatus'] = 'N/A'; $s['curWeekStatus'] = 'N/A';
            $s['candle5mStatus'] = 'N/A'; $s['candle15mStatus'] = 'N/A';
            $s['pivotR1Status'] = 'N/A'; $s['volumeStatus'] = 'N/A';
        }
        unset($s);
        return $stocks;
    }

    $fetcher = new AngelBatchFetcher($angel);
    $today = date('Y-m-d H:i');
    $from16d = date('Y-m-d H:i', strtotime('-16 days'));
    $todayDate = date('Y-m-d');
    $fromIntraday = $todayDate . ' 09:15';

    // Build the full set of requests, but SKIP anything already cached
    $requests = [];
    $cachedDaily = [];
    $cached5m = [];
    $cached15m = [];

    foreach ($stocks as $s) {
        if (!isset($s['symbolToken']) || $s['symbolToken'] === '') continue;
        $sym = $s['symbol'];
        $token = $s['symbolToken'];

        $dailyFile = CACHE_DIR . '/' . SCHEMA_VERSION . '_daily_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json';
        if (file_exists($dailyFile) && (time() - filemtime($dailyFile) < HISTORICAL_DAILY_TTL)) {
            $cachedDaily[$sym] = json_decode(file_get_contents($dailyFile), true);
        } else {
            $requests['daily_' . $sym] = ['symbolToken' => $token, 'interval' => 'ONE_DAY', 'from' => $from16d, 'to' => $today];
        }

        $f5File = CACHE_DIR . '/' . SCHEMA_VERSION . '_5m_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json';
        if (file_exists($f5File) && (time() - filemtime($f5File) < HISTORICAL_INTRADAY_TTL)) {
            $cached5m[$sym] = json_decode(file_get_contents($f5File), true);
        } else {
            $requests['5m_' . $sym] = ['symbolToken' => $token, 'interval' => 'FIVE_MINUTE', 'from' => $fromIntraday, 'to' => $today];
        }

        $f15File = CACHE_DIR . '/' . SCHEMA_VERSION . '_15m_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json';
        if (file_exists($f15File) && (time() - filemtime($f15File) < HISTORICAL_INTRADAY_TTL)) {
            $cached15m[$sym] = json_decode(file_get_contents($f15File), true);
        } else {
            $requests['15m_' . $sym] = ['symbolToken' => $token, 'interval' => 'FIFTEEN_MINUTE', 'from' => $fromIntraday, 'to' => $today];
        }
    }

    // Fire ALL remaining (non-cached) requests in ONE parallel wave
    $results = !empty($requests) ? $fetcher->fetchCandlesParallel($requests) : [];

    // Process results per stock, computing the same breakout statuses
    // as before, but now the data-gathering step was parallelized.
    foreach ($stocks as &$s) {
        $sym = $s['symbol'];
        $ltp = is_numeric($s['lastPrice'] ?? null) ? (float)$s['lastPrice'] : 0;
        $todayVolume = isset($s['totalTradedVolume']) && is_numeric($s['totalTradedVolume']) ? (float)$s['totalTradedVolume'] : null;

        // Daily analysis (weekly H/L, pivot, volume avg)
        $dailyResult = $cachedDaily[$sym] ?? null;
        if (!$dailyResult && isset($results['daily_' . $sym])) {
            $raw = $results['daily_' . $sym];
            if (isset($raw['data'])) {
                $dailyResult = compute_daily_analysis_from_candles($raw['data']);
                file_put_contents(CACHE_DIR . '/' . SCHEMA_VERSION . '_daily_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json', json_encode($dailyResult));
            }
        }
        $d = $dailyResult['data'] ?? null;

        $s['prevWeekStatus'] = 'N/A'; $s['curWeekStatus'] = 'N/A';
        $s['pivotR1Status'] = 'N/A'; $s['volumeStatus'] = 'N/A';

        if ($d) {
            if (isset($d['prevWeekHigh']) && $d['prevWeekHigh'] !== null && $ltp > $d['prevWeekHigh']) $s['prevWeekStatus'] = 'Above PW High';
            elseif (isset($d['prevWeekLow']) && $d['prevWeekLow'] !== null && $ltp < $d['prevWeekLow']) $s['prevWeekStatus'] = 'Below PW Low';
            elseif (isset($d['prevWeekHigh']) && $d['prevWeekHigh'] !== null) $s['prevWeekStatus'] = 'Inside Range';

            if (isset($d['curWeekHigh']) && $d['curWeekHigh'] !== null && $ltp >= $d['curWeekHigh']) $s['curWeekStatus'] = 'New Week High';
            elseif (isset($d['curWeekLow']) && $d['curWeekLow'] !== null && $ltp <= $d['curWeekLow']) $s['curWeekStatus'] = 'New Week Low';
            elseif (isset($d['curWeekHigh']) && $d['curWeekHigh'] !== null) $s['curWeekStatus'] = 'Inside Range';

            if (isset($d['pivotR1']) && $d['pivotR1'] !== null) {
                $s['pivotR1Status'] = $ltp > $d['pivotR1'] ? 'Above R1' : 'Below R1';
            }

            if (isset($d['avgVolume10d']) && $d['avgVolume10d'] !== null && $d['avgVolume10d'] > 0 && $todayVolume !== null) {
                $multiple = $todayVolume / $d['avgVolume10d'];
                $s['volumeStatus'] = ($multiple >= 1.5 ? 'Yes' : 'No') . ' (' . number_format($multiple, 1) . 'x)';
            }
        }

        // 5-minute breakout
        $f5Result = $cached5m[$sym] ?? null;
        if (!$f5Result && isset($results['5m_' . $sym])) {
            $raw = $results['5m_' . $sym];
            if (isset($raw['data'])) {
                $f5Result = compute_intraday_last_closed($raw['data']);
                file_put_contents(CACHE_DIR . '/' . SCHEMA_VERSION . '_5m_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json', json_encode($f5Result));
            }
        }
        $f5 = $f5Result['data'] ?? null;
        $s['candle5mStatus'] = 'N/A';
        if ($f5 && isset($f5['high']) && isset($f5['low'])) {
            if ($ltp > $f5['high']) $s['candle5mStatus'] = '5m Breakout Up';
            elseif ($ltp < $f5['low']) $s['candle5mStatus'] = '5m Breakout Down';
            else $s['candle5mStatus'] = 'Inside 5m Range';
        }

        // 15-minute breakout
        $f15Result = $cached15m[$sym] ?? null;
        if (!$f15Result && isset($results['15m_' . $sym])) {
            $raw = $results['15m_' . $sym];
            if (isset($raw['data'])) {
                $f15Result = compute_intraday_last_closed($raw['data']);
                file_put_contents(CACHE_DIR . '/' . SCHEMA_VERSION . '_15m_analysis_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sym) . '.json', json_encode($f15Result));
            }
        }
        $f15 = $f15Result['data'] ?? null;
        $s['candle15mStatus'] = 'N/A';
        if ($f15 && isset($f15['high']) && isset($f15['low'])) {
            if ($ltp > $f15['high']) $s['candle15mStatus'] = '15m Breakout Up';
            elseif ($ltp < $f15['low']) $s['candle15mStatus'] = '15m Breakout Down';
            else $s['candle15mStatus'] = 'Inside 15m Range';
        }
    }
    unset($s);

    return $stocks;
}

// Extracted pure computation (no HTTP) so it can be reused by both the
// old sequential path and the new parallel path.
function compute_daily_analysis_from_candles($candles) {
    if (empty($candles)) return ['error' => 'No daily candle data returned'];

    $today = new DateTime('today');
    $curWeekStart = clone $today;
    $curWeekStart->modify('monday this week');
    $prevWeekStart = clone $curWeekStart;
    $prevWeekStart->modify('-7 days');
    $prevWeekEnd = clone $curWeekStart;
    $prevWeekEnd->modify('-1 day');

    $prevWeekHigh = null; $prevWeekLow = null;
    $curWeekHigh = null; $curWeekLow = null;
    $volumes = [];
    $prevDay = null;

    foreach ($candles as $c) {
        $ts = new DateTime(substr($c[0], 0, 10));
        $high = (float)$c[2]; $low = (float)$c[3]; $close = (float)$c[4]; $vol = (float)$c[5];

        if ($ts >= $prevWeekStart && $ts <= $prevWeekEnd) {
            $prevWeekHigh = $prevWeekHigh === null ? $high : max($prevWeekHigh, $high);
            $prevWeekLow = $prevWeekLow === null ? $low : min($prevWeekLow, $low);
        }
        if ($ts >= $curWeekStart && $ts < $today) {
            $curWeekHigh = $curWeekHigh === null ? $high : max($curWeekHigh, $high);
            $curWeekLow = $curWeekLow === null ? $low : min($curWeekLow, $low);
        }
        if ($ts < $today) {
            $volumes[] = $vol;
            $prevDay = ['high' => $high, 'low' => $low, 'close' => $close];
        }
    }

    $pivotR1 = null;
    if ($prevDay) {
        $p = ($prevDay['high'] + $prevDay['low'] + $prevDay['close']) / 3;
        $pivotR1 = (2 * $p) - $prevDay['low'];
    }

    $recentVolumes = array_slice($volumes, -10);
    $avgVolume10d = count($recentVolumes) > 0 ? array_sum($recentVolumes) / count($recentVolumes) : null;

    return [
        'data' => [
            'prevWeekHigh' => $prevWeekHigh, 'prevWeekLow' => $prevWeekLow,
            'curWeekHigh' => $curWeekHigh, 'curWeekLow' => $curWeekLow,
            'pivotR1' => $pivotR1, 'avgVolume10d' => $avgVolume10d,
        ]
    ];
}

function compute_intraday_last_closed($candles) {
    if (count($candles) < 2) return ['error' => 'Not enough intraday candles yet'];
    $lastClosed = $candles[count($candles) - 2];
    return ['data' => ['high' => (float)$lastClosed[2], 'low' => (float)$lastClosed[3]]];
}

// Sequential single-stock breakout analysis (still used for the main
// index table's per-row expand feature, where only one index at a time
// needs analysis and parallelizing a single small batch has less benefit
// but is kept simple/consistent with the rest of that code path).
function analyze_stock_breakouts($angel, $symbol, $symbolToken, $ltp, $todayVolume) {
    $dailyResult = cached_fetch('daily_analysis_' . $symbol, function () use ($angel, $symbolToken) {
        $to = date('Y-m-d H:i');
        $from = date('Y-m-d H:i', strtotime('-16 days'));
        $r = $angel->getCandles($symbolToken, 'ONE_DAY', $from, $to);
        if (isset($r['error'])) return $r;
        return compute_daily_analysis_from_candles($r['data']);
    }, HISTORICAL_DAILY_TTL);

    $fiveMinResult = cached_fetch('5m_analysis_' . $symbol, function () use ($angel, $symbolToken) {
        $today = date('Y-m-d');
        $from = $today . ' 09:15';
        $to = date('Y-m-d H:i');
        $r = $angel->getCandles($symbolToken, 'FIVE_MINUTE', $from, $to);
        if (isset($r['error'])) return $r;
        return compute_intraday_last_closed($r['data']);
    }, HISTORICAL_INTRADAY_TTL);

    $fifteenMinResult = cached_fetch('15m_analysis_' . $symbol, function () use ($angel, $symbolToken) {
        $today = date('Y-m-d');
        $from = $today . ' 09:15';
        $to = date('Y-m-d H:i');
        $r = $angel->getCandles($symbolToken, 'FIFTEEN_MINUTE', $from, $to);
        if (isset($r['error'])) return $r;
        return compute_intraday_last_closed($r['data']);
    }, HISTORICAL_INTRADAY_TTL);

    $d = $dailyResult['data'] ?? null;
    $out = [
        'prevWeekStatus' => 'N/A', 'curWeekStatus' => 'N/A',
        'candle5mStatus' => 'N/A', 'candle15mStatus' => 'N/A',
        'pivotR1Status' => 'N/A', 'volumeStatus' => 'N/A',
    ];

    if ($d) {
        if (isset($d['prevWeekHigh']) && $d['prevWeekHigh'] !== null && $ltp > $d['prevWeekHigh']) $out['prevWeekStatus'] = 'Above PW High';
        elseif (isset($d['prevWeekLow']) && $d['prevWeekLow'] !== null && $ltp < $d['prevWeekLow']) $out['prevWeekStatus'] = 'Below PW Low';
        elseif (isset($d['prevWeekHigh']) && $d['prevWeekHigh'] !== null) $out['prevWeekStatus'] = 'Inside Range';

        if (isset($d['curWeekHigh']) && $d['curWeekHigh'] !== null && $ltp >= $d['curWeekHigh']) $out['curWeekStatus'] = 'New Week High';
        elseif (isset($d['curWeekLow']) && $d['curWeekLow'] !== null && $ltp <= $d['curWeekLow']) $out['curWeekStatus'] = 'New Week Low';
        elseif (isset($d['curWeekHigh']) && $d['curWeekHigh'] !== null) $out['curWeekStatus'] = 'Inside Range';

        if (isset($d['pivotR1']) && $d['pivotR1'] !== null) {
            $out['pivotR1Status'] = $ltp > $d['pivotR1'] ? 'Above R1' : 'Below R1';
        }

        if (isset($d['avgVolume10d']) && $d['avgVolume10d'] !== null && $d['avgVolume10d'] > 0 && $todayVolume !== null) {
            $multiple = $todayVolume / $d['avgVolume10d'];
            $out['volumeStatus'] = ($multiple >= 1.5 ? 'Yes' : 'No') . ' (' . number_format($multiple, 1) . 'x)';
        }
    }

    $f5 = $fiveMinResult['data'] ?? null;
    if ($f5 && isset($f5['high']) && isset($f5['low'])) {
        if ($ltp > $f5['high']) $out['candle5mStatus'] = '5m Breakout Up';
        elseif ($ltp < $f5['low']) $out['candle5mStatus'] = '5m Breakout Down';
        else $out['candle5mStatus'] = 'Inside 5m Range';
    }

    $f15 = $fifteenMinResult['data'] ?? null;
    if ($f15 && isset($f15['high']) && isset($f15['low'])) {
        if ($ltp > $f15['high']) $out['candle15mStatus'] = '15m Breakout Up';
        elseif ($ltp < $f15['low']) $out['candle15mStatus'] = '15m Breakout Down';
        else $out['candle15mStatus'] = 'Inside 15m Range';
    }

    return $out;
}

// ════════════════════════════════════════════════════════════════════════
// Fetch full stock data (quote + optional breakout analysis) for an index
// ════════════════════════════════════════════════════════════════════════
function fetch_index_stocks_full($indexName, $withAnalysis = false) {
    global $SECTOR_CONSTITUENTS, $SYMBOL_TOKEN_MAP;
    if (!isset($SECTOR_CONSTITUENTS[$indexName])) return ['error' => 'Unknown index'];
    $symbols = $SECTOR_CONSTITUENTS[$indexName];

    $result = cached_fetch('angel_quotes_' . $indexName, function () use ($symbols) {
        $angel = new AngelSmartAPI(ANGEL_API_KEY, ANGEL_CLIENT_CODE, ANGEL_PIN, ANGEL_TOTP_SECRET);
        $quoteResp = $angel->getQuotes($symbols);
        if (isset($quoteResp['error'])) return $quoteResp;

        $rows = [];
        global $SYMBOL_TOKEN_MAP;
        foreach ($quoteResp['data'] as $d) {
            $token = (string)($d['symbolToken'] ?? $d['symboltoken'] ?? '');
            $foundSym = array_search($token, $SYMBOL_TOKEN_MAP);
            if ($foundSym === false) $foundSym = $d['tradingSymbol'] ?? $d['tradingsymbol'] ?? 'UNKNOWN';

            $rows[] = [
                'symbol'        => $foundSym,
                'symbolToken'   => $token,
                'lastPrice'     => $d['ltp'] ?? '-',
                'change'        => $d['netChange'] ?? '-',
                'pChange'       => $d['percentChange'] ?? 0,
                'open'          => $d['open'] ?? '-',
                'dayHigh'       => $d['high'] ?? '-',
                'dayLow'        => $d['low'] ?? '-',
                'previousClose' => $d['close'] ?? '-',
                'totalTradedVolume' => $d['tradeVolume'] ?? $d['volume'] ?? '-',
                'yearHigh'      => $d['52WeekHigh'] ?? $d['high52Week'] ?? '-',
                'yearLow'       => $d['52WeekLow'] ?? $d['low52Week'] ?? '-',
            ];
        }
        $out = ['data' => $rows];
        if (!empty($quoteResp['badTokens'])) $out['badTokens'] = $quoteResp['badTokens'];
        return $out;
    }, QUOTE_CACHE_TTL);

    if (isset($result['error']) && !isset($result['data'])) return $result;
    $rows = $result['data'] ?? [];

    if ($withAnalysis) {
        $angel = new AngelSmartAPI(ANGEL_API_KEY, ANGEL_CLIENT_CODE, ANGEL_PIN, ANGEL_TOTP_SECRET);
        // NEW (v9): parallel analysis for the WHOLE list at once, instead
        // of a sequential per-stock loop. Used here for the main index
        // table's expand feature (which can show up to "All" stocks).
        $rows = analyze_stocks_breakouts_parallel($angel, $rows);
    }

    return ['data' => $rows, 'stale' => $result['stale'] ?? false, 'badTokens' => $result['badTokens'] ?? []];
}

// UPDATED (v9): fetches quotes for the FULL index (needed to determine
// which 10 stocks are the actual top gainers/losers), but only runs the
// (expensive) breakout analysis on the FINAL top-10 slice, not all 44+
// stocks. This is the single biggest win for the gainer/loser sections.
function fetch_index_stocks_extreme($indexName, $limit = 10, $direction = 'top', $withAnalysis = false) {
    $result = fetch_index_stocks_full($indexName, false); // quotes only, no analysis yet
    if (isset($result['error']) && !isset($result['data'])) return $result;
    $rows = $result['data'] ?? [];
    usort($rows, function ($a, $b) { return (float)($b['pChange'] ?? 0) <=> (float)($a['pChange'] ?? 0); });
    if ($direction === 'bottom') $rows = array_reverse($rows);
    $topSlice = array_slice($rows, 0, $limit);

    if ($withAnalysis && !empty($topSlice)) {
        $angel = new AngelSmartAPI(ANGEL_API_KEY, ANGEL_CLIENT_CODE, ANGEL_PIN, ANGEL_TOTP_SECRET);
        $topSlice = analyze_stocks_breakouts_parallel($angel, $topSlice);
    }

    return ['data' => $topSlice, 'stale' => $result['stale'] ?? false];
}

// NEW (v9): resolves a section's "effective" index -- either the requested
// manual pin (if valid), or the auto-detected top gainer/loser from the
// visible-filtered index list.
function resolve_section_index($rows, $direction, $manualIndexName) {
    global $SECTOR_CONSTITUENTS;
    if ($manualIndexName && isset($SECTOR_CONSTITUENTS[$manualIndexName])) {
        foreach ($rows as $r) {
            if (($r['index'] ?? '') === $manualIndexName) return $r;
        }
    }
    return $direction === 'top' ? $rows[0] : $rows[count($rows) - 1];
}

// ════════════════════════════════════════════════════════════════════════
// AJAX / DIAGNOSTIC ENDPOINTS
// ════════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if ($_GET['ajax'] === 'test_login') {
        $angel = new AngelSmartAPI(ANGEL_API_KEY, ANGEL_CLIENT_CODE, ANGEL_PIN, ANGEL_TOTP_SECRET);
        echo json_encode($angel->testLogin(), JSON_PRETTY_PRINT);
        exit;
    }

    if ($_GET['ajax'] === 'test_tokens') {
        global $SYMBOL_TOKEN_MAP, $ALL_NEEDED_SYMBOLS;
        $resolved = array_keys($SYMBOL_TOKEN_MAP);
        $missing = array_diff(array_keys($ALL_NEEDED_SYMBOLS), $resolved);
        echo json_encode(['total_needed' => count($ALL_NEEDED_SYMBOLS), 'resolved_count' => count($resolved), 'missing_count' => count($missing), 'missing_symbols' => array_values($missing)], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_GET['ajax'] === 'validate_tokens') {
        global $SYMBOL_TOKEN_MAP;
        $angel = new AngelSmartAPI(ANGEL_API_KEY, ANGEL_CLIENT_CODE, ANGEL_PIN, ANGEL_TOTP_SECRET);
        $angel->forceRelogin();
        $valid = [];
        $invalid = [];
        foreach ($SYMBOL_TOKEN_MAP as $sym => $token) {
            $result = $angel->getSingleQuote($token, true);
            if (isset($result['data'])) {
                $valid[] = $sym;
            } else {
                $invalid[$sym] = ['token' => $token, 'error' => $result['error'] ?? 'unknown'];
            }
            usleep(150000);
        }
        echo json_encode(['valid_count' => count($valid), 'invalid_count' => count($invalid), 'invalid_symbols' => $invalid], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_GET['ajax'] === 'rebuild_tokens') {
        @unlink(FILTERED_MAP_CACHE);
        global $ALL_NEEDED_SYMBOLS;
        $map = build_symbol_token_map(array_keys($ALL_NEEDED_SYMBOLS));
        echo json_encode(['rebuilt' => true, 'resolved_count' => count($map)], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_GET['ajax'] === 'indices') {
        echo json_encode(fetch_nse_all_indices());
        exit;
    }

    if ($_GET['ajax'] === 'index_list') {
        global $SECTOR_CONSTITUENTS;
        echo json_encode(['indices' => array_keys($SECTOR_CONSTITUENTS)]);
        exit;
    }

    if ($_GET['ajax'] === 'stocks') {
        $indexName = trim($_GET['index'] ?? '');
        if (!$indexName || !isset($GLOBALS['SECTOR_CONSTITUENTS'][$indexName])) {
            echo json_encode(['error' => 'Unknown index: ' . $indexName]);
            exit;
        }
        $withAnalysis = ($_GET['analysis'] ?? '0') === '1';
        echo json_encode(fetch_index_stocks_full($indexName, $withAnalysis));
        exit;
    }

    // UPDATED (v9): now runs analysis ONLY on the final top-10 slice
    // (via fetch_index_stocks_extreme's $withAnalysis param), and both
    // gainer + loser fetches happen back-to-back using the now-cached
    // NSE indices response instead of two fresh NSE hits.
    if ($_GET['ajax'] === 'extreme_stocks') {
        $visibleParam = $_GET['visible'] ?? '';
        $visibleIndices = $visibleParam !== '' ? explode('|', $visibleParam) : null;

        $manualGainerIndex = trim($_GET['gainer_index'] ?? '');
        $manualLoserIndex = trim($_GET['loser_index'] ?? '');
        if ($manualGainerIndex === 'AUTO') $manualGainerIndex = '';
        if ($manualLoserIndex === 'AUTO') $manualLoserIndex = '';

        $idxResult = fetch_nse_all_indices();
        $rows = $idxResult['data'] ?? [];
        $rows = array_filter($rows, function ($r) use ($visibleIndices) {
            $name = $r['index'] ?? '';
            if (!isset($GLOBALS['SECTOR_CONSTITUENTS'][$name])) return false;
            if ($visibleIndices !== null && !in_array($name, $visibleIndices, true)) return false;
            return true;
        });
        $rows = array_values($rows);

        if (empty($rows)) { echo json_encode(['error' => 'No visible index data available']); exit; }

        usort($rows, function ($a, $b) { return (float)($b['percentChange'] ?? 0) <=> (float)($a['percentChange'] ?? 0); });

        $topGainerIndex = resolve_section_index($rows, 'top', $manualGainerIndex);
        $topLoserIndex = resolve_section_index($rows, 'bottom', $manualLoserIndex);
        $withAnalysis = ($_GET['analysis'] ?? '0') === '1';

        $gainerStocksResult = fetch_index_stocks_extreme($topGainerIndex['index'], 10, 'top', $withAnalysis);
        $gainerStocks = $gainerStocksResult['data'] ?? [];

        $loserStocksResult = fetch_index_stocks_extreme($topLoserIndex['index'], 10, 'bottom', $withAnalysis);
        $loserStocks = $loserStocksResult['data'] ?? [];

        echo json_encode([
            'gainer_index' => ['name' => $topGainerIndex['index'], 'percentChange' => (float)($topGainerIndex['percentChange'] ?? 0), 'last' => $topGainerIndex['last'] ?? '-'],
            'gainer_stocks' => $gainerStocks,
            'gainer_error' => $gainerStocksResult['error'] ?? null,
            'gainer_is_manual' => $manualGainerIndex !== '',
            'loser_index' => ['name' => $topLoserIndex['index'], 'percentChange' => (float)($topLoserIndex['percentChange'] ?? 0), 'last' => $topLoserIndex['last'] ?? '-'],
            'loser_stocks' => $loserStocks,
            'loser_error' => $loserStocksResult['error'] ?? null,
            'loser_is_manual' => $manualLoserIndex !== '',
        ]);
        exit;
    }
}

// ════════════════════════════════════════════════════════════════════════
// INITIAL PAGE LOAD
// ════════════════════════════════════════════════════════════════════════
$idxResult = fetch_nse_all_indices();
$indexRows = $idxResult['data'] ?? [];
$idxError = $idxResult['error'] ?? null;
$idxStale = $idxResult['stale'] ?? false;

$indexRows = array_filter($indexRows, function ($r) { return isset($GLOBALS['SECTOR_CONSTITUENTS'][$r['index'] ?? '']); });
$indexRows = array_values($indexRows);

usort($indexRows, function ($a, $b) {
    $pa = isset($a['percentChange']) ? (float)$a['percentChange'] : 0;
    $pb = isset($b['percentChange']) ? (float)$b['percentChange'] : 0;
    return $pb <=> $pa;
});

$topGainerIndexName = !empty($indexRows) ? $indexRows[0]['index'] : null;
$topLoserIndexName = !empty($indexRows) ? $indexRows[count($indexRows) - 1]['index'] : null;
$topGainerPct = !empty($indexRows) ? (float)($indexRows[0]['percentChange'] ?? 0) : 0;
$topLoserPct = !empty($indexRows) ? (float)($indexRows[count($indexRows) - 1]['percentChange'] ?? 0) : 0;

// Initial page load skips analysis (fast first paint); JS requests it
// shortly after via the AJAX refresh.
$initialGainerStocks = $topGainerIndexName ? fetch_index_stocks_extreme($topGainerIndexName, 10, 'top', false) : ['data' => []];
$initialLoserStocks = $topLoserIndexName ? fetch_index_stocks_extreme($topLoserIndexName, 10, 'bottom', false) : ['data' => []];

$allIndexNames = array_keys($SECTOR_CONSTITUENTS);
$lastUpdated = date('d-M-Y h:i:s A');
$topDefaultCount = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NSE Sector Dashboard (Angel One SmartAPI)</title>
<style>
  :root { --green:#16a34a; --red:#dc2626; --bg:#0f172a; --card:#1e293b; --border:#334155; --text:#e2e8f0; --muted:#94a3b8; --accent:#2563eb; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:'Segoe UI',Roboto,Arial,sans-serif; background:var(--bg); color:var(--text); padding:18px; font-size:13px; }
  .container { max-width:1500px; margin:0 auto; }
  h1 { font-size:20px; margin-bottom:4px; }
  .subtitle { color:var(--muted); font-size:12px; margin-bottom:14px; }
  .diag-link { display:inline-block; margin-bottom:10px; font-size:11px; color:#60a5fa; }

  .section-header { display:flex; align-items:center; gap:10px; cursor:pointer; user-select:none; background:var(--card); border:1px solid var(--border); border-radius:8px; padding:9px 14px; margin:16px 0 6px; }
  .section-header:hover { background:#233046; }
  .section-collapse-icon { font-size:11px; color:var(--muted); transition:transform .15s; display:inline-block; width:14px; }
  .section-header.collapsed .section-collapse-icon { transform:rotate(-90deg); }
  .section-header h2 { font-size:14px; margin:0; display:flex; align-items:center; gap:8px; flex:1; }
  .section-body { display:block; }
  .section-body.collapsed { display:none; }
  .index-select {
    background:#0f172a; color:var(--text); border:1px solid var(--border); border-radius:5px;
    padding:4px 8px; font-size:11.5px; cursor:pointer; margin-left:auto;
  }
  .index-select:hover { border-color:#64748b; }
  .manual-badge { font-size:10px; padding:1px 6px; border-radius:4px; background:rgba(96,165,250,0.15); color:#60a5fa; font-weight:700; }

  .toolbar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:10px; background:var(--card); padding:10px; border-radius:8px; border:1px solid var(--border); }
  .toolbar input[type="text"] { flex:1; min-width:160px; padding:6px 10px; border-radius:5px; border:1px solid var(--border); background:#0f172a; color:var(--text); font-size:12.5px; }
  .filter-btn { padding:6px 12px; border-radius:5px; border:1px solid var(--border); background:#0f172a; color:var(--text); cursor:pointer; font-size:12px; font-weight:600; transition:all .15s; }
  .filter-btn:hover { border-color:#64748b; }
  .filter-btn.active[data-filter="all"] { background:#334155; border-color:#64748b; }
  .filter-btn.active[data-filter="gainers"] { background:var(--green); border-color:var(--green); color:#fff; }
  .filter-btn.active[data-filter="losers"] { background:var(--red); border-color:var(--red); color:#fff; }
  .refresh-btn, .toggle-btn { padding:6px 12px; border-radius:5px; border:none; background:var(--accent); color:#fff; cursor:pointer; font-size:12px; font-weight:600; }
  .refresh-btn:hover, .toggle-btn:hover { background:#1d4ed8; }
  .toggle-btn.secondary { background:#334155; } .toggle-btn.secondary:hover { background:#475569; }
  .status { font-size:11px; color:var(--muted); margin-left:auto; }
  .warn { background:#7c2d12; color:#fed7aa; padding:8px 12px; border-radius:6px; margin-bottom:10px; font-size:12px; border:1px solid #9a3412; }

  .picker-panel { display:none; background:var(--card); border:1px solid var(--border); border-radius:8px; padding:10px; margin-bottom:10px; max-height:220px; overflow-y:auto; }
  .picker-panel.open { display:block; }
  .picker-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:4px 12px; }
  .picker-item { display:flex; align-items:center; gap:6px; font-size:12px; padding:2px 0; }
  .picker-item input { cursor:pointer; } .picker-item label { cursor:pointer; user-select:none; }
  .stock-limit-control { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--muted); }
  .stock-limit-control select { background:#0f172a; color:var(--text); border:1px solid var(--border); border-radius:4px; padding:3px 6px; font-size:11.5px; }

  table { width:100%; border-collapse:collapse; background:var(--card); border-radius:8px; overflow:hidden; border:1px solid var(--border); }
  thead th { background:#111827; padding:7px 9px; text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.3px; color:var(--muted); cursor:pointer; user-select:none; white-space:nowrap; position:sticky; top:0; }
  thead th:hover { color:#fff; }
  thead th.sorted-asc::after { content:" \25B2"; color:#60a5fa; }
  thead th.sorted-desc::after { content:" \25BC"; color:#60a5fa; }
  thead th.no-sort { cursor:default; }
  tbody td { padding:6px 9px; border-top:1px solid var(--border); font-size:12px; white-space:nowrap; }
  tbody tr.idx-row:hover { background:#263449; cursor:pointer; }
  .pos { color:var(--green); font-weight:600; }
  .neg { color:var(--red); font-weight:600; }
  .zero { color:var(--muted); font-weight:600; }
  .idx-name { font-weight:600; color:#f1f5f9; }
  .badge { display:inline-block; padding:1px 7px; border-radius:4px; font-size:10.5px; font-weight:700; }
  .badge.pos { background:rgba(22,163,74,0.15); }
  .badge.neg { background:rgba(220,38,38,0.15); }
  .badge.zero { background:rgba(148,163,184,0.15); }
  .ohl-cell { font-size:11px; line-height:1.5; color:var(--muted); }
  .ohl-cell b { color:var(--text); font-weight:600; }
  .status-tag { display:inline-block; padding:1px 6px; border-radius:4px; font-size:10.5px; font-weight:600; white-space:nowrap; }
  .status-tag.up { background:rgba(22,163,74,0.15); color:var(--green); }
  .status-tag.down { background:rgba(220,38,38,0.15); color:var(--red); }
  .status-tag.neutral { background:rgba(148,163,184,0.12); color:var(--muted); }
  .empty-row td { text-align:center; color:var(--muted); padding:22px; }

  .expand-toggle { display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:3px; background:#334155; color:#fff; font-size:10px; margin-right:6px; transition:transform .15s; }
  .idx-row.expanded .expand-toggle { transform:rotate(90deg); }

  .stock-detail-row td { padding:0; border-top:none; }
  .stock-detail-wrap { display:none; background:#16202f; padding:10px 10px 14px 34px; border-top:1px solid var(--border); overflow-x:auto; }
  .stock-detail-row.open .stock-detail-wrap { display:block; }
  .stock-detail-header { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
  .stock-detail-title { font-size:11.5px; color:var(--muted); font-weight:600; }
  .mini-table { width:100%; border-collapse:collapse; font-size:11px; }
  .mini-table th { text-align:left; padding:5px 8px; color:var(--muted); font-size:10px; text-transform:uppercase; border-bottom:1px solid var(--border); position:static; white-space:nowrap; }
  .mini-table td { padding:5px 8px; border-bottom:1px solid #1f2c3f; white-space:nowrap; }
  .mini-loading, .mini-empty { color:var(--muted); font-size:11.5px; padding:8px 0; }
  .mini-error { color:#fca5a5; font-size:11.5px; padding:8px 0; line-height:1.5; }
  .mini-retry-btn { margin-top:6px; padding:4px 10px; font-size:11px; border-radius:4px; border:1px solid var(--border); background:#1e293b; color:var(--text); cursor:pointer; }
  .mini-retry-btn:hover { background:#334155; }

  .extreme-badge { padding:2px 9px; border-radius:5px; font-size:11px; font-weight:700; }
  .extreme-badge.gainer { background:rgba(22,163,74,0.15); color:var(--green); }
  .extreme-badge.loser { background:rgba(220,38,38,0.15); color:var(--red); }
  .extreme-index-name { font-size:13px; font-weight:700; color:#f1f5f9; }
  .extreme-index-pct { font-size:12px; font-weight:700; }
  .analysis-loading { font-size:10.5px; color:var(--muted); font-style:italic; }
  @media (max-width:700px) { thead th, tbody td { padding:5px 6px; font-size:11px; } }
</style>
</head>
<body>
<div class="container">
  <h1>NSE Sector &amp; Stock Dashboard <span style="font-size:12px;color:var(--muted);font-weight:normal;">(Angel One SmartAPI)</span></h1>
  <div class="subtitle">Live quotes &amp; breakout analysis via Angel One SmartAPI &middot; Last updated: <span id="lastUpdated"></span> IST</div>
  <a class="diag-link" href="?ajax=test_login" target="_blank">Login diagnostic</a> &middot; <a class="diag-link" href="?ajax=test_tokens" target="_blank">Token coverage</a> &middot; <a class="diag-link" href="?ajax=validate_tokens" target="_blank">Validate tokens live</a> &middot; <a class="diag-link" href="?ajax=rebuild_tokens" target="_blank">Rebuild tokens</a>

  <?php if ($idxError): ?>
    <div class="warn"><strong>Index overview notice:</strong> <?= htmlspecialchars($idxError) ?> <?= $idxStale ? ' Showing cached index list.' : '' ?></div>
  <?php endif; ?>

  <div class="section-header" id="mainSectionHeader">
    <span class="section-collapse-icon">&#9660;</span>
    <h2>All Sector Indices</h2>
  </div>
  <div class="section-body" id="mainSectionBody">
    <div class="toolbar">
      <button class="filter-btn active" data-filter="all">All (<span id="countAll">0</span>)</button>
      <button class="filter-btn" data-filter="gainers">Gainers (<span id="countGainers">0</span>)</button>
      <button class="filter-btn" data-filter="losers">Losers (<span id="countLosers">0</span>)</button>
      <input type="text" id="searchBox" placeholder="Search index name...">
      <button class="toggle-btn secondary" id="pickerToggleBtn">Show/Hide Indices</button>
      <button class="refresh-btn" id="refreshBtn">Refresh Now</button>
      <span class="status" id="statusText"></span>
    </div>

    <div class="toolbar">
      <div class="stock-limit-control"> Stocks per expanded index:
        <select id="stockLimitSelect"><option value="5">5</option><option value="10" selected>10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option><option value="all">All</option></select>
      </div>
      <div class="stock-limit-control"><label><input type="checkbox" id="stockGainersOnly"> Gainers only</label></div>
      <div class="stock-limit-control"><label><input type="checkbox" id="stockLosersOnly"> Losers only</label></div>
      <div class="stock-limit-control"><label><input type="checkbox" id="showAnalysisCols" checked> Show breakout analysis columns</label></div>
    </div>

    <div class="picker-panel" id="pickerPanel">
      <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
        <span style="font-size:11.5px; color:var(--muted);">Indices checked here are also used to pick the Top Gainer / Top Loser below (when those are set to Auto).</span>
        <span><button class="toggle-btn secondary" id="selectAllBtn" style="padding:4px 9px; font-size:11px;">Select All</button><button class="toggle-btn secondary" id="selectTop10Btn" style="padding:4px 9px; font-size:11px;">Top 10 by % Change</button><button class="toggle-btn secondary" id="selectNoneBtn" style="padding:4px 9px; font-size:11px;">Clear All</button></span>
      </div>
      <div class="picker-grid" id="pickerGrid"></div>
    </div>

    <table id="indicesTable">
      <thead>
        <tr>
          <th class="no-sort" style="width:20px;"></th>
          <th data-key="index" data-type="string">Index Name</th>
          <th data-key="last" data-type="number">LTP</th>
          <th data-key="variation" data-type="number">Chg</th>
          <th data-key="percentChange" data-type="number" class="sorted-desc">% Chg</th>
          <th class="no-sort">O/H/L</th>
          <th data-key="previousClose" data-type="number">Prev Close</th>
          <th data-key="yearHigh" data-type="number">52W H/L</th>
        </tr>
      </thead>
      <tbody id="tableBody"><tr class="empty-row"><td colspan="8">Loading...</td></tr></tbody>
    </table>
  </div>

  <div class="section-header" id="gainerSectionHeader">
    <span class="section-collapse-icon">&#9660;</span>
    <h2>
      <span class="extreme-badge gainer">GAINER SECTION</span>
      <span class="extreme-index-name" id="gainerIndexName"><?= htmlspecialchars($topGainerIndexName ?? '-') ?></span>
      <span class="extreme-index-pct pos" id="gainerIndexPct"><?= ($topGainerPct >= 0 ? '+' : '') . number_format($topGainerPct, 2) ?>%</span>
      <span class="manual-badge" id="gainerManualBadge" style="display:none;">MANUAL</span>
    </h2>
    <select class="index-select" id="gainerIndexSelect" onclick="event.stopPropagation()">
      <option value="AUTO">Auto (Top Gainer)</option>
      <?php foreach ($allIndexNames as $name): ?>
        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="section-body" id="gainerSectionBody">
    <table id="gainerStocksTable">
      <thead><tr><th>Symbol</th><th>LTP</th><th>Chg</th><th>% Chg</th><th>O/H/L</th><th>Vol</th><th>PW H/L</th><th>CW H/L</th><th>5m BO</th><th>15m BO</th><th>Pivot R1</th><th>Vol &ge;1.5x</th></tr></thead>
      <tbody id="gainerStocksBody">
        <?php $gs = $initialGainerStocks['data'] ?? []; if (empty($gs)): ?>
          <tr class="empty-row"><td colspan="12">No data available.</td></tr>
        <?php else: foreach ($gs as $s): $pc = (float)($s['pChange'] ?? 0); $cls = $pc > 0 ? 'pos' : ($pc < 0 ? 'neg' : 'zero'); ?>
        <tr>
          <td class="idx-name"><?= htmlspecialchars($s['symbol'] ?? '-') ?></td>
          <td><?= htmlspecialchars($s['lastPrice'] ?? '-') ?></td>
          <td class="<?= $cls ?>"><?= htmlspecialchars($s['change'] ?? '-') ?></td>
          <td><span class="badge <?= $cls ?>"><?= number_format($pc, 2) ?>%</span></td>
          <td class="ohl-cell">O:<b><?= htmlspecialchars($s['open'] ?? '-') ?></b> H:<b><?= htmlspecialchars($s['dayHigh'] ?? '-') ?></b> L:<b><?= htmlspecialchars($s['dayLow'] ?? '-') ?></b></td>
          <td><?= htmlspecialchars($s['totalTradedVolume'] ?? '-') ?></td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="section-header" id="loserSectionHeader">
    <span class="section-collapse-icon">&#9660;</span>
    <h2>
      <span class="extreme-badge loser">LOSER SECTION</span>
      <span class="extreme-index-name" id="loserIndexName"><?= htmlspecialchars($topLoserIndexName ?? '-') ?></span>
      <span class="extreme-index-pct neg" id="loserIndexPct"><?= number_format($topLoserPct, 2) ?>%</span>
      <span class="manual-badge" id="loserManualBadge" style="display:none;">MANUAL</span>
    </h2>
    <select class="index-select" id="loserIndexSelect" onclick="event.stopPropagation()">
      <option value="AUTO">Auto (Top Loser)</option>
      <?php foreach ($allIndexNames as $name): ?>
        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="section-body" id="loserSectionBody">
    <table id="loserStocksTable">
      <thead><tr><th>Symbol</th><th>LTP</th><th>Chg</th><th>% Chg</th><th>O/H/L</th><th>Vol</th><th>PW H/L</th><th>CW H/L</th><th>5m BO</th><th>15m BO</th><th>Pivot R1</th><th>Vol &ge;1.5x</th></tr></thead>
      <tbody id="loserStocksBody">
        <?php $ls = $initialLoserStocks['data'] ?? []; if (empty($ls)): ?>
          <tr class="empty-row"><td colspan="12">No data available.</td></tr>
        <?php else: foreach ($ls as $s): $pc = (float)($s['pChange'] ?? 0); $cls = $pc > 0 ? 'pos' : ($pc < 0 ? 'neg' : 'zero'); ?>
        <tr>
          <td class="idx-name"><?= htmlspecialchars($s['symbol'] ?? '-') ?></td>
          <td><?= htmlspecialchars($s['lastPrice'] ?? '-') ?></td>
          <td class="<?= $cls ?>"><?= htmlspecialchars($s['change'] ?? '-') ?></td>
          <td><span class="badge <?= $cls ?>"><?= number_format($pc, 2) ?>%</span></td>
          <td class="ohl-cell">O:<b><?= htmlspecialchars($s['open'] ?? '-') ?></b> H:<b><?= htmlspecialchars($s['dayHigh'] ?? '-') ?></b> L:<b><?= htmlspecialchars($s['dayLow'] ?? '-') ?></b></td>
          <td><?= htmlspecialchars($s['totalTradedVolume'] ?? '-') ?></td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
          <td class="analysis-loading">loading...</td><td class="analysis-loading">loading...</td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const TOP_DEFAULT_COUNT = <?= (int)$topDefaultCount ?>;
const INITIAL_ROWS = <?= json_encode($indexRows) ?>;

const table = document.getElementById('indicesTable');
const tbody = document.getElementById('tableBody');
const searchBox = document.getElementById('searchBox');
const filterBtns = document.querySelectorAll('.filter-btn');
const pickerPanel = document.getElementById('pickerPanel');
const pickerGrid = document.getElementById('pickerGrid');
const pickerToggleBtn = document.getElementById('pickerToggleBtn');
const stockLimitSelect = document.getElementById('stockLimitSelect');
const stockGainersOnly = document.getElementById('stockGainersOnly');
const stockLosersOnly = document.getElementById('stockLosersOnly');
const showAnalysisCols = document.getElementById('showAnalysisCols');
const gainerIndexSelect = document.getElementById('gainerIndexSelect');
const loserIndexSelect = document.getElementById('loserIndexSelect');

let currentFilter = 'all', currentSortKey = 'percentChange', currentSortDir = 'desc';
let allIndicesData = [], visibleIndexNames = new Set(), expandedRows = {};

function setupCollapsible(headerId, bodyId) {
  const header = document.getElementById(headerId);
  const body = document.getElementById(bodyId);
  header.addEventListener('click', () => { header.classList.toggle('collapsed'); body.classList.toggle('collapsed'); });
}
setupCollapsible('mainSectionHeader', 'mainSectionBody');
setupCollapsible('gainerSectionHeader', 'gainerSectionBody');
setupCollapsible('loserSectionHeader', 'loserSectionBody');

gainerIndexSelect.addEventListener('change', () => refreshExtremeSections());
loserIndexSelect.addEventListener('change', () => refreshExtremeSections());

function fmtCell(v){ return v===null||v===undefined||v===''?'-':v; }
function ohlCell(open, high, low) { return `O:<b>${fmtCell(open)}</b> H:<b>${fmtCell(high)}</b> L:<b>${fmtCell(low)}</b>`; }

function statusTagClass(status) {
  if (!status) return 'neutral';
  const s = status.toLowerCase();
  if (s.includes('above') || s.includes('up') || s.includes('new week high') || s.startsWith('yes')) return 'up';
  if (s.includes('below') || s.includes('down') || s.includes('new week low') || s.startsWith('no ')) return 'down';
  return 'neutral';
}
function renderStatusTag(status) {
  if (!status || status === 'N/A') return '<span class="analysis-loading">n/a</span>';
  return `<span class="status-tag ${statusTagClass(status)}">${status}</span>`;
}

function sortByPercentDesc(rows){ return [...rows].sort((a,b)=>(parseFloat(b.percentChange)||0)-(parseFloat(a.percentChange)||0)); }
function initVisibleSet(rows){ visibleIndexNames = new Set(sortByPercentDesc(rows).slice(0,TOP_DEFAULT_COUNT).map(r=>r.index)); }
function buildPickerPanel(rows){
  const sorted = sortByPercentDesc(rows); pickerGrid.innerHTML = '';
  sorted.forEach(r=>{ const pc=parseFloat(r.percentChange)||0, cls=pc>0?'pos':(pc<0?'neg':'zero'), id='chk_'+r.index.replace(/[^a-zA-Z0-9]/g,'_');
    const div=document.createElement('div'); div.className='picker-item'; div.innerHTML=`<input type="checkbox" id="${id}" data-index="${r.index}" ${visibleIndexNames.has(r.index)?'checked':''}><label for="${id}">${r.index} <span class="${cls}">(${pc>=0?'+':''}${pc.toFixed(2)}%)</span></label>`; pickerGrid.appendChild(div);
  });
  pickerGrid.querySelectorAll('input[type=checkbox]').forEach(cb=>cb.addEventListener('change',()=>{ cb.checked?visibleIndexNames.add(cb.dataset.index):visibleIndexNames.delete(cb.dataset.index); renderTable(allIndicesData); refreshExtremeSections(); }));
}
pickerToggleBtn.addEventListener('click',()=>pickerPanel.classList.toggle('open'));
document.getElementById('selectAllBtn').addEventListener('click',()=>{ visibleIndexNames=new Set(allIndicesData.map(r=>r.index)); buildPickerPanel(allIndicesData); renderTable(allIndicesData); refreshExtremeSections(); });
document.getElementById('selectNoneBtn').addEventListener('click',()=>{ visibleIndexNames=new Set(); buildPickerPanel(allIndicesData); renderTable(allIndicesData); refreshExtremeSections(); });
document.getElementById('selectTop10Btn').addEventListener('click',()=>{ initVisibleSet(allIndicesData); buildPickerPanel(allIndicesData); renderTable(allIndicesData); refreshExtremeSections(); });

function updateCounts(){ const rows=[...tbody.querySelectorAll('tr.idx-row')]; document.getElementById('countAll').textContent=rows.length; document.getElementById('countGainers').textContent=rows.filter(r=>parseFloat(r.dataset.percent)>0).length; document.getElementById('countLosers').textContent=rows.filter(r=>parseFloat(r.dataset.percent)<0).length; }
function matchesFilterAndSearch(row){ const pct=parseFloat(row.dataset.percent), name=row.dataset.name||'', term=searchBox.value.trim().toLowerCase(); let showByFilter=true; if(currentFilter==='gainers')showByFilter=pct>0; else if(currentFilter==='losers')showByFilter=pct<0; const showBySearch=term===''||name.includes(term), showByVis=visibleIndexNames.has(row.dataset.fullname); return showByFilter&&showBySearch&&showByVis; }
function applyFilterAndSearch(){ [...tbody.querySelectorAll('tr.idx-row')].forEach(row=>{ const show=matchesFilterAndSearch(row); row.style.display=show?'':'none'; const det=row.nextElementSibling; if(det&&det.classList.contains('stock-detail-row')) det.style.display=(show&&det.classList.contains('open'))?'':'none'; }); updateCounts(); }
filterBtns.forEach(btn=>btn.addEventListener('click',()=>{ filterBtns.forEach(b=>b.classList.remove('active')); btn.classList.add('active'); currentFilter=btn.dataset.filter; applyFilterAndSearch(); }));
searchBox.addEventListener('input',applyFilterAndSearch);

function renderTable(rows){ allIndicesData=rows; tbody.innerHTML=''; if(!rows||!rows.length){ tbody.innerHTML='<tr class="empty-row"><td colspan="8">No data available.</td></tr>'; return; }
  rows.forEach(r=>{ const pc=parseFloat(r.percentChange)||0, cls=pc>0?'pos':(pc<0?'neg':'zero'); const tr=document.createElement('tr'); tr.className='idx-row'; tr.dataset.percent=pc; tr.dataset.name=(r.index||'').toLowerCase(); tr.dataset.fullname=r.index;
    tr.innerHTML=`<td><span class="expand-toggle">&#9656;</span></td><td class="idx-name">${fmtCell(r.index)}</td><td>${fmtCell(r.last)}</td><td class="${cls}">${fmtCell(r.variation)}</td><td><span class="badge ${cls}">${pc.toFixed(2)}%</span></td><td class="ohl-cell">${ohlCell(r.open,r.dayHigh,r.dayLow)}</td><td>${fmtCell(r.previousClose)}</td><td class="ohl-cell">H:<b>${fmtCell(r.yearHigh)}</b> L:<b>${fmtCell(r.yearLow)}</b></td>`;
    const det=document.createElement('tr'); det.className='stock-detail-row'; det.innerHTML=`<td colspan="8"><div class="stock-detail-wrap"><div class="mini-loading">Loading stocks...</div></div></td>`; det.style.display='none';
    tr.addEventListener('click',()=>toggleExpand(tr,det,r.index));
    tbody.appendChild(tr); tbody.appendChild(det);
  });
  applyFilterAndSearch();
}

function getStockLimit(){ const v=stockLimitSelect.value; return v==='all'?Infinity:parseInt(v,10); }
function buildMiniTableHeader() {
  const showAnalysis = showAnalysisCols.checked;
  let cols = '<th>Symbol</th><th>LTP</th><th>Chg</th><th>% Chg</th><th>O/H/L</th><th>Vol</th>';
  if (showAnalysis) cols += '<th>PW H/L</th><th>CW H/L</th><th>5m BO</th><th>15m BO</th><th>Pivot R1</th><th>Vol &ge;1.5x</th>';
  return cols;
}

function renderStockDetail(detTr, idxName){
  const state=expandedRows[idxName], wrap=detTr.querySelector('.stock-detail-wrap');
  if(!state){ wrap.innerHTML='<div class="mini-loading">Loading stocks...</div>'; return; }
  if(state.error){ wrap.innerHTML=`<div class="mini-error">Could not load stocks: ${state.error}</div><button class="mini-retry-btn" data-retry="${idxName}">Retry</button>`; wrap.querySelector('.mini-retry-btn').addEventListener('click',()=>{ delete expandedRows[idxName]; loadStocks(detTr,idxName); }); return; }
  if(!state.stocks||!state.stocks.length){ wrap.innerHTML='<div class="mini-empty">No stock-level data available.</div>'; return; }

  let stocks=state.stocks.filter(s=>s.symbol&&s.symbol.toUpperCase()!==idxName.replace(/ /g,'').toUpperCase());
  if(stockGainersOnly.checked)stocks=stocks.filter(s=>(parseFloat(s.pChange)||0)>0);
  if(stockLosersOnly.checked)stocks=stocks.filter(s=>(parseFloat(s.pChange)||0)<0);
  stocks.sort((a,b)=>(parseFloat(b.pChange)||0)-(parseFloat(a.pChange)||0));
  const limit=getStockLimit(), shown=stocks.slice(0,limit);
  if(!shown.length){ wrap.innerHTML=`<div class="stock-detail-header"><span class="stock-detail-title">${idxName} constituents</span></div><div class="mini-empty">No stocks match filter.</div>`; return; }

  const showAnalysis = showAnalysisCols.checked;
  let html=`<div class="stock-detail-header"><span class="stock-detail-title">${idxName} constituents (showing ${shown.length} of ${stocks.length})</span></div><table class="mini-table"><thead><tr>${buildMiniTableHeader()}</tr></thead><tbody>`;
  shown.forEach(s=>{
    const pc=parseFloat(s.pChange)||0, cls=pc>0?'pos':(pc<0?'neg':'zero');
    html+=`<tr><td class="idx-name">${fmtCell(s.symbol)}</td><td>${fmtCell(s.lastPrice)}</td><td class="${cls}">${fmtCell(s.change)}</td><td><span class="badge ${cls}">${pc.toFixed(2)}%</span></td><td class="ohl-cell">${ohlCell(s.open,s.dayHigh,s.dayLow)}</td><td>${fmtCell(s.totalTradedVolume)}</td>`;
    if (showAnalysis) html += `<td>${renderStatusTag(s.prevWeekStatus)}</td><td>${renderStatusTag(s.curWeekStatus)}</td><td>${renderStatusTag(s.candle5mStatus)}</td><td>${renderStatusTag(s.candle15mStatus)}</td><td>${renderStatusTag(s.pivotR1Status)}</td><td>${renderStatusTag(s.volumeStatus)}</td>`;
    html += '</tr>';
  });
  html+='</tbody></table>'; wrap.innerHTML=html;
}

function loadStocks(detTr, idxName, withAnalysis){
  const wrap=detTr.querySelector('.stock-detail-wrap');
  wrap.innerHTML='<div class="mini-loading">Loading stocks' + (withAnalysis ? ' + breakout analysis' : '') + '...</div>';
  const analysisParam = withAnalysis ? '&analysis=1' : '';
  fetch('?ajax=stocks&index='+encodeURIComponent(idxName)+analysisParam)
    .then(r=>r.json())
    .then(j=>{ if(j.error&&!j.data) expandedRows[idxName]={stocks:null,error:j.error}; else expandedRows[idxName]={stocks:j.data||[],error:null}; renderStockDetail(detTr,idxName); })
    .catch(e=>{ expandedRows[idxName]={stocks:null,error:String(e)}; renderStockDetail(detTr,idxName); });
}

function toggleExpand(tr,detTr,idxName){
  const isOpen=detTr.classList.contains('open');
  if(isOpen){ detTr.classList.remove('open'); detTr.style.display='none'; tr.classList.remove('expanded'); return; }
  tr.classList.add('expanded'); detTr.classList.add('open'); detTr.style.display='';
  if(expandedRows[idxName]){ renderStockDetail(detTr,idxName); return; }
  loadStocks(detTr,idxName, showAnalysisCols.checked);
}

stockLimitSelect.addEventListener('change',()=>{ document.querySelectorAll('.stock-detail-row.open').forEach(det=>{ renderStockDetail(det,det.previousElementSibling.dataset.fullname); }); });
stockGainersOnly.addEventListener('change',()=>{ if(stockGainersOnly.checked)stockLosersOnly.checked=false; document.querySelectorAll('.stock-detail-row.open').forEach(det=>renderStockDetail(det,det.previousElementSibling.dataset.fullname)); });
stockLosersOnly.addEventListener('change',()=>{ if(stockLosersOnly.checked)stockGainersOnly.checked=false; document.querySelectorAll('.stock-detail-row.open').forEach(det=>renderStockDetail(det,det.previousElementSibling.dataset.fullname)); });
showAnalysisCols.addEventListener('change',()=>{
  document.querySelectorAll('.stock-detail-row.open').forEach(det=>{
    const idxName = det.previousElementSibling.dataset.fullname;
    delete expandedRows[idxName];
    loadStocks(det, idxName, showAnalysisCols.checked);
  });
});

function sortTable(key,type){
  const rows=[...tbody.querySelectorAll('tr.idx-row')];
  if(currentSortKey===key) currentSortDir=currentSortDir==='asc'?'desc':'asc'; else{ currentSortKey=key; currentSortDir='desc'; }
  const headers=[...table.querySelectorAll('thead th')], colIndex=headers.findIndex(th=>th.dataset.key===key);
  const pairs=rows.map(r=>({row:r,det:r.nextElementSibling}));
  pairs.sort((pa,pb)=>{ const a=pa.row,b=pb.row; let va=a.children[colIndex].innerText.replace('%','').replace(/,/g,'').trim(), vb=b.children[colIndex].innerText.replace('%','').replace(/,/g,'').trim(); if(type==='number'){ va=parseFloat(va)||0; vb=parseFloat(vb)||0; return currentSortDir==='asc'?va-vb:vb-va; } return currentSortDir==='asc'?va.localeCompare(vb):vb.localeCompare(va); });
  pairs.forEach(p=>{ tbody.appendChild(p.row); if(p.det&&p.det.classList.contains('stock-detail-row')) tbody.appendChild(p.det); });
  headers.forEach(th=>th.classList.remove('sorted-asc','sorted-desc'));
  table.querySelector(`thead th[data-key="${key}"]`).classList.add(currentSortDir==='asc'?'sorted-asc':'sorted-desc');
}
table.querySelectorAll('thead th[data-key]').forEach(th=>th.addEventListener('click',()=>sortTable(th.dataset.key,th.dataset.type)));

function renderExtremeStocksTable(tbodyId, stocks) {
  const tb = document.getElementById(tbodyId);
  tb.innerHTML = '';
  if (!stocks || stocks.length === 0) { tb.innerHTML = '<tr class="empty-row"><td colspan="12">No data available.</td></tr>'; return; }
  stocks.forEach(s => {
    const pc = parseFloat(s.pChange) || 0;
    const cls = pc > 0 ? 'pos' : (pc < 0 ? 'neg' : 'zero');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="idx-name">${fmtCell(s.symbol)}</td><td>${fmtCell(s.lastPrice)}</td><td class="${cls}">${fmtCell(s.change)}</td>
      <td><span class="badge ${cls}">${pc.toFixed(2)}%</span></td><td class="ohl-cell">${ohlCell(s.open,s.dayHigh,s.dayLow)}</td><td>${fmtCell(s.totalTradedVolume)}</td>
      <td>${renderStatusTag(s.prevWeekStatus)}</td><td>${renderStatusTag(s.curWeekStatus)}</td><td>${renderStatusTag(s.candle5mStatus)}</td>
      <td>${renderStatusTag(s.candle15mStatus)}</td><td>${renderStatusTag(s.pivotR1Status)}</td><td>${renderStatusTag(s.volumeStatus)}</td>
    `;
    tb.appendChild(tr);
  });
}

function refreshExtremeSections() {
  const visibleParam = Array.from(visibleIndexNames).join('|');
  const gainerSel = gainerIndexSelect.value;
  const loserSel = loserIndexSelect.value;
  fetch('?ajax=extreme_stocks&visible=' + encodeURIComponent(visibleParam) +
        '&gainer_index=' + encodeURIComponent(gainerSel) +
        '&loser_index=' + encodeURIComponent(loserSel) +
        '&analysis=1')
    .then(r => r.json())
    .then(j => {
      if (j.error) return;
      document.getElementById('gainerIndexName').textContent = j.gainer_index.name;
      document.getElementById('gainerIndexPct').textContent = (j.gainer_index.percentChange >= 0 ? '+' : '') + j.gainer_index.percentChange.toFixed(2) + '%';
      document.getElementById('gainerManualBadge').style.display = j.gainer_is_manual ? 'inline-block' : 'none';
      renderExtremeStocksTable('gainerStocksBody', j.gainer_stocks);

      document.getElementById('loserIndexName').textContent = j.loser_index.name;
      document.getElementById('loserIndexPct').textContent = j.loser_index.percentChange.toFixed(2) + '%';
      document.getElementById('loserManualBadge').style.display = j.loser_is_manual ? 'inline-block' : 'none';
      renderExtremeStocksTable('loserStocksBody', j.loser_stocks);
    })
    .catch(() => {});
}

function refreshData(){
  document.getElementById('statusText').textContent='Refreshing...';
  fetch('?ajax=indices').then(r=>r.json()).then(j=>{
    if(j.error&&!j.data){ document.getElementById('statusText').textContent='Error: '+j.error; return; }
    let rows=sortByPercentDesc(j.data || []);
    renderTable(rows); buildPickerPanel(rows);
    document.getElementById('lastUpdated').textContent=new Date().toLocaleString('en-IN',{hour12:true});
    currentSortKey='percentChange'; currentSortDir='desc';
    document.getElementById('statusText').textContent=j.stale?'Showing cached data (live fetch failed)':'Updated '+new Date().toLocaleTimeString('en-IN');
  }).catch(e=>{ document.getElementById('statusText').textContent='Refresh failed: '+e; });
}
document.getElementById('refreshBtn').addEventListener('click',()=>{ refreshData(); refreshExtremeSections(); });

(function init(){
  const rows=sortByPercentDesc(INITIAL_ROWS);
  initVisibleSet(rows);
  renderTable(rows);
  buildPickerPanel(rows);
  document.getElementById('lastUpdated').textContent = new Date().toLocaleString('en-IN', { hour12: true });
  setTimeout(refreshExtremeSections, 800);
  setInterval(() => { refreshData(); refreshExtremeSections(); }, 15000);
})();
</script>
</body>
</html>