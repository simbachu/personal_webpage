<?php

declare(strict_types=1);

namespace App\Benefactor;

use App\Benefactor\OAuth\PatreonOAuthClient;
use App\Shared\Http\Route;
use App\Shared\Http\RouteHandler;
use App\Shared\Http\RouteResult;

//! @brief Route handler for Patreon OAuth login and member markup
class BenefactorRouteHandler implements RouteHandler
{
    //! @brief Construct the benefactor route handler
    //! @param oauth Patreon OAuth client
    //! @param memberCache Per-user member cache
    //! @param markup Member HTML formatter
    //! @param session OAuth session storage
    //! @param pageUrl Absolute /benefactor URL used as redirect_uri
    //! @param request Callback query parameters
    //! @param stateGenerator Optional CSRF state generator (injectable for tests)
    public function __construct(
        private readonly PatreonOAuthClient $oauth,
        private readonly MemberCache $memberCache,
        private readonly MemberMarkup $markup,
        private readonly BenefactorSession $session,
        private readonly string $pageUrl,
        private readonly BenefactorRequest $request,
        private $stateGenerator = null
    ) {
        $this->stateGenerator ??= static fn (): string => bin2hex(random_bytes(16));
    }

    //! @brief Handle GET /benefactor (login, callback, or cached members)
    //! @param route The matched route
    //! @param parameters Route parameters (unused)
    //! @return RouteResult Page data or a redirect after OAuth callback
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        if (!$this->oauth->isConfigured()) {
            return $this->page($route, [
                'configured' => false,
                'error' => 'Patreon OAuth is not configured.',
            ]);
        }

        if ($this->request->error !== null) {
            return $this->loginPage($route)->withData([
                'error' => $this->request->error,
            ]);
        }

        if ($this->request->code !== null) {
            return $this->handleCallback($route);
        }

        $token = $this->session->getAccessToken();
        $userId = $this->session->getUserId();
        if ($token !== null && $userId !== null) {
            return $this->presentMembers($route, $token, $userId);
        }

        return $this->loginPage($route);
    }

    //! @brief Exchange the OAuth code and redirect to a clean URL
    //! @param route The matched route
    //! @return RouteResult
    private function handleCallback(Route $route): RouteResult
    {
        $expected = $this->session->getState();
        if ($this->request->state === null || $expected === null || $this->request->state !== $expected) {
            return $this->loginPage($route)->withData([
                'error' => 'Invalid OAuth state.',
            ]);
        }

        $this->session->clearState();

        $tokenResult = $this->oauth->exchangeCode((string) $this->request->code, $this->pageUrl);
        if ($tokenResult->isFailure()) {
            return $this->loginPage($route)->withData([
                'error' => $tokenResult->getError(),
            ]);
        }

        $token = $tokenResult->getValue();
        $this->session->setAccessToken($token);

        $campaign = $this->memberCache->resolve($token, null);
        if ($campaign->isFailure()) {
            return $this->loginPage($route)->withData([
                'error' => $campaign->getError(),
            ]);
        }

        $this->session->setUserId($campaign->getValue()->userId);

        return RouteResult::redirect($this->pageUrl, $route->getTemplate());
    }

    //! @brief Show cached or freshly fetched member markup
    //! @param route The matched route
    //! @param token OAuth access token
    //! @param userId Patreon user id
    //! @return RouteResult
    private function presentMembers(Route $route, string $token, string $userId): RouteResult
    {
        $campaign = $this->memberCache->resolve($token, $userId);
        if ($campaign->isFailure()) {
            return $this->page($route, [
                'error' => $campaign->getError(),
            ]);
        }

        $formatted = $this->markup->format($campaign->getValue()->members);

        return $this->page($route, [
            'logged_in' => true,
            'markup' => $formatted['markup'],
            'patrons' => $formatted['patrons'],
        ]);
    }

    //! @brief Show the Patreon login link and store CSRF state
    //! @param route The matched route
    //! @return RouteResult
    private function loginPage(Route $route): RouteResult
    {
        $state = ($this->stateGenerator)();
        $this->session->setState($state);

        return $this->page($route, [
            'authorize_url' => $this->oauth->authorizeUrl($this->pageUrl, $state),
        ]);
    }

    //! @brief Build a full page result with stable template keys
    //! @param route The matched route
    //! @param data Overrides for the default view data
    //! @return RouteResult
    private function page(Route $route, array $data): RouteResult
    {
        $defaults = [
            'configured' => true,
            'logged_in' => false,
            'authorize_url' => null,
            'markup' => null,
            'patrons' => [],
            'error' => null,
            'meta' => [
                'title' => 'Benefactor',
                'description' => 'Copy a ranked list of your Patreon members as HTML.',
            ],
        ];

        return new RouteResult($route->getTemplate(), array_merge($defaults, $data));
    }
}
