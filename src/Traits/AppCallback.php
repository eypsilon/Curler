<?php declare(strict_types=1);

namespace Many\Traits;

use Many\Exception\AppCallbackException;
use ReflectionClass;
use function array_values;
use function call_user_func;
use function crc32;
use function explode;
use function func_get_args;
use function function_exists;
use function header;
use function is_callable;
use function is_string;
use function method_exists;
use function microtime;
use function sprintf;

/**
 * Callback trait, chained callbacks
 *
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */
trait AppCallback
{

    /**
     * @var array temp callback storage
     */
    private $callback = [];

    /**
     * @var bool Enable Exceptions
     */
    private $enableExceptions = false;

    /**
     * Enables chained callbacks for the using Class
     *
     * @param string function name
     * @param array parameters
     * @return self
     * @throws AppCallbackException
     */
    function callback(): self
    {
        $args = func_get_args();
        $fn = $args[0] ?? [];
        unset($args[0]);
        if ($fn)
            $this->callback[] = ['function' => $fn, 'params' => $args];
        elseif (empty($fn))
            $this->callbackError('Function is empty', __FUNCTION__);
        return $this;
    }

    /**
     * Execute the callback. Check in the using class, if $this->callback is empty, if not,
     * call execCallback() with the final content as first parameter, before returning content
     *
     * $content = "Random Class is done";
     * if ($this->callback)
     *     $content = $this->execCallback($content);
     * return $content;
     *
     * @param any $content
     * @return mixed
     */
    protected function execCallback($content)
    {
        if ($this->callback)
            foreach($this->callback as $cb)
                if ($cb['function'] ?? null)
                    $content = $this->execIfCallable($cb['function'], array_values($cb['params'] ?? []), $content);
        return $content;
    }

    /**
     * Run callback function or method, if callable
     *
     * @param mixed $fn
     * @param array $params
     * @param any $content
     * @return mixed|null
     * @throws AppCallbackException
     */
    protected function execIfCallable($fn, array $params, $content)
    {
        $exec = function($cls=null, $mtd=null) use($fn, $params, $content) {
            if ($cls AND $mtd)
                return call_user_func([new $cls, $mtd], $content, ...$params);
            return call_user_func($fn, $content, ...$params);
        };
        if (is_callable($fn)) {
            return $exec();
        } elseif (is_string($fn)) {
            if (function_exists($fn))
                return $exec();
            $xpl = explode('::', $fn);
            $par1 = $params[0] ?? null;
            if (isset($xpl[1]) OR $par1) {
                if ($par1 AND !isset($xpl[1]))
                    $xpl[1] = 'class';
                if ($xpl[1] === 'class' AND $par1)
                    if (method_exists((string) $xpl[0], (string) $par1))
                        return $exec($xpl[0], $par1);
            }
        }
        $this->callbackError("Failed to execute {$fn}", __FUNCTION__);
        return $content;
    }

    /**
     * Handles errors, sets a http header and an Exception
     *
     * @param string $msg
     * @return void
     * @throws AppCallbackException if callbacks fails to execute
     */
    protected function callbackError(string $msg, string $mtd): void
    {
        header(sprintf('x-%s-error-%s: %s'
            , $class = (new ReflectionClass(__TRAIT__))->getShortName()
            , crc32(microtime())
            , $msg
        ));
        if ($this->enableExceptions)
            throw new AppCallbackException(sprintf('%s — %s::%s', $msg, $class, $mtd));
        return;
    }

}
