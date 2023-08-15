<?php
declare(strict_types=1);

namespace MultifactorAuthentication;

use Authentication\Authenticator\ResultInterface;
use Cake\Core\InstanceConfigTrait;
use MultifactorAuthentication\Authenticator\MfaAuthenticatorCollection;
use MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface;
use MultifactorAuthentication\Exception\AuthenticatorsNotLoadedException;
use MultifactorAuthentication\Identifier\MfaIdentifierCollection;
use MultifactorAuthentication\Identifier\MfaIdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

class MfaService implements MfaServiceInterface
{
    use InstanceConfigTrait;

    /**
     * Authenticator collection
     *
     * @var \MultifactorAuthentication\Authenticator\MfaAuthenticatorCollection|null
     */
    protected ?MfaAuthenticatorCollection $_authenticators;

    /**
     * Identifier collection
     *
     * @var \MultifactorAuthentication\Identifier\MfaIdentifierCollection|null
     */
    protected ?MfaIdentifierCollection $_identifiers;

    /**
     * Authenticator that successfully authenticated the identity.
     *
     * @var \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface|null
     */
    protected ?MfaAuthenticatorInterface $_successfulAuthenticator;

    /**
     * Result of the last authenticate() call.
     *
     * @var \Authentication\Authenticator\ResultInterface|null
     */
    protected ?ResultInterface $_result;

    /**
     * Default configuration
     *
     * - `authenticators` - An array of authentication objects to use for authenticating users.
     *   You can configure multiple adapters they will be checked sequentially
     *   when users are identified.
     * - `identifiers` - An array of identifiers. The identifiers are constructed by the service
     *   and then passed to the authenticators that will pass the credentials to them and get the
     *   user data.
     * - `identityClass` - The class name of identity or a callable identity builder.
     * - `identityAttribute` - The request attribute used to store the identity. Default to `identity`.
     * - `unauthenticatedRedirect` - The URL to redirect unauthenticated errors to. See
     *    AuthenticationComponent::allowUnauthenticated()
     * - `queryParam` - The name of the query string parameter containing the previously blocked URL
     *   in case of unauthenticated redirect, or null to disable appending the denied URL.
     *
     * ### Example:
     *
     * ```
     * $service = new AuthenticationService([
     *    'authenticators' => [
     *        'MfaAuthentication.Form
     *    ],
     *    'identifiers' => [
     *        'MfaAuthentication.Password'
     *    ]
     * ]);
     * ```
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'authenticators' => [],
        'identifiers' => [],
        'queryParam' => null,
        'unauthenticatedRedirect' => null,
        'authenticationService' => null,
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Configuration options.
     */
    public function __construct(array $config = [])
    {
        $this->_authenticators = null;
        $this->_identifiers = null;
        $this->setConfig($config);
    }

    /**
     * Access the identifier collection
     *
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierCollection
     */
    public function identifiers(): MfaIdentifierCollection
    {
        if ($this->_identifiers === null) {
            /** @var \MultifactorAuthentication\Identifier\MfaIdentifierInterface[] $identifiers */
            $identifiers = $this->getConfig('identifiers');
            $this->_identifiers = new MfaIdentifierCollection($identifiers);
        }

        return $this->_identifiers;
    }

    /**
     * Access the authenticator collection
     *
     * @return \MultifactorAuthentication\Authenticator\MfaAuthenticatorCollection
     */
    public function authenticators(): MfaAuthenticatorCollection
    {
        if ($this->_authenticators === null) {
            $identifiers = $this->identifiers();
            /** @var \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface[] $authenticators */
            $authenticators = $this->getConfig('authenticators');
            $this->_authenticators = new MfaAuthenticatorCollection($identifiers, $authenticators);
        }

        return $this->_authenticators;
    }

    /**
     * @param string $name Authenticator Name
     * @param array<string, mixed> $config Authenticator Configuration
     * @return \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface
     * @throws \Exception
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function loadAuthenticator(string $name, array $config = []): MfaAuthenticatorInterface
    {
        /** @var \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface $authenticator */
        $authenticator = $this->authenticators()->load($name, $config);

