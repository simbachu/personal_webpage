<?php

declare(strict_types=1);

namespace App\Router\Handler;

use App\Router\RouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use App\Type\TournamentIdentifier;
use App\Type\MonsterIdentifier;
use App\Presenter\TournamentPresenter;
use App\Service\TournamentManagerInterface;
use App\Service\EmailRegistrationService;
use App\Repository\UserRegistrationRepositoryInterface;
use App\Service\PokemonCatalogService;
use App\Service\SwissTournamentService;
use InvalidArgumentException;
use RuntimeException;

//! @brief Route handler for tournament routes
//!
//! Handles tournament flow: email entry, secret verification, voting, progress, bracket.
class TournamentRouteHandler implements RouteHandler
{
    private TournamentPresenter $presenter;
    private TournamentManagerInterface $tournamentManager;
    private EmailRegistrationService $emailService;
    private UserRegistrationRepositoryInterface $userRepo;
    private PokemonCatalogService $catalogService;

    //! @brief Construct tournament route handler
    //! @param presenter Tournament presenter
    //! @param tournamentManager Tournament manager service
    //! @param emailService Email registration service
    //! @param userRepo User registration repository
    //! @param catalogService Pokemon catalog service
    public function __construct(
        TournamentPresenter $presenter,
        TournamentManagerInterface $tournamentManager,
        EmailRegistrationService $emailService,
        UserRegistrationRepositoryInterface $userRepo,
        PokemonCatalogService $catalogService
    ) {
        $this->presenter = $presenter;
        $this->tournamentManager = $tournamentManager;
        $this->emailService = $emailService;
        $this->userRepo = $userRepo;
        $this->catalogService = $catalogService;
    }

    //! @brief Handle tournament routes
    //! @param route The matched route
    //! @param parameters Route parameters
    //! @return RouteResult The result containing tournament data or error
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        // Get actual request path relative to /tournament
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/tournament', PHP_URL_PATH);
        $path = str_replace('/tournament', '', $requestPath);
        if ($path === '') {
            $path = '/';
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Route based on path
        if ($path === '/' || $path === '') {
            return $this->handleSetup();
        } elseif ($path === '/email' || str_starts_with($path, '/email')) {
            return $this->handleEmailEntry($method);
        } elseif ($path === '/secret' || str_starts_with($path, '/secret')) {
            return $this->handleSecretEntry($method);
        } elseif (str_starts_with($path, '/vote') || str_starts_with($path, '/voting')) {
            return $this->handleVoting($method, $parameters);
        } elseif (str_starts_with($path, '/progress')) {
            return $this->handleProgress($parameters);
        } elseif (str_starts_with($path, '/bracket')) {
            return $this->handleBracket($method, $parameters);
        } elseif (str_starts_with($path, '/bracket-vote')) {
            return $this->handleBracketVoting($method, $parameters);
        }

        // Default: setup page
        return $this->handleSetup();
    }

    //! @brief Handle tournament setup/landing page
    //! @return RouteResult
    private function handleSetup(): RouteResult
    {
        // Check if user has an active tournament and redirect if so
        $email = $this->getUserEmail();
        if ($email !== null && $this->userRepo->isRegistered($email)) {
            $tournaments = $this->tournamentManager->getUserTournaments($email);
            if (!empty($tournaments)) {
                $tournament = $tournaments[0]; // Get most recent tournament
                $redirectUrl = $this->getTournamentRedirectUrl($tournament->getId());
                if ($redirectUrl !== null) {
                    header("Location: {$redirectUrl}");
                    exit;
                }
            }
        }

        $data = $this->presenter->presentSetup();
        return new RouteResult(
            TemplateName::TOURNAMENT_SETUP,
            array_merge($data, [
                'meta' => [
                    'title' => 'Pokémon Tournament Ranking',
                    'description' => 'Rank all Pokémon by voting in a Swiss tournament format.',
                ],
            ])
        );
    }

