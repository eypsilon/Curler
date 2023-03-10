<?php declare(strict_types=1);

namespace Many\Http;

use Many\Traits\AppCallback;
use DateTime;
use DateTimeZone;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_replace;
use function base64_encode;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function date_default_timezone_get;
use function file_get_contents;
use function floor;
use function func_get_args;
use function get_defined_constants;
use function getallheaders;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_last_error;
use function log;
use function microtime;
use function parse_str;
use function pow;
use function preg_grep;
use function round;
use function sprintf;
use function strtoupper;
use function substr;
use function trim;
use const CURLAUTH_ANY;
use const CURLAUTH_BASIC;
use const CURLAUTH_BEARER;
use const CURLAUTH_DIGEST;
use const CURLINFO_CONTENT_TYPE;
use const CURLINFO_HEADER_OUT;
use const CURLINFO_SCHEME;
use const CURLINFO_RESPONSE_CODE;
use const CURLINFO_SIZE_DOWNLOAD;
use const CURLOPT_AUTOREFERER;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPAUTH;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_HTTP_VERSION;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;
use const CURLOPT_USERPWD;
use const CURLOPT_USERNAME;
use const CURLOPT_VERBOSE;
use const CURLOPT_XOAUTH2_BEARER;
use const CURL_HTTP_VERSION_NONE;
use const JSON_ERROR_NONE;

/**
 * CURL Unchained
 *
 * @see https://www.php.net/manual/de/function.curl-setopt.php
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://opensource.org/licenses/mit-license.html MIT License
 */
class Curler
{
    use AppCallback;

    /**
     * @var int $curlCallCount counts usage
     * @var array $curlCallTrace collects each call, if enabled
     * @var array $curlConfig
     */
    protected static $curlCallCount = 0,
        $curlCallTrace = [],
        $curlConfig = [
            'response_only' => false,
            'curl_trace' => false,
            'exceptions' => false,
            'meta' => false,
            'request_info' => false,
            'curl_info' => false,
            'default_url' => null,
            'date_format' => 'Y-m-d H:i:s.u', # DATE_ATOM
            'image_to_data' => [], // ['image/jpeg',]
            'default_header' => [],
            'default_options' => [],
            'default_callback' => [],
        ];

