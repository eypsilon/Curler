<?php

use Many\Http\Curler;

/**
 * Custom callbacks. You can call multiple callbacks on each
 * request, just chain them in the desired order. Each callback
 * will use the resulting content from the previous one.
 *
 * For demo purposes only
 *
 * @param mixed $r contains response content from curl exec
 */

// Standard output format
function curlConv($r, $t) {
    if (Curler::isJson($r)) {
        $j = \json_decode($r);
        $j->{$t}[] = \sprintf('%s done, %s', $t, Curler::dateMicroSeconds(null, 'H:i:s.u'));
        $j = \json_encode($j, JSON_PRETTY_PRINT);
    }
    return $j ?? $r;
}

function curlCallback($r) {
    return curlConv($r, 'custom_callback');
}

function curlCallbackTwo($r) {
    return curlConv($r, 'callback_two');
}

function curlCallbackThree($r) {
    return curlConv($r, 'callback_three');
}

function appendString($r, $s) {
    return \is_string($r) ? $r . $s : $r;
}

/**
 * Example Callback Class
 */
class CallbackClass
{
    function init($r) {
        return curlConv($r, 'callback_class_init');
    }
    static function run($r) {
        return curlConv($r, 'callback_class_run');
    }
}
