<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Authenticator;

use Authentication\AbstractCollection;
use Cake\Core\App;
use MultifactorAuthentication\Exception\MissingClassException;
use MultifactorAuthentication\Exception\WrongImplementationException;
use MultifactorAuthentication\Identifier\MfaIdentifierCollection;

class MfaAuthenticatorCollection extends AbstractCollection
{
    /**
     * Identifier collection.
     *
     * @var \MultifactorAuthentication\Identifier\MfaIdentifierCollection
     */
    protected MfaIdentifierCollection $_identifiers;

    /**
     * Constructor.
     *
     * @param \MultifactorAuthentication\Identifier\MfaIdentifierCollection $identifiers Identifiers collection.
     * @param array $config Config array.
     */
    public function __construct(MfaIdentifierCollection $identifiers, array $config = [])
    {
        $this->_identifiers = $identifiers;

        parent::__construct($config);
    }

    /**
     * Creates authenticator instance.
     *
     * @param string $class Authenticator class.
     * @param string $alias Authenticator alias.
     * @param array $config Config array.
     * @return \MultifactorAuthentication\Authenticator\MfaAuthenticatorInterface
     * @throws \MultifactorAuthentication\Exception\WrongImplementationException
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function _create($class, string $alias, array $config): MfaAuthenticatorInterface
    {
        $authenticator = new $class($this->_identifiers, $config);
        if (!($authenticator instanceof MfaAuthenticatorInterface)) {
            throw new WrongImplementationException(sprintf(
                'Authenticator class `%s` must implement `%s`.',
                $class,
                MfaAuthenticatorInterface::class
            ));
        }

        return $authenticator;
    }

    /**
     * Resolves authenticator class name.
     *
     * @param string $class Class name to be resolved.
     * @return string|null
     * @psalm-return class-string|null
     */
    protected function _resolveClassName(string $class): ?string
    {
        $className = App::className($class, 'Authenticator', 'Authenticator');

        return is_string($className) ? $className : null;
    }

    /**
     * @param string $class Missing class.
     * @param string|null $plugin Class plugin.
     * @return void
     * @throws \MultifactorAuthentication\Exception\MissingClassException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        $message = sprintf('Authenticator class `%s` was not found.', $class);
        throw new MissingClassException($message);
    }
}
