<?php declare(strict_types=1);

namespace Many\Exception;

use Many\Exception\Exceptions;

/**
 * Callback Failed Exception
 *
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
final class AppCallbackException extends Exceptions
{
    /** @var Int Default http_response_code */
    protected $errorCode = 406;
}
