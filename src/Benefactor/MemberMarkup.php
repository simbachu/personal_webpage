<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief Formats entitled patrons as copyable HTML spans keyed by highest tier
final class MemberMarkup
{
    //! @brief Format members into markup and structured preview data
    //! @param members Campaign members
    //! @return array{markup: string, patrons: list<array{class: string, name: string}>}
    public function format(array $members): array
    {
        $patrons = [];
        foreach ($members as $member) {
            if ($member->patronStatus !== 'active_patron') {
                continue;
            }

            $highest = $this->highestTier($member->tiers);
            if ($highest === null) {
                continue;
            }

            $className = $this->slug($highest->title);
            if ($className === '') {
                continue;
            }

            $patrons[] = [
                'class' => $className,
                'name' => $member->fullName,
                'amountCents' => $highest->amountCents,
            ];
        }

        usort($patrons, static function (array $left, array $right): int {
            $amountOrder = $right['amountCents'] <=> $left['amountCents'];
            if ($amountOrder !== 0) {
                return $amountOrder;
            }
            return strcasecmp($left['name'], $right['name']);
        });

        $spans = [];
        $preview = [];
        foreach ($patrons as $patron) {
            $preview[] = [
                'class' => $patron['class'],
                'name' => $patron['name'],
            ];
            $spans[] = sprintf(
                '<span class="%s">%s</span>',
                htmlspecialchars($patron['class'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                htmlspecialchars($patron['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
        }

        return [
            'markup' => implode(', ', $spans),
            'patrons' => $preview,
        ];
    }

    //! @brief Pick the entitled tier with the highest amount
    //! @param tiers Entitled tiers
    //! @return PatronTier|null
    private function highestTier(array $tiers): ?PatronTier
    {
        $highest = null;
        foreach ($tiers as $tier) {
            if ($highest === null || $tier->amountCents > $highest->amountCents) {
                $highest = $tier;
            }
        }
        return $highest;
    }

    //! @brief Slug a tier title into a CSS class (Hardy Club → hardy-club)
    //! @param title Tier title
    //! @return string
    private function slug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}
