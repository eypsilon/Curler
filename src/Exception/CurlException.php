<?php declare(strict_types=1);

namespace Many\Exception;

use Exception;

/**
 * Curl Exception
 *
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
final class CurlException extends Exception
{
    /** @return Exception */
    function __construct(string $message=null, Int $code=406)
    {
        return parent::__construct($message, $code);
    }
}
