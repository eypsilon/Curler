<?php error_reporting(E_ALL);

/**
 * For demo purposes only
 *
 * php -S localhost:8000
 * http://localhost:8000
 */

use Many\Http\Curler;
use Many\Exception\AppCallbackException;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/callback.functions.php';

/** Get file content in a browser */
if ('self' === ($_GET['check'] ?? null))
{
    exit(highlight_file(__FILE__, true));
}

/** @var string (optional) default URL */
$defaultUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb';

/** @var array Images to test "img to string" converter, will use $defaultUrl */
$imgList = [
    '/8/80/Andaman.jpg/200px-Andaman.jpg',
    '/2/21/Aurora_and_sunset.jpg/200px-Aurora_and_sunset.jpg',
    '/9/93/Sunset_at_Egipura%2C_Bangalore.jpg/200px-Sunset_at_Egipura%2C_Bangalore.jpg',
    '/3/3e/After_The_Storm_(3594761019).jpg/200px-After_The_Storm_(3594761019).jpg',
];

/** @var string File with json content */
$jsonFile = 'https://raw.githubusercontent.com/eypsilon/Curler/master/public/www.example.json';

/** @var array some URLs to load */
$loadUrls = [
    'http://example.com/',
    'https://raw.githubusercontent.com/eypsilon/browser-reload/master/LICENSE',
    'https://github.com/eypsilon/MycroBench',
];


/**
 * @var array Set Config
 */
Curler::setConfig([
    'response_only'  => false,
    'curl_trace'     => true, // (default) false
    'exceptions'     => true, // (default) false
    'meta'           => false,
    'request_info'   => false,
    'curl_info'      => false,
    'default_url'    => null,
    'date_format'    => 'Y-m-d H:i:s.u',
    'image_to_data'  => [], // 'image/jpeg', ...
    'default_header' => [
        'x-app-curler' => 'Many/Curler',
        'Content-Type' => 'application/json',
    ],
    'default_options' => [
        CURLOPT_USERAGENT => 'AwesomeUser',
    ],
    'default_callback' => [],
]);

/**
 * @var Curler extended example with multiple callbacks (order matters)
 */
try {
    $curled['curler_info'] = '<b>Loads the content of a JSON file and executes some callback functions in a row</b> <small>(7 to be specific)</small>';
    $curled['curler'] = (new Curler)
        // ->disableDefaultUrl()
        // ->responseOnly()
        // ->requestInfo()
        // ->curlInfo()

        // callbacks, see "./callback.functions.php"
        ->callback('curlCallback')
        ->callback('curlCallbackTwo')
        ->callback('curlCallbackThree')

        // using a class
        ->callback('CallbackClass::run')           // class::run()
        ->callback('CallbackClass::class', 'init') // (new class)->init()
        ->callback(CallbackClass::class, 'init')   // (new class)->init()

        // using a closure
        ->callback(function($response) {
            // do stuff with $response here and
            return curlConv($response, 'closure_callback');
        })

        // pre validate content
        // ->callback('appendString', ' ...')
        ->jsonDecode()
        ->jsonEncode()
        ->callbackIf(['is_string'], 'curlConv', 'done_too')

        // callback shorties
        ->jsonDecode()                             // ->callback('json_decode')
        ->jsonEncode(JSON_PRETTY_PRINT)            // ->callback('json_encode', JSON_PRETTY_PRINT)
        ->htmlChars()                              // ->htmlSpecialChars()

        ->exec($jsonFile);
} catch(AppCallbackException $e) {
    $curled['exception']['curler'] = $e->getMessage();
}


/**
 * @var array Load multiple URLs
 */
$curled['load_urls_info'] = '<b>Loads a handful of URLs and truncates the response</b>';
foreach($loadUrls as $i => $url) {
    try {
        $host = parse_url((string) $url, PHP_URL_HOST);
        $curled['load_urls'][] = (new Curler)
            ->htmlChars()
            ->callback('trim')
            ->callback('substr', 0, 20)
            ->callback('appendString', '… ' . Curler::dateMicroSeconds())
            ->get($url);
    } catch(AppCallbackException $e) {
        $curled['exception']['load_urls'][$host] = $e->getMessage();
    }
}