    //! @brief Handle email entry page
    //! @param method HTTP method
    //! @return RouteResult
    private function handleEmailEntry(string $method): RouteResult
    {
        if ($method === 'POST') {
            $email = $_POST['email'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new RouteResult(
                    TemplateName::TOURNAMENT_EMAIL_ENTRY,
                    [
                        'error' => 'Invalid email address',
                        'email' => $email,
                        'meta' => [
                            'title' => 'Pokémon Tournament - Register Email',
                            'description' => 'Enter your email to start ranking Pokémon.',
                        ],
                    ],
                    HttpStatusCode::BAD_REQUEST
                );
            }

            // Check if already registered
            if ($this->userRepo->isRegistered($email)) {
                // Store email in session
                $this->storeUserEmail($email);
                
                // Already registered, redirect to appropriate tournament state
                $tournaments = $this->tournamentManager->getUserTournaments($email);
                if (!empty($tournaments)) {
                    $tournament = $tournaments[0];
                    $redirectUrl = $this->getTournamentRedirectUrl($tournament->getId());
                    if ($redirectUrl !== null) {
                        header("Location: {$redirectUrl}");
                        exit;
                    }
                    // Fallback to progress
                    $tournamentId = $tournament->getId()->__toString();
                    header("Location: /tournament/progress?id={$tournamentId}");
                    exit;
                }
            }

            // Generate secret and send email
            $secret = $this->emailService->generateSecret();
            $expiresAt = time() + 86400; // 24 hours
            $this->userRepo->storePendingRegistration($email, $secret, $expiresAt);
            
            try {
                $this->emailService->sendRegistrationEmail($email, $secret);
            } catch (\Exception $e) {
                return new RouteResult(
                    TemplateName::TOURNAMENT_EMAIL_ENTRY,
                    [
                        'error' => 'Failed to send email. Please try again.',
                        'email' => $email,
                        'meta' => [
                            'title' => 'Pokémon Tournament - Register Email',
                            'description' => 'Enter your email to start ranking Pokémon.',
                        ],
                    ],
                    HttpStatusCode::INTERNAL_SERVER_ERROR
                );
            }

            // Redirect to secret entry page
            header("Location: /tournament/secret?email=" . urlencode($email));
            exit;
        }

        // GET: Show email entry form
        return new RouteResult(
            TemplateName::TOURNAMENT_EMAIL_ENTRY,
            [
                'meta' => [
                    'title' => 'Pokémon Tournament - Register Email',
                    'description' => 'Enter your email to start ranking Pokémon.',
                ],
            ]
        );
    }

    //! @brief Handle secret entry/verification page
    //! @param method HTTP method
    //! @return RouteResult
    private function handleSecretEntry(string $method): RouteResult
    {
        $email = $_GET['email'] ?? $_POST['email'] ?? '';

        if ($method === 'POST') {
            $secret = $_POST['secret'] ?? '';
            
            if (empty($email) || empty($secret)) {
                return new RouteResult(
                    TemplateName::TOURNAMENT_SECRET_ENTRY,
                    [
                        'error' => 'Email and secret code are required',
                        'email' => $email,
                        'meta' => [
                            'title' => 'Pokémon Tournament - Verify Code',
                            'description' => 'Enter your verification code.',
                        ],
                    ],
                    HttpStatusCode::BAD_REQUEST
                );
            }

            // Verify secret
            $verified = $this->userRepo->verifyAndRegister($email, strtoupper(trim($secret)));
            
            if (!$verified) {
                return new RouteResult(
                    TemplateName::TOURNAMENT_SECRET_ENTRY,
                    [
                        'error' => 'Invalid or expired secret code',
                        'email' => $email,
                        'meta' => [
                            'title' => 'Pokémon Tournament - Verify Code',
                            'description' => 'Enter your verification code.',
                        ],
                    ],
                    HttpStatusCode::UNAUTHORIZED
                );
            }

            // Store email in session for future visits
            $this->storeUserEmail($email);

            // Check if user has existing tournament
            $tournaments = $this->tournamentManager->getUserTournaments($email);
            if (!empty($tournaments)) {
                $tournament = $tournaments[0];
                $redirectUrl = $this->getTournamentRedirectUrl($tournament->getId());
                if ($redirectUrl !== null) {
                    header("Location: {$redirectUrl}");
                    exit;
                }
                // Fallback to progress
                $tournamentId = $tournament->getId()->__toString();
                header("Location: /tournament/progress?id={$tournamentId}");
                exit;
            }

            // Start new tournament - get all eligible Pokemon
            $allPokemon = $this->getAllEligiblePokemon();
            if (empty($allPokemon)) {
                return new RouteResult(
                    TemplateName::TOURNAMENT_SECRET_ENTRY,
                    [
                        'error' => 'No eligible Pokémon found. Please contact support.',
                        'email' => $email,
                        'meta' => [
                            'title' => 'Pokémon Tournament - Verify Code',
                            'description' => 'Enter your verification code.',
                        ],
                    ],
                    HttpStatusCode::INTERNAL_SERVER_ERROR
                );
            }

            // Create tournament with all eligible Pokemon
            $tournament = $this->tournamentManager->createTournament($allPokemon, $email);
            
            // Store email in session
            $this->storeUserEmail($email);
            
            // Redirect to first voting match
            header("Location: /tournament/vote?id={$tournament->getId()->__toString()}");
            exit;
        }

        // GET: Show secret entry form
        return new RouteResult(
            TemplateName::TOURNAMENT_SECRET_ENTRY,
            [
                'email' => $email,
                'meta' => [
                    'title' => 'Pokémon Tournament - Verify Code',
                    'description' => 'Enter your verification code.',
                ],
            ]
        );
    }

