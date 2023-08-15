<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Identifier;

use ArrayObject;
use Authentication\AbstractCollection;
use Authentication\Identifier\IdentifierInterface;
use Cake\Core\App;
use MultifactorAuthentication\Exception\MissingClassException;
use MultifactorAuthentication\Exception\WrongImplementationException;

class MfaIdentifierCollection extends AbstractCollection implements MfaIdentifierInterface
{
    /**
     * Errors
     *
     * @var array
     */
    protected array $_errors = [];

    /**
     * Identifier that successfully Identified the identity.
     *
     * @var \MultifactorAuthentication\Identifier\MfaIdentifierInterface|null
     */
    protected ?MfaIdentifierInterface $_successfulIdentifier;

    /**
     * @inheritDoc
     */
    public function identify(array $credentials): array|null|ArrayObject
    {
        /** @var \MultifactorAuthentication\Identifier\MfaIdentifierInterface $identifier */
        foreach ($this->_loaded as $name => $identifier) {
            $result = $identifier->identify($credentials);
            if ($result) {
                $this->_successfulIdentifier = $identifier;

                return $result;
            }
            $this->_errors[$name] = $identifier->getErrors();
        }

        $this->_successfulIdentifier = null;

        return null;
    }

    /**
     * Creates identifier instance.
     *
     * @param string $class Identifier class.
     * @param string $alias Identifier alias.
     * @param array $config Config array.
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierInterface
     * @throws \MultifactorAuthentication\Exception\WrongImplementationException
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function _create($class, string $alias, array $config): MfaIdentifierInterface
    {
        $identifier = new $class($config);
        if (!($identifier instanceof MfaIdentifierInterface)) {
            throw new WrongImplementationException(sprintf(
                'Identifier class `%s` must implement `%s`.',
                $class,
                IdentifierInterface::class
            ));
        }

        return $identifier;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Resolves identifier class name.
     *
     * @param string $class Class name to be resolved.
     * @return string|null
     * @psalm-return class-string|null
     */
    protected function _resolveClassName(string $class): ?string
    {
        $className = App::className($class, 'Identifier', 'Identifier');

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
        $message = sprintf('Identifier class `%s` was not found.', $class);
        throw new MissingClassException($message);
    }

    /**
     * Gets the successful identifier instance if one was successful after calling identify.
     *
     * @return \MultifactorAuthentication\Identifier\MfaIdentifierInterface|null
     */
    public function getIdentificationProvider(): ?MfaIdentifierInterface
    {
        return $this->_successfulIdentifier;
    }
}
