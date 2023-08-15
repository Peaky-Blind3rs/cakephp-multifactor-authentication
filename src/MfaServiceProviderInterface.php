<?php
declare(strict_types=1);

namespace MultifactorAuthentication;

use Psr\Http\Message\ServerRequestInterface;

interface MfaServiceProviderInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Server Request Interface Instance
     * @return \MultifactorAuthentication\MfaServiceInterface
     */
    public function getMultiFactorAuthenticationService(
        ServerRequestInterface $request
    ): MfaServiceInterface;
}