        return $authenticator;
    }

    /**
     * @param string $name Identifier Name
     * @param array<string, mixed> $config Identifier Configuration
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierInterface
     * @throws \Exception
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function loadIdentifier(string $name, array $config = []): MfaIdentifierInterface
    {
        /** @var \MultifactorAuthentication\Identifier\MfaIdentifierInterface $identifier */
        $identifier = $this->identifiers()->load($name, $config);

        return $identifier;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $result = null;
        /** @var \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface $authenticator */
        foreach ($this->authenticators() as $authenticator) {
            $result = $authenticator->authenticate($request);
            if ($result->isValid()) {
                $this->_successfulAuthenticator = $authenticator;

                return $this->_result = $result;
            }
        }

        if ($result === null) {
            throw new AuthenticatorsNotLoadedException(
                'No authenticators loaded. You need to load at least one authenticator.'
            );
        }

        $this->_successfulAuthenticator = null;

        return $this->_result = $result;
    }

    /**
     * @inheritDoc
     */
    public function getAuthenticationProvider(): ?MfaAuthenticatorInterface
    {
        return $this->_successfulAuthenticator;
    }

    /**
     * @inheritDoc
     */
    public function getResult(): ?ResultInterface
    {
        return $this->_result;
    }

    /**
     * @inheritDoc
     */
    public function getIdentityAttribute(): string
    {
        /** @var string $identityAttribute */
        $identityAttribute = $this->getConfig('identityAttribute');

        return $identityAttribute;
    }

    /**
     * @inheritDoc
     */
    public function getUnauthenticatedRedirectUrl(ServerRequestInterface $request): ?string
    {
        /** @var string|null $param */
        $param = $this->getConfig('queryParam');
        /** @var string|null $target */
        $target = $this->getConfig('unauthenticatedRedirect');
        if ($target === null) {
            return null;
        }
        if ($param === null) {
            return $target;
        }

        $uri = $request->getUri();
        $redirect = $uri->getPath();
        if ($uri->getQuery()) {
            $redirect .= '?' . $uri->getQuery();
        }
        $query = urlencode($param) . '=' . urlencode($redirect);

        /** @var array $url */
        $url = parse_url($target);
        /** @var string|null $urlQuery */
        $urlQuery = $url['query'];
        if (isset($urlQuery) && strlen($urlQuery)) {
            $urlQuery .= '&' . $query;
        } else {
            $urlQuery = $query;
        }
        /** @var string|null $urlFragment */
        $urlFragment = $url['fragment'] ?? null;
        $fragment = isset($urlFragment) ? '#' . $urlFragment : '';
        /** @var string|null $urlPath */
        $urlPath = $url['path'] ?? null;
        $urlPath = $urlPath ?? '/';

        return $urlPath . '?' . $urlQuery . $fragment;
    }

    /**
     * @inheritDoc
     */
    public function getMfaRedirect(ServerRequestInterface $request): ?string
    {
        /** @var string|null $redirectParam */
        $redirectParam = $this->getConfig('queryParam');
        $params = $request->getQueryParams();
        if (
            empty($redirectParam) ||
            !isset($params[$redirectParam]) ||
            strlen((string)$params[$redirectParam]) === 0
        ) {
            return null;
        }
        /** @var string $redirectUrl */
        $redirectUrl = $params[$redirectParam];
        $parsed = parse_url($redirectUrl);
        if ($parsed === false) {
            return null;
        }
        if (!empty($parsed['host']) || !empty($parsed['scheme'])) {
            return null;
        }
        $parsed += ['path' => '/', 'query' => ''];
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        if (strlen($parsed['path']) && $parsed['path'][0] !== '/') {
            $parsed['path'] = "/{$parsed['path']}";
        }
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        if ($parsed['query']) {
            $parsed['query'] = "?{$parsed['query']}";
        }

        return $parsed['path'] . $parsed['query'];
    }
}
