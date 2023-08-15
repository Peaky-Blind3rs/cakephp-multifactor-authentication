<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Identifier;

use ArrayObject;
use Authentication\Identifier\Resolver\ResolverAwareTrait;
use Authentication\Identifier\Resolver\ResolverInterface;
use Authentication\PasswordHasher\PasswordHasherFactory;
use Authentication\PasswordHasher\PasswordHasherInterface;
use Authentication\PasswordHasher\PasswordHasherTrait;

class MfaPasswordIdentifier extends AbstractMfaIdentifier
{
    use PasswordHasherTrait {
        getPasswordHasher as protected _getPasswordHasher;
    }
    use ResolverAwareTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fields' => [
            self::MFA_USER_SESSION_ID => 'user_session_id',
            self::MFA_PASSWORD => 'password',
        ],
        'resolver' => 'Authentication.Orm',
        'passwordHasher' => null,
    ];

    /**
     * Return password hasher object.
     *
     * @return \Authentication\PasswordHasher\PasswordHasherInterface Password hasher instance.
     */
    public function getPasswordHasher(): PasswordHasherInterface
    {
        if ($this->_passwordHasher === null) {
            /** @var array|string|null $passwordHasher */
            $passwordHasher = $this->getConfig('passwordHasher');
            if ($passwordHasher !== null) {
                $passwordHasher = PasswordHasherFactory::build($passwordHasher);
            } else {
                $passwordHasher = $this->_getPasswordHasher();
            }
            $this->_passwordHasher = $passwordHasher;
        }

        return $this->_passwordHasher;
    }

    /**
     * @param array<string, string> $credentials Credentials Array
     * @return \ArrayObject|array|null
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function identify(array $credentials): ArrayObject|array|null
    {
        if (!isset($credentials[self::MFA_USER_SESSION_ID])) {
            return null;
        }
        $identity = $this->_findIdentity($credentials[self::MFA_USER_SESSION_ID]);
        if (array_key_exists(self::MFA_PASSWORD, $credentials)) {
            $password = $credentials[self::MFA_PASSWORD];
            if (!$this->_checkPassword($identity, $password)) {
                return null;
            }
        }

        return $identity;
    }

    /**
     * @param \ArrayObject<string, mixed>|array|null $identity Identity
     * @param string|null $password Password
     * @return bool
     * @psalm-suppress TooManyTemplateParams
     */
    protected function _checkPassword(ArrayObject|array|null $identity, ?string $password): bool
    {
        /** @var string $passwordField */
        $passwordField = $this->getConfig('fields.' . self::MFA_PASSWORD);

        if ($identity === null) {
            $identity = [
                $passwordField => '',
            ];
        }

        $hasher = $this->getPasswordHasher();
        /** @var string|null $hashedPassword */
        $hashedPassword = $identity[$passwordField];
        if (
            $hashedPassword === null ||
            !$hasher->check((string)$password, $hashedPassword)
        ) {
            return false;
        }

        $this->_needsPasswordRehash = $hasher->needsRehash($hashedPassword);

        return true;
    }

    /**
     * Find a user record using the username/identifier provided.
     *
     * @param string $identifier The username/identifier.
     * @return \ArrayObject<string, mixed>|array|null
     * @psalm-suppress TooManyTemplateParams
     */
    protected function _findIdentity(string $identifier): ArrayObject|array|null
    {
        /** @var string|string[] $fields */
        $fields = $this->getConfig('fields.' . self::MFA_USER_SESSION_ID);
        $conditions = [];
        foreach ((array)$fields as $field) {
            $conditions[$field] = $identifier;
        }

        /** @var \ArrayObject<string, mixed>|array|null $result */
        $result = $this->getResolver()->find($conditions, ResolverInterface::TYPE_OR);

        return $result;
    }
}
