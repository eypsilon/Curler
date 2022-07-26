<?php declare(strict_types=1);

namespace Many\Exception;

use Exception;

/**
 * Base Exception
 *
 * @author Engin Ypsilon <engin.ypsilon@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
class Exceptions extends Exception
{
    function __construct($message=null, Int $code=0)
    {
        return parent::__construct((string) $message, (int) (isset($this->errorCode) AND !$code) ? $this->errorCode : $code);
    }
}
