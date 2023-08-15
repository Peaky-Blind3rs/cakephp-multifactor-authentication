<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Authenticator;

use Authentication\Authenticator\ResultInterface;
use Cake\Core\InstanceConfigTrait;
use MultifactorAuthentication\Identifier\MfaIdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractMfaAuthenticator implements MfaAuthenticatorInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fields' => [
            MfaIdentifierInterface::MFA_USER_SESSION_ID => 'user_session_id',
            MfaIdentifierInterface::MFA_PASSWORD => 'password',
        ],
    ];

    /**
     * @var \MultifactorAuthentication\Identifier\MfaIdentifierInterface
     */
    protected MfaIdentifierInterface $_identifier;

    /**
     * @param \MultifactorAuthentication\Identifier\MfaIdentifierInterface $identifier Identifier or identifiers collection.
     * @param array<string, mixed> $config Configuration settings.
     */
    public function __construct(MfaIdentifierInterface $identifier, array $config = [])
    {
        $this->_identifier = $identifier;
        $this->setConfig($config);
    }

    /**
     * Gets the identifier.
     *
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierInterface
     */
    public function getIdentifier(): MfaIdentifierInterface
    {
        return $this->_identifier;
    }

    /**
     * Sets the identifier.
     *
     * @param \MultifactorAuthentication\Identifier\MfaIdentifierInterface $identifier IdentifierInterface instance.
     * @return $this
     */
    public function setIdentifier(MfaIdentifierInterface $identifier)
    {
        $this->_identifier = $identifier;

        return $this;
    }

    /**
     * @inheritDoc
     */
    abstract public function authenticate(ServerRequestInterface $request): ResultInterface;
}