/**
 * @var Curler Load images, change configs to get datetime for images, set default URL and image converter
 */
Curler::setConfig([
    'meta' => true,
    'default_url' => $defaultUrl,
    'image_to_data' => ['image/jpeg', /* 'image/png', */],
]);

$c = new Curler;
$printImages = [];
$curled['loaded_images_info'] = '<b>The images displayed above, the responses are truncated</b>';

foreach($imgList as $img)
{
    try {
        if ($src = $c->get($img))
        {
            $printImages[] = sprintf('<img src="%s" alt="%s" style="height:130px" />', $src['response'], $img);
            $src['response'] = substr($src['response'], 0, 35) . ' …';
            $curled['loaded_images'][] = $src;
        }
    } catch(AppCallbackException $e) {
        $curled['exception']['images'][$img] = $e->getMessage();
    }
}

$url = $_GET['url'] ?? null;
$setUrl = is_string($url) ? $url : 'https://mycro-bench.vercel.app/';

/**
 * @Template\Engin © 1994 eypsilon
 */
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Many\Curler | local-dev-many-title</title>
<meta name="description" content="Many/Curler Example Page" />
<style>
header {text-align: center;}
header h1 {margin: 0;}
header p {margin: 5px 0;}
fieldset {margin: 15px 0; padding: 10px; display: flex; background: #f5f5f5; border-color: #fff;}
input {width: 100%; border-width: 1px 0;}
[type=button] {pointer-events: none; color: #999;}
input, button {display: block; padding: 6px 10px;}
pre {margin: 1.5em 0; white-space: pre-wrap;}
hr {margin: 1em 0;}
</style>
</head>
<body>
<header>
    <h1><a href="/">Many\Curler</a></h1>
    <p>another one curls the dust</p>
</header>
<form action="" method="get">
    <fieldset>
        <button type="button">test</button>
        <input type="text" name="url" value="<?= htmlspecialchars($setUrl) ?>" placeholder="Enter a URL" />
        <button type="submit">Get</button>
    </fieldset>
</form>
<?php

// live curler
if (is_string($url) AND false === filter_var($url, FILTER_VALIDATE_URL))
{
    printf('<h2>Many\Curler\Live</h2><pre>FILTER_VALIDATE_URL_FAILED %s</pre><hr />'
        , htmlspecialchars($url)
    );
} elseif (is_string($url) AND false !== filter_var($url, FILTER_VALIDATE_URL)) {
    printf('<h2>Many\Curler\Live</h2><pre>%s</pre><hr />',
        (new Curler)
            ->disableDefaultUrl()
            ->responseOnly()
            ->htmlChars()
            ->get($url)
    );
}

// out
printf('%s<h2>Some requests</h2><pre>%s</pre>'
    , implode(PHP_EOL, $printImages) // print images
    , print_r(array_merge($curled, [
        'getCurlCount' => Curler::getCurlCount(),
        'getCurlTrace' => array_map(
            function($e) use($defaultUrl) {
                return str_replace($defaultUrl, '{replaced_url} …', $e);
            }, Curler::getCurlTrace()
        ),
        'getConfig'    => Curler::getConfig(),
        'getOptions'   => Curler::getOptions(),
    ]), true)
);

// responder/receiver example
printf('<hr /><h3>Responder/Receiver Example</h3> %s <hr />
    <p><a href="?check=self">Check current file</a></p>'
    , highlight_file(__DIR__ . '/app.responder.receiver.php', true)
);

// advanced benchmarks
printf("<hr /><pre>start: %s\nend:   %s\ndiff:  %s\nmem:   %s\npeak:  %s</pre>"
    , $started = Curler::dateMicroSeconds($_SERVER['REQUEST_TIME_FLOAT'], 'H:i:s.u')
    , $current = Curler::dateMicroSeconds(null, 'H:i:s.u') // current microtime(true)
    , Curler::dateMicroDiff($started, $current, '%s.%f')
    , Curler::readableBytes(memory_get_usage())
    , Curler::readableBytes(memory_get_peak_usage())
);

?>
</body>
</html>
