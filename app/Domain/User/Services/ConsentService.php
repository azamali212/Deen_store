<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Enums\ConsentType;
use App\Domain\User\Events\ConsentGranted;
use App\Domain\User\Events\ConsentWithdrawn;
use App\Domain\User\Exceptions\ConsentNotWithdrawableException;
use App\Models\User;
use App\Models\UserConsent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * P12-5 — the consent ledger, and the only thing allowed to write to it.
 *
 * Every method here either inserts a row or closes one. Nothing updates a
 * `version` and nothing deletes. If that rule is ever broken the table stops
 * being evidence and becomes a settings screen, which is exactly what a
 * regulator asking "prove you were allowed to email this person in March"
 * cannot use.
 */
final readonly class ConsentService
{
    /**
     * Idempotent. Agreeing again to wording you have already agreed to
     * returns the row you already have — it does not pile up duplicates
     * every time the app re-sends the checkbox state.
     */
    public function grant(
        User $user,
        ConsentType $type,
        ?string $version = null,
    ): UserConsent {

        $version ??= $type->currentVersion();

        return DB::transaction(function () use ($user, $type, $version): UserConsent {

            $active = $this->activeFor($user, $type);

            if ($active !== null && $active->version === $version) {
                return $active;
            }

            // Same consent, newer wording. The old row is closed as
            // SUPERSEDED, not withdrawn — the user never said no, the
            // document changed under them, and the trail must say which.
            $active?->forceFill(['superseded_at' => CarbonImmutable::now()])->save();

            [$ip, $agent] = $this->circumstances();

            $consent = UserConsent::query()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'version' => $version,
                'granted_at' => CarbonImmutable::now(),
                'ip_address' => $ip,
                'user_agent' => $agent,
            ]);

            event(new ConsentGranted($consent));

            return $consent;
        });
    }

    /**
     * Returns null when there was nothing to withdraw — withdrawing twice is
     * not an error, it is the same outcome asked for twice.
     */
    public function withdraw(User $user, ConsentType $type): ?UserConsent
    {
        if (! $type->isWithdrawable()) {
            throw ConsentNotWithdrawableException::forType($type);
        }

        return DB::transaction(function () use ($user, $type): ?UserConsent {

            $active = $this->activeFor($user, $type);

            if ($active === null) {
                return null;
            }

            $active->forceFill(['withdrawn_at' => CarbonImmutable::now()])->save();

            event(new ConsentWithdrawn($active));

            return $active;
        });
    }

    public function activeFor(User $user, ConsentType $type): ?UserConsent
    {
        return UserConsent::query()
            ->where('user_id', $user->id)
            ->where('type', $type->value)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Every consent type with its current state, including the ones never
     * answered — a client rendering a privacy screen needs the full list,
     * not only the rows that happen to exist.
     *
     * @return list<array<string, mixed>>
     */
    public function current(User $user): array
    {
        /** @var Collection<int, UserConsent> $rows */
        $rows = UserConsent::query()
            ->where('user_id', $user->id)
            ->active()
            ->get()
            ->keyBy(static fn (UserConsent $consent): string => $consent->type->value);

        return array_map(
            function (ConsentType $type) use ($rows): array {
                $row = $rows->get($type->value);

                return [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'required' => $type->isRequired(),
                    'withdrawable' => $type->isWithdrawable(),
                    'granted' => $row !== null,
                    'version' => $row?->version,
                    'current_version' => $type->currentVersion(),
                    'needs_reconsent' => $row !== null && $row->version !== $type->currentVersion(),
                    'granted_at' => $row?->granted_at?->toIso8601String(),
                ];
            },
            ConsentType::cases(),
        );
    }

    /**
     * The full trail, newest first. This is what gets handed over when
     * someone has to answer for a decision made two years ago.
     *
     * @return Collection<int, UserConsent>
     */
    public function history(User $user): Collection
    {
        return UserConsent::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function circumstances(): array
    {
        $request = request();

        // Not runningInConsole(). Under PHPUnit the SAPI is always `cli`, so
        // that check would report "console" for a perfectly real HTTP request
        // and quietly record every test consent with no IP — which is how a
        // column ends up empty in production too. A matched ROUTE is the thing
        // that actually distinguishes the two: an artisan command has none.
        if ($request->route() === null) {
            return [null, null];
        }

        return [
            $request->ip(),
            mb_substr((string) $request->userAgent(), 0, 500) ?: null,
        ];
    }
}
