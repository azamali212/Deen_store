<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Data\CountryDocumentMap;
use App\Domain\Seller\Enums\SellerDocumentType;

/**
 * A3 — the one place that answers "what does a seller in THIS country have
 * to upload, and have they done it yet".
 *
 * Everything here reads CountryDocumentMap; nothing hard-codes a country.
 */
final class SellerDocumentRequirements
{
    /**
     * @return array<string, array<int, array<int, SellerDocumentType>>>
     */
    public function groupsFor(string $country): array
    {
        return CountryDocumentMap::for($country);
    }

    /**
     * Every type this country might ask for, across all options. Used to
     * refuse an upload of something the country never needs — a CNIC from
     * a seller in Germany.
     *
     * @return array<int, string>
     */
    public function allowedTypes(string $country): array
    {
        $types = [];

        foreach ($this->groupsFor($country) as $options) {
            foreach ($options as $option) {
                foreach ($option as $type) {
                    $types[$type->value] = true;
                }
            }
        }

        return array_keys($types);
    }

    public function allows(string $country, SellerDocumentType $type): bool
    {
        return in_array($type->value, $this->allowedTypes($country), strict: true);
    }

    /**
     * Groups with no complete option yet. A group is satisfied as soon as
     * ANY ONE of its options is fully uploaded (P10-3), so a seller who
     * gave a passport is done even though the driving-licence option is
     * untouched.
     *
     * @param  array<int, string>  $uploaded  document_type values
     * @return array<int, string>  group keys, e.g. ['identity', 'tax']
     */
    public function missingGroups(string $country, array $uploaded): array
    {
        $missing = [];

        foreach ($this->groupsFor($country) as $group => $options) {
            if ($this->chosenOption($options, $uploaded) === null) {
                $missing[] = $group;
            }
        }

        return $missing;
    }

    /**
     * What the seller still has to upload to finish the cheapest route —
     * the option they are closest to completing. This is what the API
     * shows as "still needed", so it must name documents, not groups.
     *
     * @param  array<int, string>  $uploaded
     * @return array<int, string>  document_type values
     */
    public function missingTypes(string $country, array $uploaded): array
    {
        $missing = [];

        foreach ($this->groupsFor($country) as $options) {
            if ($this->chosenOption($options, $uploaded) !== null) {
                continue;
            }

            // Closest option first — and "closest" needs TWO measures, not
            // one. Fewest-left alone ties a one-document option nobody has
            // touched against a two-document option already half done, and
            // the first one declared wins. On GB that meant uploading the
            // front of a driving licence and being told to go and get a
            // passport (C68).
            //
            // So: least left to do, and on a tie the option with the most
            // already uploaded. Someone who has started a route gets told
            // how to finish THAT route.
            $best = null;
            $bestGap = PHP_INT_MAX;
            $bestProgress = -1;

            foreach ($options as $option) {
                $values = array_map(
                    fn (SellerDocumentType $t): string => $t->value,
                    $option,
                );

                $gap = array_values(array_filter(
                    $values,
                    fn (string $value): bool => ! in_array($value, $uploaded, strict: true),
                ));

                $progress = count($values) - count($gap);

                $closer = count($gap) < $bestGap;
                $equalButStarted = count($gap) === $bestGap && $progress > $bestProgress;

                if ($closer || $equalButStarted) {
                    $bestGap = count($gap);
                    $bestProgress = $progress;
                    $best = $gap;
                }
            }

            foreach ($best ?? [] as $value) {
                $missing[] = $value;
            }
        }

        return $missing;
    }

    /**
     * The identity documents the seller actually used, or null when the
     * identity group is not complete. Layer 2 needs this to know whether
     * there is a back side to compare at all (C53).
     *
     * @param  array<int, string>  $uploaded
     * @return array<int, SellerDocumentType>|null
     */
    public function identityOptionUsed(string $country, array $uploaded): ?array
    {
        $options = $this->groupsFor($country)['identity'] ?? [];

        return $this->chosenOption($options, $uploaded);
    }

    /**
     * Every type this application actually requires, given what has been
     * uploaded. C54 — Layer 2 loops over THIS, never over the whole enum:
     * with per-country rules most types are legitimately absent, and
     * checking for all of them would switch the cross-checks off for
     * every seller on earth.
     *
     * @param  array<int, string>  $uploaded
     * @return array<int, SellerDocumentType>
     */
    public function requiredTypes(string $country, array $uploaded): array
    {
        $required = [];

        foreach ($this->groupsFor($country) as $options) {
            $chosen = $this->chosenOption($options, $uploaded) ?? $options[0] ?? [];

            foreach ($chosen as $type) {
                $required[] = $type;
            }
        }

        return $required;
    }

    /**
     * @param  array<int, array<int, SellerDocumentType>>  $options
     * @param  array<int, string>  $uploaded
     * @return array<int, SellerDocumentType>|null
     */
    private function chosenOption(array $options, array $uploaded): ?array
    {
        foreach ($options as $option) {
            $complete = true;

            foreach ($option as $type) {
                if (! in_array($type->value, $uploaded, strict: true)) {
                    $complete = false;

                    break;
                }
            }

            if ($complete) {
                return $option;
            }
        }

        return null;
    }
}
