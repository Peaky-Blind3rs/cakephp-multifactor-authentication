<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Identifier;

use Cake\Core\InstanceConfigTrait;

abstract class AbstractMfaIdentifier implements MfaIdentifierInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * Errors
     *
     * @var array
     */
    protected array $_errors = [];

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Configuration
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Returns errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }
}
