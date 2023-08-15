<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Identifier;

use ArrayObject;

interface MfaIdentifierInterface
{
    final public const MFA_USER_SESSION_ID = 'user_session_id';

    final public const MFA_PASSWORD = 'password';

    /**
     * Identifies a user or service by the passed credentials
     *
     * @param array $credentials Authentication credentials
     * @return \ArrayObject|array|null
     */
    public function identify(array $credentials): ArrayObject|array|null;

    /**
     * Gets a list of errors happened in the identification process
     *
     * @return array
     */
    public function getErrors(): array;
}
