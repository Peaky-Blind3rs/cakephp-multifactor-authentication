<?php
declare(strict_types=1);

namespace MultifactorAuthentication\Authenticator;

use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\UrlChecker\UrlCheckerTrait;
use MultifactorAuthentication\Identifier\MfaIdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

class FormMfaAuthenticator extends AbstractMfaAuthenticator
{
    use UrlCheckerTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'loginUrl' => null,
        'urlChecker' => 'Authentication.Default',
        'fields' => [
            MfaIdentifierInterface::MFA_USER_SESSION_ID => 'user_session_id',
            MfaIdentifierInterface::MFA_PASSWORD => 'password',
        ],
    ];

    /**
     * Checks the fields to ensure they are supplied.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request that contains login information.
     * @return array|null Username and password retrieved from a request body.
     */
    protected function _getData(ServerRequestInterface $request): ?array
    {
        /** @var array<string, string> $fields */
        $fields = $this->_config['fields'];
        /** @var array $body */
        $body = $request->getParsedBody();

        $data = [];
        foreach ($fields as $key => $field) {
            if (!isset($body[$field])) {
                return null;
            }

            $value = $body[$field];
            if (!is_string($value) || !strlen($value)) {
                return null;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Prepares the error object for a login URL error
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request that contains login information.
     * @return \Authentication\Authenticator\ResultInterface
     */
    protected function _buildLoginUrlErrorResult(ServerRequestInterface $request): ResultInterface
    {
        $uri = $request->getUri();
        /** @var string $base */
        $base = $request->getAttribute('base');
        if ($base !== null) {
            $uri = $uri->withPath($base . $uri->getPath());
        }
        /** @var bool $checkFullUrl */
        $checkFullUrl = $this->getConfig('urlChecker.checkFullUrl', false);
        if ($checkFullUrl) {
            $uri = (string)$uri;
        } else {
            $uri = $uri->getPath();
        }

        $errors = [
            sprintf(
                'Multi-Factor Authentication URL `%s` did not match `%s`.',
                $uri,
                implode('` or `', (array)$this->getConfig('loginUrl'))
            ),
        ];

        return new Result(null, ResultInterface::FAILURE_OTHER, $errors);
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        if (!$this->_checkUrl($request)) {
            return $this->_buildLoginUrlErrorResult($request);
        }

        $data = $this->_getData($request);
        if ($data === null) {
            return new Result(null, ResultInterface::FAILURE_CREDENTIALS_MISSING, [
                'Multi-Factor Authentication credentials not found',
            ]);
        }

        $user = $this->_identifier->identify($data);

        if (empty($user)) {
            return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
        }

        return new Result($user, ResultInterface::SUCCESS);
    }
}
