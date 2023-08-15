<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Controller\Component;

use Authentication\Authenticator\ResultInterface;
use Cake\Controller\Component;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Utility\Hash;
use MultifactorAuthentication\Authenticator\FormMfaAuthenticator;
use MultifactorAuthentication\Exception\MissingRequestAttributeException;
use MultifactorAuthentication\Exception\UnauthenticatedException;
use MultifactorAuthentication\Exception\WrongImplementationException;
use MultifactorAuthentication\MfaServiceInterface;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class MfaComponent extends Component implements EventDispatcherInterface
{
    use EventDispatcherTrait;

    protected $components = ['Authentication.Authentication'];

    /**
     * Configuration options
     *
     * - `requireIdentity` - By default AuthenticationComponent will require an
     *   identity to be present whenever it is active. You can set the option to
     *   false to disable that behavior. See allowUnauthenticated() as well.
     * - `unauthenticatedMessage` - Error message to use when `UnauthenticatedException` is thrown.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'requireIdentity' => true,
        'identityAttribute' => 'identity',
        'sessionKey' => 'session.is_mfa_completed',
        'identityCheckEvent' => 'Controller.startup',
        'unauthenticatedMessage' => null,
    ];

    /**
     * List of actions that don't require authentication.
     *
     * @var string[]
     */
    protected array $unauthenticatedActions = [];

    /**
     * Authentication service instance.
     *
     * @var \MultifactorAuthentication\MfaServiceInterface|null
     */
    protected ?MfaServiceInterface $_authentication = null;

    /**
     * Initialize component.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config): void
    {
        $controller = $this->getController();
        $this->setEventManager($controller->getEventManager());
    }

    /**
     * Get the Controller callbacks this Component is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.initialize' => 'beforeFilter',
            'Controller.startup' => 'startup',
        ];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function beforeFilter(): void
    {
        $authentication = $this->getAuthenticationService();
        $provider = $authentication->getAuthenticationProvider();

        if (
            $provider instanceof FormMfaAuthenticator
        ) {
            $this->dispatchEvent('MultiFactorAuthentication.afterIdentify', [
                'provider' => $provider,
                'identity' => $this->Authentication->getIdentity(),
                'service' => $authentication,
            ], $this->getController());
        }

        if ($this->getConfig('identityCheckEvent') === 'Controller.initialize') {
            $this->doIdentityCheck();
        }
    }

    /**
     * Start up event handler
     *
     * @return void
     * @throws \Exception when request is missing or has an invalid AuthenticationService
     * @throws \Authentication\Authenticator\UnauthenticatedException when requireIdentity is true and request is missing an identity
     */
    public function startup(): void
    {
        if ($this->getConfig('identityCheckEvent') === 'Controller.startup') {
            $this->doIdentityCheck();
        }
    }

    /**
     * @return \MultifactorAuthentication\MfaServiceInterface
     * @throws \MultifactorAuthentication\Exception\MissingRequestAttributeException
     */
    public function getAuthenticationService(): MfaServiceInterface
    {
        if ($this->_authentication !== null) {
            return $this->_authentication;
        }

        $controller = $this->getController();
        $service = $controller->getRequest()->getAttribute('mfa_authentication');
        if ($service === null) {
            throw new MissingRequestAttributeException(
                'The request object does not contain the required `authentication` attribute. Verify the ' .
                'AuthenticationMiddleware has been added.'
            );
        }

        if (!($service instanceof MfaServiceInterface)) {
            throw new WrongImplementationException(
                'Authentication service does not implement ' . MfaServiceInterface::class
            );
        }

        $this->_authentication = $service;

        return $service;
    }

    /**
     * Check if the identity presence is required.
     *
     * Also checks if the current action is accessible without authentication.
     *
     * @return void
     * @throws \Exception when request is missing or has an invalid AuthenticationService
     * @throws \MultifactorAuthentication\Exception\UnauthenticatedException when requireIdentity is true and request is missing an identity
     */
    protected function doIdentityCheck(): void
    {
        if (!$this->getConfig('requireIdentity')) {
            return;
        }

        $request = $this->getController()->getRequest();
        /** @var string $action */
        $action = $request->getParam('action');
        if (in_array($action, $this->unauthenticatedActions, true)) {
            return;
        }
        if (in_array($action, $this->Authentication->getUnauthenticatedActions(), true)) {
            return;
        }
        /** @var string $identityAttribute */
        $identityAttribute = $this->getConfig('identityAttribute');
        /** @var \Authentication\IdentityInterface $identity */
        $identity = $request->getAttribute($identityAttribute);
        /** @var string $sessionKey */
        $sessionKey = $this->getConfig('sessionKey');
        /** @var bool $mfaDone */
        $mfaDone = Hash::get($identity->getOriginalData(), $sessionKey, false);
        if (!$mfaDone) {
            /** @var string $unauthenticatedMessage */
            $unauthenticatedMessage = $this->getConfig('unauthenticatedMessage', '');
            throw new UnauthenticatedException($unauthenticatedMessage);
        }
    }

    /**
     * @param string[] $actions Unauthenticated Actions
     * @return $this
     */
    public function allowUnauthenticated(array $actions)
    {
        $this->unauthenticatedActions = $actions;

        return $this;
    }

    /**
     * @param string[] $actions Unauthenticated Actions
     * @return $this
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function addUnauthenticatedActions(array $actions)
    {
        $this->unauthenticatedActions = array_merge($this->unauthenticatedActions, $actions);
        $this->unauthenticatedActions = array_values(array_unique($this->unauthenticatedActions));

        return $this;
    }

    /**
     * Get the current list of actions that don't require authentication.
     *
     * @return string[]
     */
    public function getUnauthenticatedActions(): array
    {
        return $this->unauthenticatedActions;
    }

    /**
     * @return \Authentication\Authenticator\ResultInterface|null
     */
    public function getResult(): ?ResultInterface
    {
        return $this->getAuthenticationService()->getResult();
    }
}
