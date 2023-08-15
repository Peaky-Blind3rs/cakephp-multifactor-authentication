<?php
declare(strict_types=1);

namespace MultifactorAuthentication;

use Authentication\Authenticator\ResultInterface;
use MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface;
use MultifactorAuthentication\Identifier\MfaIdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MfaServiceInterface
{
    /**
     * Loads an authenticator.
     *
     * @param string $name Name or class name.
     * @param array $config Authenticator configuration.
     * @return \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface
     */
    public function loadAuthenticator(string $name, array $config = []): MfaAuthenticatorInterface;

    /**
     * Loads an identifier.
     *
     * @param string $name Name or class name.
     * @param array $config Identifier configuration.
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierInterface
     */
    public function loadIdentifier(string $name, array $config = []): MfaIdentifierInterface;

    /**
     * Authenticate the request against the configured authentication adapters.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Authentication\Authenticator\ResultInterface The result object. If none of the adapters was a success
     *  the last failed result is returned.
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface;

    /**
     * Gets the successful authenticator instance if one was successful after calling authenticate
     *
     * @return \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface|null
     */
    public function getAuthenticationProvider(): ?MfaAuthenticatorInterface;

    /**
     * Gets the result of the last authenticate() call.
     *
     * @return \Authentication\Authenticator\ResultInterface|null Authentication result interface
     */
    public function getResult(): ?ResultInterface;

    /**
     * Return the name of the identity attribute.
     *
     * @return string
     */
    public function getIdentityAttribute(): string;

    /**
     * Return the URL to redirect unauthenticated users to.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string|null
     */
    public function getUnauthenticatedRedirectUrl(ServerRequestInterface $request): ?string;

    /**
     * Return the URL that an authenticated user came from or null.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string|null
     */
    public function getMfaRedirect(ServerRequestInterface $request): ?string;
}
