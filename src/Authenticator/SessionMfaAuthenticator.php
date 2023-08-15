<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Authenticator;

use ArrayAccess;
use ArrayObject;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use MultifactorAuthentication\Identifier\MfaIdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionMfaAuthenticator extends AbstractMfaAuthenticator
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fields' => [
            MfaIdentifierInterface::MFA_USER_SESSION_ID => 'user_session_id',
        ],
        'sessionKey' => 'Auth.session.is_mfa_completed',
        'identify' => false,
        'identityAttribute' => 'identity',
    ];

    /**
     * Authenticate a user using session data.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to authenticate with.
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        /** @var string $sessionKey */
        $sessionKey = $this->getConfig('sessionKey');
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        /** @var \Authentication\IdentityInterface|null $user */
        $user = $session->read($sessionKey);

        if (empty($user)) {
            return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
        }

        if ($this->getConfig('identify') === true) {
            /** @var array<string, mixed> $credentials */
            $credentials = [];
            /** @var array<string, string> $fields */
            $fields = $this->getConfig('fields');
            foreach ($fields as $key => $field) {
                /**
                 * @psalm-suppress MixedAssignment
                 */
                $credentials[$key] = $user[$field];
            }
            $user = $this->_identifier->identify($credentials);

            if (empty($user)) {
                return new Result(null, ResultInterface::FAILURE_CREDENTIALS_INVALID);
            }
        }

        if (!($user instanceof ArrayAccess)) {
            $user = new ArrayObject($user);
        }

        return new Result($user, ResultInterface::SUCCESS);
    }
}