    //! @brief Handle voting/match page
    //! @param method HTTP method
    //! @param parameters Route parameters
    //! @return RouteResult
    private function handleVoting(string $method, array $parameters): RouteResult
    {
        $tournamentId = $this->getTournamentIdFromRequest();
        if ($tournamentId === null) {
            return $this->errorResult('Tournament ID required', HttpStatusCode::BAD_REQUEST);
        }

        // Store email from request in session if present
        $email = $this->getUserEmail();
        if ($email !== null) {
            $this->storeUserEmail($email);
        }

        try {
            $tournament = $this->tournamentManager->getTournament($tournamentId);
        } catch (InvalidArgumentException $e) {
            return $this->errorResult('Tournament not found', HttpStatusCode::NOT_FOUND);
        }

        if ($method === 'POST') {
            // Handle vote submission
            $winner = $_POST['winner'] ?? '';
            $outcomeParam = $_POST['outcome'] ?? '';
            $participant1 = $_POST['participant1'] ?? '';
            $participant2 = $_POST['participant2'] ?? '';
            
            if (empty($participant1)) {
                return $this->errorResult('Invalid vote data', HttpStatusCode::BAD_REQUEST);
            }

            try {
                $monster1 = MonsterIdentifier::fromString($participant1);
                $monster2 = $participant2 ? MonsterIdentifier::fromString($participant2) : null;
                
                // Check if draw was selected
                if ($outcomeParam === 'draw') {
                    if ($monster2 === null) {
                        return $this->errorResult('Cannot have draw for bye match', HttpStatusCode::BAD_REQUEST);
                    }
                    // Record draw result
                    $this->tournamentManager->recordMatchResult(
                        $tournamentId,
                        $monster1,
                        $monster2,
                        'draw',
                        null
                    );
                } else {
                    // Handle winner selection
                    if (empty($winner)) {
                        return $this->errorResult('Invalid vote data', HttpStatusCode::BAD_REQUEST);
                    }
                    
                    $winnerMonster = MonsterIdentifier::fromString($winner);
                    
                    // Determine outcome
                    $outcome = $winnerMonster->equals($monster1) ? 'win' : 'loss';
                    
                    if ($monster2 === null) {
                        // Bye - record free win for participant
                        $this->tournamentManager->recordBye($tournamentId, $monster1);
                    } else {
                        $this->tournamentManager->recordMatchResult(
                            $tournamentId,
                            $monster1,
                            $monster2,
                            $outcome,
                            $winnerMonster
                        );
                    }
                }

                // Check if round is complete and auto-advance if needed
                if ($this->tournamentManager->isCurrentRoundComplete($tournamentId)) {
                    try {
                        $this->tournamentManager->advanceToNextRound($tournamentId);
                    } catch (\Exception $e) {
                        // If advancement fails, continue to next match/progress
                    }
                }

                // Get user email to include in redirect
                $email = $this->getUserEmail();
                $emailParam = $email ? "&email=" . urlencode($email) : "";
                
                // Redirect back to voting for next match or progress
                header("Location: /tournament/vote?id={$tournamentId->__toString()}{$emailParam}");
                exit;
            } catch (\Exception $e) {
                return $this->errorResult('Failed to record vote: ' . $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        // GET: Show voting page with current match
        $pairings = $this->tournamentManager->getCurrentRoundPairings($tournamentId);
        if (empty($pairings)) {
            // Tournament complete or no more matches
            if ($tournament->isComplete()) {
                $email = $this->getUserEmail();
                $emailParam = $email ? "&email=" . urlencode($email) : "";
                header("Location: /tournament/bracket?id={$tournamentId->__toString()}{$emailParam}");
                exit;
            }
            // Advance to next round
            try {
                $this->tournamentManager->advanceToNextRound($tournamentId);
                $pairings = $this->tournamentManager->getCurrentRoundPairings($tournamentId);
            } catch (\Exception $e) {
                return $this->errorResult('Failed to advance round', HttpStatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        // Get first incomplete pairing
        $currentPairing = $pairings[0] ?? null;
        if ($currentPairing === null) {
            $email = $this->getUserEmail();
            $emailParam = $email ? "&email=" . urlencode($email) : "";
            header("Location: /tournament/progress?id={$tournamentId->__toString()}{$emailParam}");
            exit;
        }

        try {
            $data = $this->presenter->presentVoting(
                $tournamentId,
                $currentPairing[0],
                $currentPairing[1] ?? null
            );
            
            // Add email to data for template links
            if ($email !== null) {
                $data['email'] = $email;
            }
            
            return new RouteResult(
                TemplateName::TOURNAMENT_VOTING,
                array_merge($data, [
                    'meta' => [
                        'title' => 'Pokémon Tournament - Vote',
                        'description' => 'Choose your favorite Pokémon.',
                    ],
                ])
            );
        } catch (RuntimeException $e) {
            return $this->errorResult('Failed to load voting page: ' . $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    //! @brief Handle progress/standings page
    //! @param parameters Route parameters
    //! @return RouteResult
    private function handleProgress(array $parameters): RouteResult
    {
        $tournamentId = $this->getTournamentIdFromRequest();
        if ($tournamentId === null) {
            return $this->errorResult('Tournament ID required', HttpStatusCode::BAD_REQUEST);
        }

        // Store email from request in session if present
        $email = $this->getUserEmail();
        if ($email !== null) {
            $this->storeUserEmail($email);
        }

        try {
            $data = $this->presenter->presentProgress($tournamentId);
            
            // Add email to data for template links
            if ($email !== null) {
                $data['email'] = $email;
            }
            
            return new RouteResult(
                TemplateName::TOURNAMENT_PROGRESS,
                array_merge($data, [
                    'meta' => [
                        'title' => 'Pokémon Tournament - Progress',
                        'description' => 'View tournament standings and progress.',
                    ],
                ])
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResult('Tournament not found', HttpStatusCode::NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResult('Failed to load progress: ' . $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    //! @brief Handle bracket view
    //! @param method HTTP method
    //! @param parameters Route parameters
    //! @return RouteResult
    private function handleBracket(string $method, array $parameters): RouteResult
    {
        $tournamentId = $this->getTournamentIdFromRequest();
        if ($tournamentId === null) {
            return $this->errorResult('Tournament ID required', HttpStatusCode::BAD_REQUEST);
        }

        // Store email from request in session if present
        $email = $this->getUserEmail();
        if ($email !== null) {
            $this->storeUserEmail($email);
        }

        try {
            $data = $this->presenter->presentBracket($tournamentId);
            
            // Add email to data for template links
            if ($email !== null) {
                $data['email'] = $email;
            }
            
            return new RouteResult(
                TemplateName::TOURNAMENT_BRACKET,
                array_merge($data, [
                    'meta' => [
                        'title' => 'Pokémon Tournament - Bracket',
                        'description' => 'View the top 16 double elimination bracket.',
                    ],
                ])
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResult('Tournament not found', HttpStatusCode::NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->errorResult($e->getMessage(), HttpStatusCode::BAD_REQUEST);
        }
    }

    //! @brief Handle bracket voting
    //! @param method HTTP method
    //! @param parameters Route parameters
    //! @return RouteResult
    private function handleBracketVoting(string $method, array $parameters): RouteResult
    {
        $tournamentId = $this->getTournamentIdFromRequest();
        if ($tournamentId === null) {
            return $this->errorResult('Tournament ID required', HttpStatusCode::BAD_REQUEST);
        }

        // Store email from request in session if present
        $email = $this->getUserEmail();
        if ($email !== null) {
            $this->storeUserEmail($email);
        }

        try {
            $tournament = $this->tournamentManager->getTournament($tournamentId);
        } catch (InvalidArgumentException $e) {
            return $this->errorResult('Tournament not found', HttpStatusCode::NOT_FOUND);
        }

        if ($method === 'POST') {
            // Handle bracket vote submission
            $matchId = $_POST['match_id'] ?? '';
            $winner = $_POST['winner'] ?? '';
            
            if (empty($matchId) || empty($winner)) {
                return $this->errorResult('Match ID and winner required', HttpStatusCode::BAD_REQUEST);
            }

            try {
                $winnerMonster = MonsterIdentifier::fromString($winner);
                $this->tournamentManager->recordBracketMatchResult($tournamentId, $matchId, $winnerMonster);

                // Redirect back to bracket view
                $emailParam = $email ? "&email=" . urlencode($email) : "";
                header("Location: /tournament/bracket?id={$tournamentId->__toString()}{$emailParam}");
                exit;
            } catch (\Exception $e) {
                return $this->errorResult('Failed to record bracket vote: ' . $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        // GET: Show bracket voting page with current match
        $match = $this->tournamentManager->getNextBracketMatch($tournamentId);
        if ($match === null) {
            // No matches ready, redirect to bracket view
            $emailParam = $email ? "&email=" . urlencode($email) : "";
            header("Location: /tournament/bracket?id={$tournamentId->__toString()}{$emailParam}");
            exit;
        }

        try {
            $data = $this->presenter->presentVoting(
                $tournamentId,
                $match['participant1'],
                $match['participant2'] ?? null
            );
            
            // Add bracket-specific data
            $data['match_id'] = $match['id'];
            $data['bracket_type'] = $match['bracket'] ?? 'winner';
            
            // Add email to data for template links
            if ($email !== null) {
                $data['email'] = $email;
            }
            
            return new RouteResult(
                TemplateName::TOURNAMENT_VOTING,
                array_merge($data, [
                    'meta' => [
                        'title' => 'Pokémon Tournament - Bracket Vote',
                        'description' => 'Vote in the double elimination bracket.',
                    ],
                ])
            );
        } catch (RuntimeException $e) {
            return $this->errorResult('Failed to load bracket voting: ' . $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    //! @brief Get tournament ID from request (query string or POST)
    //! @return TournamentIdentifier|null
    private function getTournamentIdFromRequest(): ?TournamentIdentifier
    {
        $id = $_GET['id'] ?? $_POST['tournament_id'] ?? null;
        if ($id === null) {
            return null;
        }

        try {
            return TournamentIdentifier::fromString($id);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    //! @brief Get user email from request (query param, session, or cookie)
    //! @return string|null Email if found, null otherwise
    private function getUserEmail(): ?string
    {
        // Check query parameter first
        $email = $_GET['email'] ?? $_POST['email'] ?? null;
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        // Check session (if available)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $email = $_SESSION['tournament_email'] ?? null;
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return null;
    }

    //! @brief Store user email in session for persistence
    //! @param email User email
    private function storeUserEmail(string $email): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['tournament_email'] = $email;
    }

    //! @brief Determine the appropriate redirect URL for a tournament based on its state
    //! @param tournamentId Tournament identifier
    //! @return string|null Redirect URL or null if no redirect needed
    private function getTournamentRedirectUrl(TournamentIdentifier $tournamentId): ?string
    {
        try {
            $tournament = $this->tournamentManager->getTournament($tournamentId);
            $email = $this->getUserEmail();
            $emailParam = $email ? "&email=" . urlencode($email) : "";
            
            // If tournament is complete, show bracket
            if ($tournament->isComplete()) {
                return "/tournament/bracket?id={$tournamentId->__toString()}{$emailParam}";
            }

            // Check if there are incomplete matches in current round
            $pairings = $this->tournamentManager->getCurrentRoundPairings($tournamentId);
            
            // If no pairings, tournament might need to advance or is in progress
            // Check if we can find any incomplete pairing to vote on
            if (empty($pairings)) {
                // Round might be complete or tournament needs advancement
                return "/tournament/progress?id={$tournamentId->__toString()}{$emailParam}";
            }

            // There are pairings, redirect to voting
            return "/tournament/vote?id={$tournamentId->__toString()}{$emailParam}";
        } catch (\Exception $e) {
            // Tournament not found or error - don't redirect
            return null;
        }
    }

    //! @brief Get all eligible Pokemon species for tournament
    //! @return array<MonsterIdentifier> Array of monster identifiers
    private function getAllEligiblePokemon(): array
    {
        // Get catalog from PokemonCatalogService
        $dbPath = 'var/catalog.sqlite'; // Default path
        $catalog = $this->catalogService->getEligibleSpecies($dbPath);
        
        $identifiers = [];
        foreach ($catalog as $species) {
            // Use species name (normalized to lowercase for identifier)
            // Catalog returns display_name, but we need the species_name for PokeAPI
            $speciesName = strtolower($species['name']);
            $identifiers[] = MonsterIdentifier::fromString($speciesName);
        }
        
        return $identifiers;
    }

    //! @brief Create error result
    //! @param message Error message
    //! @param statusCode HTTP status code
    //! @return RouteResult
    private function errorResult(string $message, HttpStatusCode $statusCode): RouteResult
    {
        return new RouteResult(
            TemplateName::TOURNAMENT_SETUP,
            [
                'error' => $message,
                'meta' => [
                    'title' => 'Tournament Error',
                    'description' => $message,
                ],
            ],
            $statusCode
        );
    }
}

