<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Exception;

use Cake\Http\Exception\HttpException;
use Throwable;

class UnauthenticatedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message The exception message
     * @param int $code The exception code that will be used as a HTTP status code
     * @param \Throwable|null $previous The previous exception or null
     */
    public function __construct(string $message = '', int $code = 401, ?Throwable $previous = null)
    {
        if (!$message) {
            $message = 'Authentication is required to continue';
        }
        parent::__construct($message, $code, $previous);
    }
}
