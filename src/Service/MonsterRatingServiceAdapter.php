<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\Result;
use App\Type\MonsterIdentifier;

//! @brief Adapter to make MonsterRatingService compatible with PokemonOpinionService interface
//!
//! This adapter allows gradual migration from PokemonOpinionService to MonsterRatingService
//! by providing the same interface while using the repository pattern internally.
//!
//! @code
//! // Drop-in replacement for PokemonOpinionService
//! $ratingService = new MonsterRatingService();
//! $adapter = new MonsterRatingServiceAdapter($ratingService);
//!
//! // Use exactly like PokemonOpinionService
//! $result = $adapter->getOpinion($identifier);
//! @endcode
final class MonsterRatingServiceAdapter
{
    private MonsterRatingService $ratingService;

    //! @brief Construct a new MonsterRatingServiceAdapter instance
    //! @param ratingService The MonsterRatingService to adapt
    public function __construct(MonsterRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    //! @brief Get opinion data for a Pokemon by identifier
    //! @param identifier MonsterIdentifier to look up
    //! @return Result<array{opinion:string,rating:string}> Compatible with PokemonOpinionService
    public function getOpinion(MonsterIdentifier $identifier): Result
    {
        $result = $this->ratingService->getRating($identifier);

        if ($result->isFailure()) {
            return Result::failure($result->getError());
        }

        $rating = $result->getValue();
        return Result::success([
            'opinion' => $rating->opinion,
            'rating' => $rating->rating,
        ]);
    }

    //! @brief Check if an opinion exists for a Pokemon
    //! @param identifier MonsterIdentifier to check
    //! @return bool True if an opinion exists for the Pokemon
    public function hasOpinion(MonsterIdentifier $identifier): bool
    {
        return $this->ratingService->hasRating($identifier);
    }

    //! @brief Get all available opinion names (species names)
    //! @return array List of all species names that have opinions
    public function getAllOpinionNames(): array
    {
        return $this->ratingService->getAllRatingNames();
    }

    //! @brief Get the underlying MonsterRatingService for advanced usage
    //! @return MonsterRatingService The wrapped rating service instance
    public function getRatingService(): MonsterRatingService
    {
        return $this->ratingService;
    }
}
