<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Authenticator;

use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MfaAuthenticatorInterface
{
    /**
     * Authenticate user.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server Request Instance
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface;
}
