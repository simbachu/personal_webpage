<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Immutable value object representing Pokemon monster data
//!
//! This class encapsulates all the data needed to display a Pokemon monster,
//! providing type safety and clear contracts for the monster data structure.
//! All properties are readonly to ensure immutability.
//!
//! @code
//! // Example usage:
//! $monster = new MonsterData(
//!     id: 25,
//!     name: 'Pikachu',
//!     image: 'https://example.com/pikachu.png',
//!     type1: 'electric',
//!     type2: null
//! );
//!
//! echo $monster->name; // "Pikachu"
//! echo $monster->isDualType(); // false
//! $array = $monster->toArray(); // Convert to template format
//! @endcode
final class MonsterData
{
    //! @brief Construct a new MonsterData instance
    //! @param id The Pokemon's unique ID number
    //! @param name The Pokemon's name in title case (e.g., "Pikachu")
    //! @param image The URL to the Pokemon's official artwork image
    //! @param type1 The Pokemon's primary type (e.g., "electric", "fire")
    //! @param type2 The Pokemon's secondary type, null if single-type
    //! @param precursor Evolution precursor data (what this Pokemon evolves from)
    //! @param successors Array of evolution successor data (what this Pokemon evolves into)
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $image,
        public readonly string $type1,
        public readonly ?string $type2 = null,
        public readonly ?EvolutionData $precursor = null,
        public readonly array $successors = []
    ) {}

    //! @brief Check if this Pokemon has a secondary type
    //! @return bool True if this Pokemon has both a primary and secondary type, false otherwise
    public function isDualType(): bool
    {
        return $this->type2 !== null;
    }

    //! @brief Check if this Pokemon has any evolution data
    //! @return bool True if this Pokemon has a precursor (evolves from something) or successors (evolves into something)
    public function hasEvolutionData(): bool
    {
        return $this->precursor !== null || !empty($this->successors);
    }

    //! @brief Get all types for this Pokemon as an array
    //! @return string[] Array containing the primary type, and secondary type if present
    public function getTypes(): array
    {
        $types = [$this->type1];
        if ($this->type2 !== null) {
            $types[] = $this->type2;
        }
        return $types;
    }

    //! @brief Convert this MonsterData to an array format suitable for template rendering
    //! @return array{id: int, name: string, image: string, type1: string, type2?: string, precursor?: array, successors?: array} Array representation with all monster data
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'type1' => $this->type1,
        ];

        if ($this->type2 !== null) {
            $data['type2'] = $this->type2;
        }

        if ($this->precursor !== null) {
            $data['precursor'] = $this->precursor->toArray();
        }

        if (!empty($this->successors)) {
            if (count($this->successors) === 1) {
                $data['successor'] = $this->successors[0]->toArray();
            } else {
                $data['successors'] = array_map(fn(EvolutionData $evo) => $evo->toArray(), $this->successors);
            }
        }

        return $data;
    }

    //! @brief Create a MonsterData instance from array data (for backward compatibility)
    //! @param data Array data containing monster information
    //! @return self New MonsterData instance created from the array data
    public static function fromArray(array $data): self
    {
        $precursor = null;
        if (isset($data['precursor']) && is_array($data['precursor'])) {
            $precursor = EvolutionData::fromArray($data['precursor']);
        }

        $successors = [];
        if (isset($data['successor']) && is_array($data['successor'])) {
            $successors = [EvolutionData::fromArray($data['successor'])];
        } elseif (isset($data['successors']) && is_array($data['successors'])) {
            $successors = array_map(
                fn(array $evo) => EvolutionData::fromArray($evo),
                $data['successors']
            );
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            name: (string)($data['name'] ?? ''),
            image: (string)($data['image'] ?? ''),
            type1: (string)($data['type1'] ?? ''),
            type2: isset($data['type2']) ? (string)$data['type2'] : null,
            precursor: $precursor,
            successors: $successors
        );
    }
}
