# MANY/CURLER | Another one CURLs the dust

This class is written to simplify the use of CURL in PHP using chained methods and more rememberable names.

I needed a simple way to explore the Shopware API (v6.*) via http and the Bearer token authentication scheme, but haven't found, what i was looking for to do it, so i ended up with this one. It does nothing new at all, nothing exciting, just simple Requests to URLs and Endpoints like you would expect, but a bit prettier than CURL itself.

See [./tests/curler](./tests/curler/) directory for Examples.

```terminal
composer require eypsilon/curler
```

```php
use Many\Http\Curler;

/**
 * @var array Simple get
 */
$c = (new Curler)->get('https://example.com/');
print $c['response'];

/**
 * @var string with Auth creds
 */
$xc = (new Curler)
    ->authAny('user', 'pass')
    ->post(
        json_encode([
            'lorem_ipsum' => 'Dolor Sit Amet',
        ])
    )
    ->responseOnly() // returns $xc['response']
    ->exec('/api/usr');
print $xc;
```


## Usage

```php
/**
 * @var array Set Configs and Defaults
 */
Curler::setConfig([
    'response_only' => false, // returns the response content as is
    'curl_trace'    => false, // track requests, (GET) Curl::getCurlTrace()
    'exceptions'    => false, // enable Exceptions
    'meta'          => false, // enable meta data
    'request_info'  => false, // [getallheaders(), $_SERVER] in 'meta'
    'curl_info'     => false, // CURL generated infos about the request in 'meta'

    // Default URL, will be prefixed to each request URL, disable with: ->disableDefaultUrl()
    'default_url'   => null,  // 'https://example.com'
    'date_format'   => 'Y-m-d H:i:s.u',

    // Convert images to valid data strings (no defaults defined)
    'image_to_data' => [], // 'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/svg+xml', ...

    // Send default headers (no defaults defined)
    'default_header' => [], // 'x-powered-by' => 'Many/Curler',

    // Add/overwrite CURL default options, see Curl::getOptions()
    'default_options' => [
        CURLINFO_HEADER_OUT => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_URL => null,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [],
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
    ],

    // Set a callback function, that gets fired after first curl_exec(), eg. for logging
    'default_callback' => [], // => ['print_r', true],
]);

/**
 * @var mixed Extended example with available methods
 */
$curl = (new Curler)

    ->method('post')            // Set http method, default is "get"
    ->url('http://example.com') // Set target url or path, same as ->exec(URL)

    /**
     * Misc */
    ->disableDefaultUrl()       // If default_url is setted, disable for this instance
    ->responseOnly()            // Returns CURL response content only
    ->requestInfo()             // getallheaders(), $_SERVER
    ->curlInfo()                // curl_getinfo()


    /**
     * Set CURL Options */
    ->setOpt(CURLOPT_ENCODING, 'zip')
    ->setOpt(CURLOPT_USERAGENT, 'Many/Curler')
    ->setOpt(CURLOPT_AUTOREFERER, false)

    // array alternate
    ->setOptArray([
        CURLOPT_ENCODING => 'zip',
        CURLOPT_USERAGENT => 'Many/Curler',
        CURLOPT_AUTOREFERER => false,
    ])


    /**
     * Header */
    ->header([
        'Authentication' => 'Many pw.2345',      // 'Authentication: Many pw.2345'
        'Authentication' => ['Many', 'pw.2345'], // 'Authentication: Many pw.2345'
    ])


    /**
     * HTTP Auth */
    ->httpAuth(CURLAUTH_BASIC) // protection type
    ->userPwd('user', 'pass')  // or ('user:pass')

    // CURLAUTH_ANY (.htaccess, uses basic or digest)
    ->authAny('user', 'pass')

    // CURLAUTH_BASIC
    ->authBasic('user', 'pass')

    // CURLAUTH_DIGEST
    ->authDigest('user', 'pass')

    // CURLAUTH_BEARER (?user optional, not .htaccess)
    ->authBearer('token.lr.72.m', '?user')


    /**
     * Sets CURLOPT_CUSTOMREQUEST=POST and CURLOPT_POST=true internally.
     * Arrays will be converted to strings using http_build_query() */
    ->post([
        'lorem_ipsum' => 'dolor sit amet',
    ])

    /**
     * Set postfields avoiding internally setted stuff to send data as body
     * content, eg PUT. This class uses http_build_query(), if an array is
     * given. Convert to any string format that fits your needs */
    ->postFields(
        json_encode([
            'lorem_ipsum' => 'dolor sit amet',
        ])
    )


    /**
     * Callback, run multiple callbacks through chaining in the given order.
     * Each callback will use the resulting content from the previous one. */
    ->callback('json_decode', true)             // any PHP internal function
    ->callback('curlCallback')                  // custom function

    // Custom class
    ->callback('CallbackClass::run')            // (static) class::run()
    ->callback('CallbackClass::class', 'init')  // (new class)->init() # init() could be any method
    ->callback(CallbackClass::class, 'init')    // (new class)->init()

    // Closure
    ->callback(function($response) {
        // Do stuff with $response here and
        return $response;
    })

    // Pre validated callbacks, set simple validator functions to pre-validate the content.
    // If Exceptions are enabled, throws Exceptions on fail, otherwise function gets ignored
    ->callbackIf(['\Many\Http\Curler::isJson'], 'json_decode', true)
    ->callbackIf(['\Many\Http\Curler::isJsonObj'], 'json_encode')
    ->callbackIf(['is_string'], 'json_decode')

    // Shorthands
    ->jsonDecode(true)                          // Shorty for json_decode()
    ->jsonEncode(JSON_PRETTY_PRINT)             // Shorty for json_encode()
    ->htmlChars()                               // Shorty for htmlspecialchars()
    ->htmlSpecialChars()                        // Shorty for ->htmlChars()


    /**
     * Final execs, getter */
    ->exec() // OR
    ->exec('/api/endpoint', [
        CURLOPT_USERAGENT => 'AwesomeCurler', // set any CURL option here
    ])

    /**
     * Alternate exec aliases. They all sets their name as REQUEST_METHOD
     * internally. You can use ->postFields(json_encode([])) to send content
     * additionally in the body. */
    ->delete() // OR
    ->delete('/api/endpoint', [/* ... */])

    ->get()
    ->get('/api/endpoint', [/* ... */])

    ->patch()
    ->patch('/api/endpoint', [/* ... */])

    ->put()
    ->put('/api/endpoint', [/* ... */])
```