    /**
     * @var array $tmpCurlConf
     * @var array $curlSet Class default Settings
     */
    protected $tmpCurlConf = [],
        $curlSet = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
            CURLOPT_URL => null,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [],
            CURLINFO_HEADER_OUT => false,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => null, // (string) http_build_query($str)
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 90,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_USERAGENT => 'Many/Curler',
        ];

    /**
     * @return void
     */
    function __construct()
    {
        if ($ce = self::$curlConfig['exceptions'])
            $this->enableExceptions = $ce;
        if ($dh = self::$curlConfig['default_header'])
            $this->header($dh);
        if ($do = self::$curlConfig['default_options'])
            $this->setOptArray($do);
        if ($dc = self::$curlConfig['default_callback'])
            $this->callback(...$dc);
    }

    /**
     * Chainables
     */

    /**
     * Set/overwrite single CURL option
     *
     * @param int $option
     * @param any $val
     * @return self
     */
    function setOpt(int $option, $val): self
    {
        $this->curlSet[$option] = $val;
        return $this;
    }

    /**
     * Set/overwrite default CURL options array
     *
     * @param array $options
     * @return self
     */
    function setOptArray(array $options): self
    {
        $this->curlSet = array_replace($this->curlSet, $options);
        return $this;
    }

    /**
     * Set HTTP Method
     *
     * @param string $v method
     * @return self
     */
    function method(string $v): self
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($v));
        return $this;
    }

    /**
     * Set target URL (endpoint)
     *
     * @param string $url
     * @return self
     */
    function url(string $url): self
    {
        $defUrl = (true === ($this->tmpCurlConf['disable_default_url'] ?? false))
            ? null : self::$curlConfig['default_url'] ;
        $this->setOpt(CURLOPT_URL, $defUrl . $url);
        return $this;
    }

    /**
     * Disable default URL for instance
     *
     * @param bool $s set
     * @return self
     */
    function disableDefaultUrl(bool $s=true): self
    {
        $this->tmpCurlConf['disable_default_url'] = $s;
        return $this;
    }

    /**
     * Alias for disableDefaultHost
     *
     * @return self
     */
    function disableDefaultHost(): self
    {
        return $this->disableDefaultUrl(...func_get_args());
    }

    /**
     * Set header
     *
     * @param array $header [KEY => VALUE]
     * @param bool $overwrite existing header, if true
     * @param array $r temp var
     * @return self
     */
    function header(array $header, bool $overwrite=false, array $r=[]): self
    {
        if (!$overwrite AND $this->curlSet[CURLOPT_HTTPHEADER])
            $header = array_merge($this->curlSet[CURLOPT_HTTPHEADER], $header);
        foreach($header as $k => $v)
            $r[] = sprintf('%s%s', is_string($k) ? "{$k}: " : null, is_array($v) ? implode(' ', $v) : $v);
        $this->setOpt(CURLOPT_HTTPHEADER, $r);
        return $this;
    }

    /**
     * Chainable Auth methods
     */

    /**
     * Set CURL http auth method, eg. CURLAUTH_BASIC
     *
     * @param int $auth
     * @return self
     */
    function httpAuth(int $auth): self
    {
        $this->setOpt(CURLOPT_HTTPAUTH, $auth);
        return $this;
    }

    /**
     * Set User + Password, expected: "User:Password"
     *
     * @param string $u User or 'User:Password'
     * @param string|null $p Password or null
     * @return self
     */
    function userPwd(string $u, string $p=null): self
    {
        $this->setOpt(CURLOPT_USERPWD, $u . ($p ? ":{$p}" : null));
        return $this;
    }

    /**
     * HTTP authentication for CURLAUTH_ANY
     *
     * @param string User or 'User:Password'
     * @param string|null Password or null
     * @return self
     */
    function authAny(): self
    {
        $this->httpAuth(CURLAUTH_ANY);
        $this->userPwd(...func_get_args());
        return $this;
    }

    /**
     * HTTP authentication for CURLAUTH_BASIC
     *
     * @param string User or 'User:Password'
     * @param string|null Password or null
     * @return self
     */
    function authBasic(): self
    {
        $this->httpAuth(CURLAUTH_BASIC);
        $this->userPwd(...func_get_args());
        return $this;
    }

    /**
     * HTTP authentication for CURLAUTH_DIGEST
     *
     * @param string User or 'User:Password'
     * @param string|null Password or null
     * @return self
     */
    function authDigest(): self
    {
        $this->httpAuth(CURLAUTH_DIGEST);
        $this->userPwd(...func_get_args());
        return $this;
    }

    /**
     * HTTP authentication for CURLAUTH_BEARER
     *
     * @param string $t token
     * @param string|null $u Username For IMAP, LDAP, POP3 and SMTP
     * @return self
     */
    function authBearer(string $t, string $u=null): self
    {
        $this->httpAuth(CURLAUTH_BEARER);
        $this->setOpt(CURLOPT_XOAUTH2_BEARER, $t);
        if ($u)
            $this->setOpt(CURLOPT_USERNAME, $u);
        return $this;
    }

    /**
     * Chainable REQUEST methods
     */

    /**
     * add POST data, sets method() to POST internally
     *
     * @param array|string $p http_build_query($str)
     * @return self
     */
    function post($p): self
    {
        $this->method('post');
        $this->postFields($p);
        $this->setOpt(CURLOPT_POST, true);
        return $this;
    }

    /**
     * Post fields
     *
     * @param array|string $f fields
     * @return self
     */
    function postFields($f): self
    {
        $this->setOpt(CURLOPT_POSTFIELDS, is_array($f) ? http_build_query($f) : $f);
        return $this;
    }

    /**
     * Chain helper
     */

    /**
     * Returns final CURL exec response only
     *
     * @param bool $s set
     * @return self
     */
    function responseOnly(bool $s=true): self
    {
        $this->tmpCurlConf['response_only_temp'] = $s;
        return $this;
    }

    /**
     * json_decode Shorty
     *
     * @return self
     */
    function jsonDecode(): self
    {
        $this->callbackIf(['\\' . __CLASS__ . '::isJson'], 'json_decode', ...func_get_args());
        return $this;
    }

    /**
     * json_encode Shorty
     *
     * @return self
     */
    function jsonEncode(): self
    {
        $this->callbackIf(['\\' . __CLASS__ . '::isJsonObj'], 'json_encode', ...func_get_args());
        return $this;
    }

    /**
     * Convert special chars to HTML entities
     *
     * @return self
     */
    function htmlChars(): self
    {
        $this->callbackIf(['is_string'], 'htmlspecialchars', ...func_get_args());
        return $this;
    }

    /**
     * Alias for $this->htmlChars()
     *
     * @return self
     */
    function htmlSpecialChars(): self
    {
        return $this->htmlChars(...func_get_args());
    }

    /**
     * Get CURL generated infos
     *
     * @param boolean $s
     * @return self
     */
    function curlInfo(bool $s=true): self
    {
        $this->tmpCurlConf['curl_info'] = $s;
        return $this;
    }

    /**
     * Request infos
     *
     * @param bool $rInfo getAllHeaders(), $_SERVER
     * @return self
     */
    function requestInfo(bool $s=true): self
    {
        $this->tmpCurlConf['request_info'] = $s;
        return $this;
    }

    /**
     * final execs & aliases
     */

    /**
     * Alias for ->exec(), Sets request method to DELETE
     *
     * @return mixed
     */
    final function delete()
    {
        $this->method('delete');
        return $this->exec(...func_get_args());
    }

    /**
     * Alias for ->exec()
     *
     * @return mixed
     */
    final function get()
    {
        return $this->exec(...func_get_args());
    }

    /**
     * Alias for ->exec(), Sets request method to PATCH
     *
     * @return mixed
     */
    final function patch()
    {
        $this->method('patch');
        return $this->exec(...func_get_args());
    }

    /**
     * Alias for ->exec(), Sets request method to PUT
     *
     * @return mixed
     */
    final function put()
    {
        $this->method('put');
        return $this->exec(...func_get_args());
    }

    /**
     * exec CURL
     *
     * @param string $url
     * @param array $setOpts alternate way to set CURL options on the fly
     * @return mixed
     */
    final function exec(string $url=null, array $setOpts=[])
    {
        if ($setOpts)
            $this->setOptArray($setOpts);
        if ($url)
            $this->url($url);

        $exec = $this->filterOutput($this->curlExec()); // img to string converter
        $error = ($exec['error'] OR $exec['http_code'] !== 200) ? $exec['http_code'] : null;

        if ($this->callback)
            $exec['exec'] = $this->execCallback($exec['exec']);

        return true === ($this->tmpCurlConf['response_only_temp'] ?? self::$curlConfig['response_only'] ?? false)
            ? $exec['exec']
            : [
                'url' => $this->curlSet[CURLOPT_URL],
                'http_code' => $exec['http_code'],
                'status' => isset($error) ? 'error' : 'ok',
                'method' => $this->curlSet[CURLOPT_CUSTOMREQUEST],
                'content_type' => $exec['type'],
                'meta' => $exec['meta'] ?? 'disabled',
                'error' => isset($error) ? trim("{$error} {$exec['error']}") : null,
                'response' => $exec['exec'],
            ];
    }

    /**
     * Static methods
     */

    /**
     * Set/overwrite default configs
     *
     * @param array $v
     * @return void
     */
    static function setConfig(array $v): void
    {
        self::$curlConfig = array_replace(self::$curlConfig, $v);
        return;
    }

    /**
     * Get configs
     *
     * @param string $k config key name to get specific values
     * @return array|int|string|null
     */
    static function getConfig(string $k=null)
    {
        return $k ? (self::$curlConfig[$k] ?? null) : self::$curlConfig;
    }

    /**
     * Get CURL request counter
     *
     * @return int
     */
    static function getCurlCount(): int
    {
        return self::$curlCallCount;
    }

    /**
     * CURL get trace, if enabled
     *
     * @return array|null
     */
    static function getCurlTrace(): ?array
    {
        return self::$curlCallTrace;
    }

    /**
     * Get body content in requests
     *
     * @param bool $parsed returns content parsed as array
     * @param null $put tmp var
     * @return mixed
     */
    static function getBodyContent(bool $parsed=false, $put=null)
    {
        $body = file_get_contents('php://input');
        if ($parsed AND $body)
            parse_str($body, $put);
        return $put ?? $body;
    }

    /**
     * Get CURL Options
     *
     * @param bool $getAll available constants
     * @param array $r temp var
     * @return array
     */
    static function getOptions(bool $getAll=false, array $r=[]): array
    {
        if (!$constants = (get_defined_constants(true)['curl'] ?? null))
            return ['error' => 'CURL constants not found'];
        $curlOptKeys = preg_grep('/^CURLOPT_/', array_keys($constants));
        $curlOptLookup = array_flip(array_intersect_key($constants, array_flip($curlOptKeys)));
        foreach((new self)->curlSet as $num => $val)
            $r[$curlOptLookup[$num] ?? $num] = $val;
        if ($getAll)
            $r['all'] = $constants;
        return $r;
    }

    /**
     * Check if value is a valid JSON string. If strict, the checked value has to be an valid object.
     *
     * @param any $v value to check
     * @param bool $s strict mode, value must be convertible to object, default true
     * @return bool
     */
    static function isJson($v, bool $s=true): bool
    {
        if (is_string($v)) {
            $o = json_decode($v);
            $i = json_last_error() === JSON_ERROR_NONE;
        } else $o = $i = false;
        return $s ? ($i AND is_object($o)) : $i;
    }

    /**
     * Check if value is a valid JSON Object (array or object).
     *
     * @param any $v value to check
     * @return bool
     */
    static function isJsonObj($v): bool
    {
        return (is_array($v) OR is_object($v));
    }

    /**
     * Get datetime with microseconds
     *
     * @param string|null $d datetime if null, returns current date()
     * @param string $f format
     * @param int $s substr
     * @return string
     */
    static function dateMicroSeconds(string $d=null, string $f=null, int $s=-2): string
    {
        $d = $d ?? microtime(true);
        $f = is_string($f) ? $f : self::$curlConfig['date_format'];
        return substr(DateTime::createFromFormat('U.u', (string) $d)
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format($f), 0, $s);
    }

    /**
     * Returns the difference between two Dates
     *
     * @param string $date1 date start
     * @param string $date2 date end
     * @param string $f format
     * @param int $s substr
     * @return mixed
     */
    static function dateMicroDiff($d1, $d2, string $f='%S.%f', int $s=-2)
    {
        return substr((new DateTime($d1))->diff(new DateTime($d2))->format($f), 0, $s);
    }

    /**
     * Readable Bytes
     *
     * @param int $b bytes
     * @param int $n number format decimal, numbers after comma
     * @param array $u unit file sizes
     * @return void
     */
    static function readableBytes($b, $n=2, $u=['B','KB','MB','GB','TB','PB'])
    {

        return $b > 0 ? round($b/pow(1024, ($i=floor(log($b, 1024)))), $n) . ' ' . $u[$i] : 0;
    }

    /**
     * Protected methods
     */

    /**
     * Filter Output, alter images to: "data:image/png;base64,CONTENT"
     *
     * @param array $exec
     * @return array
     */
    protected function filterOutput(array $exec): array
    {
        if ($exec['exec'] AND in_array($exec['type'], self::$curlConfig['image_to_data']))
            $exec['exec'] = sprintf('data:%s;base64,%s', $exec['type'], base64_encode($exec['exec']));
        return $exec;
    }

    /**
     * Get Request info
     *
     * @param bool
     * @return array|null
     */
    protected function getRequestInfo(): ?array
    {
        return ($this->tmpCurlConf['request_info'] ?? self::$curlConfig['request_info']) ? [
            'request_header' => getallheaders(),
            'server' => $_SERVER,
        ] : null ;
    }

    /**
     * Execute CURL
     *
     * @return array
     */
    protected function curlExec(): array
    {
        curl_setopt_array($c = curl_init(), $this->curlSet);

        $r = [
            'exec' => curl_exec($c),
            'error' => curl_error($c),
            'type' => curl_getinfo($c, CURLINFO_CONTENT_TYPE),
            'http_code' => curl_getinfo($c, CURLINFO_RESPONSE_CODE),
        ];

        $infoScheme = defined('CURLINFO_SCHEME') ? CURLINFO_SCHEME : 1048625;

        if (self::$curlConfig['meta'])
            $r['meta'] = [
                'scheme' => curl_getinfo($c, $infoScheme),
                'size' => static::readableBytes(curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD)),
                'datetime' => static::dateMicroSeconds(),
            ];
        if ($ri = $this->getRequestInfo())
            $r['meta']['request_info'] = $ri;
        if ($this->tmpCurlConf['curl_info'] ?? self::$curlConfig['curl_info'])
            $r['meta']['curl_info'] = curl_getinfo($c);

        curl_close($c);

        ++self::$curlCallCount;
        if (self::$curlConfig['curl_trace'])
            $this->setCurlTrace($r['http_code']);

        return $r;
    }

    /**
     * Set CURL trace array
     *
     * @return void
     */
    protected function setCurlTrace($rCode)
    {
        $traceDate = static::dateMicroSeconds();
        $countTrace = sprintf("%02d", self::$curlCallCount);
        self::$curlCallTrace["{$countTrace}__{$traceDate}"] = sprintf('%s %s %s'
            , $this->curlSet[CURLOPT_CUSTOMREQUEST]
            , $rCode
            , $this->curlSet[CURLOPT_URL]
        );
        return;
    }

}
