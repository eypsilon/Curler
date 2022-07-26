<?php declare(strict_types=1);

namespace Many\Exception;

use Many\Exception\Exceptions;

/**
 * Curl Exception
 *
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
final class CurlException extends Exceptions
{
    /** @var Int Default http_response_code */
    protected $errorCode = 406;
}
