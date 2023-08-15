<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Middleware;

use Cake\Core\InstanceConfigTrait;
use Laminas\Diactoros\Response\RedirectResponse;
use MultifactorAuthentication\Exception\UnauthenticatedException;
use MultifactorAuthentication\MfaServiceInterface;
use MultifactorAuthentication\MfaServiceProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MultiFactorAuthenticationMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * Authentication service or application instance.
     *
     * @var \MultifactorAuthentication\MfaServiceInterface|\MultifactorAuthentication\MfaServiceProviderInterface
     */
    protected MfaServiceInterface|MfaServiceProviderInterface $subject;

    /**
     * Constructor
     *
     * @param \MultifactorAuthentication\MfaServiceProviderInterface|\MultifactorAuthentication\MfaServiceInterface $subject Multi-Factor Authentication service or application instance.
     * @param array<string, mixed> $config Array of configuration settings.
     */
    public function __construct(MfaServiceProviderInterface|MfaServiceInterface $subject, array $config = [])
    {
        $this->setConfig($config);
        $this->subject = $subject;
    }

    /**
     * Callable implementation for the middleware stack.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $service = $this->getMfaAuthenticationService($request);

        $result = $service->authenticate($request);

        $request = $request->withAttribute('mfa_authentication', $service);
        $request = $request->withAttribute('mfaAuthenticationResult', $result);
        try {
            $response = $handler->handle($request);
        } catch (UnauthenticatedException $e) {
            $url = $service->getUnauthenticatedRedirectUrl($request);
            if ($url) {
                return new RedirectResponse($url);
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Returns AuthenticationServiceInterface instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request.
     * @return \MultifactorAuthentication\MfaServiceInterface
     * @throws \RuntimeException When authentication method has not been defined.
     * @throws \Exception
     */
    protected function getMfaAuthenticationService(ServerRequestInterface $request): MfaServiceInterface
    {
        $subject = $this->subject;

        if ($subject instanceof MfaServiceProviderInterface) {
            $subject = $subject->getMultiFactorAuthenticationService($request);
        }

        return $subject;
    }
}