### Loading images

To convert images automatically to their valid string representations, set expected image types in `setConfig` (no defaults defined)

```php
Curler::setConfig([
    'image_to_data' => [
        'image/jpeg',
        // 'image/png',
        // 'image/webp',
        // 'image/gif',
        // 'image/x-icon',
        // 'image/svg+xml',
        // ...
    ]
]);

$img = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/21/Aurora_and_sunset.jpg/200px-Aurora_and_sunset.jpg';

if ($src = (new Curler)->responseOnly()->get($img))
    printf('<img src="%s" alt="%s" />', $src, $img);
```


#### Exceptions

Catch AppCallbackException. The Class sends also additional http_header, if any errors occures.

```php
use Many\Exception\AppCallbackException;

Curler::setConfig([
    'exceptions' => true,
]);

try {
    $get = (new Curler)
        ->callback('theImpossibleFunction')
        ->get('/app/endpoint');
} catch(AppCallbackException $e) {
    $failed = $e->getMessage();
}
```


#### Track requested URLs

To track CURL requests, set `curl_trace` to true, before doing any request.

```php
Curler::setConfig([
    'curl_trace' => true,
]);

/** @var array Get all CURL requests with timestamps in an array */
$curlGetTrace = Curler::getCurlTrace();
```


#### Misc methods

```php
/** @var int Get total amount of requests done so far */
$curlsTotal = Curler::getCurlCount();

/** @var array Get Config */
$curlGetConfig = Curler::getConfig();

/** @var array Get curl_setopt(), (true) all available CURL constants */
$curlGetOptions = Curler::getOptions(true);

/** @var mixed Get body content, (true) parsed to array */
$curlGetBodyContent = Curler::getBodyContent(true);

/** @var bool Check if val is JSON format */
$isJson = Curler::isJson('{}', true); // (true) strict mode

/** @var bool Check if val is valid JSON Object (is_array or is_object) */
$isJsonObj = Curler::isJsonObj([]);

/** @var string Readable Bytes */
$memUsage = Curler::readableBytes(memory_get_usage());

/** @var string Datetime with microseconds (microtime(true), $_SERVER['REQUEST_TIME_FLOAT']) */
$microDate = Curler::dateMicroSeconds(null, 'Y-m-d H:i:s.u');

/** @var string Get difference between two Dates with microseconds */
$microDateDiff = Curler::dateMicroDiff(
    Curler::dateMicroSeconds($_SERVER['REQUEST_TIME_FLOAT']), // script started (microtime(true))
    Curler::dateMicroSeconds(),                               // current microtime(true)
    '%s.%f'
);
```
