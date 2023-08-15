<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Exception;

use Cake\Http\Exception\InternalErrorException;
use Throwable;

class MissingClassException extends InternalErrorException
{
    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
