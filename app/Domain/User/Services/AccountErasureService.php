<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Domain\User\Contracts\AvatarStorageInterface;
use App\Domain\User\Contracts\ErasureBlockerInterface;
use App\Domain\User\Events\AccountErased;
use App\Domain\User\Events\AccountErasureCancelled;
use App\Domain\User\Events\AccountErasureRequested;
use App\Domain\User\Exceptions\ErasureAlreadyRequestedException;
use App\Domain\User\Exceptions\ErasureBlockedException;
use App\Domain\User\Exceptions\NoErasureRequestException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * P12 — GDPR Art. 17, "the right to be forgotten", as it actually has to be
 * built rather than as it reads.
 *
 * Three things shape every decision in this class.
 *
 * 1. Art. 17(3)(b): the right does not override processing required by a
 *    legal obligation. AML record-keeping and tax law both demand years of
 *    retention. So erasure removes what identifies a PERSON and leaves what
 *    the law obliges us to keep — audit rows, consent records, KYC rows past
 *    their own retention clock (Phase 9a already owns that clock).
 *
 * 2. The user row itself is never deleted (C59). Orders, audit entries,
 *    seller records and consent rows all point at users.id. Hard-deleting it
 *    would either break those foreign keys or cascade the deletion straight
 *    through the records we are legally required to keep. So the row stays
 *    and is ANONYMISED in place: whatever remains pointing at it points at
 *    nobody.
 *
 * 3. Erasure is irreversible, so it waits (P12-3). A grace period turns a
 *    stolen session into a recoverable incident instead of a permanent one.
 */
final readonly class AccountErasureService
{
    public function __construct(
        private AvatarStorageInterface $avatars,
    ) {}

    // ---------------------------------------------------------------- request

    public function request(User $user): User
    {
        if ($user->erased_at !== null) {
            throw ErasureAlreadyRequestedException::alreadyErased();
        }

        if ($user->erasure_requested_at !== null) {
            throw ErasureAlreadyRequestedException::on(
                $user->erasure_requested_at,
                $this->scheduledFor($user) ?? CarbonImmutable::now(),
            );
        }

        $reasons = $this->blockingReasons($user);

        if ($reasons !== []) {
            throw ErasureBlockedException::because($reasons);
        }

        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()])->save();

        $scheduledFor = $this->scheduledFor($user);

        // Fired here, while the address is still valid. Nothing can be sent
        // AFTER the sweep runs — by then there is no address to send to
        // (C65). This is the last message this account will ever receive.
        event(new AccountErasureRequested($user, $scheduledFor));

        return $user;
    }

    public function cancel(User $user, bool $byUser = true, ?string $reason = null): User
    {
        if ($user->erasure_requested_at === null) {
            throw NoErasureRequestException::forUser($user->id);
        }

        $user->forceFill(['erasure_requested_at' => null])->save();

        event(new AccountErasureCancelled($user, $byUser, $reason));

        return $user;
    }

    // ----------------------------------------------------------------- status

    public function scheduledFor(User $user): ?CarbonInterface
    {
        if ($user->erasure_requested_at === null) {
            return null;
        }

        return CarbonImmutable::parse($user->erasure_requested_at)
            ->addDays($this->graceDays());
    }

    /**
     * @return list<string>
     */
    public function blockingReasons(User $user): array
    {
        $reasons = [];

        foreach ($this->blockers() as $blocker) {
            $reason = $blocker->reasonToBlock($user);

            if ($reason !== null) {
                $reasons[] = $reason;
            }
        }

        return $reasons;
    }

    // ------------------------------------------------------------- the sweep

    /**
     * Accounts whose grace period has run out. Erased ones are excluded by
     * erased_at, which is why that column exists at all.
     *
     * withTrashed(): a user who soft-deleted their account and THEN asked to
     * be erased is exactly the person this sweep is for.
     *
     * @return Collection<int, User>
     */
    public function due(?CarbonInterface $asOf = null): Collection
    {
        $cutoff = CarbonImmutable::parse($asOf ?? CarbonImmutable::now())
            ->subDays($this->graceDays());

        return User::withTrashed()
            ->whereNotNull('erasure_requested_at')
            ->whereNull('erased_at')
            ->where('erasure_requested_at', '<=', $cutoff)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{erased: int, cancelled: int, failed: int}
     */
    public function processDue(?CarbonInterface $asOf = null): array
    {
        $erased = 0;
        $cancelled = 0;
        $failed = 0;

        foreach ($this->due($asOf) as $user) {
            try {
                // C63 — re-check at the last moment. Fourteen days is long
                // enough for someone to open a store after asking to be
                // erased. Rather than leaving the request stuck forever,
                // cancel it and tell them why; they can ask again once the
                // blocker is gone.
                $reasons = $this->blockingReasons($user);

                if ($reasons !== []) {
                    $this->cancel($user, byUser: false, reason: $reasons[0]);
                    $cancelled++;

                    continue;
                }

                $this->erase($user);
                $erased++;
            } catch (\Throwable $e) {
                $failed++;

                // Never the email, never the name — logging them here would
                // leak into log files the erasure was supposed to clear.
                Log::error('Account erasure failed.', [
                    'user_id' => $user->id,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'erased' => $erased,
            'cancelled' => $cancelled,
            'failed' => $failed,
        ];
    }

    // ------------------------------------------------------------- the erasure

    /**
     * The actual anonymisation. One transaction: either the account is
     * fully erased or nothing moved. A half-erased account — profile gone,
     * email still there — is the worst possible outcome, because it looks
     * done and is not.
     */
    public function erase(User $user): User
    {
        return DB::transaction(function () use ($user): User {

            // File first, row second — same ordering as the KYC retention
            // sweep (C43). If the disk delete throws, the transaction rolls
            // back and the row still points at the file, so the next run
            // tries again. The reverse order would orphan the file forever.
            $profile = $user->profile()->withTrashed()->first();

            if ($profile?->avatar_path !== null) {
                $this->avatars->delete($profile->avatar_path);
            }

            // forceDelete on the profile: user_profiles soft-deletes, and a
            // soft-deleted profile is not erased, it is merely hidden.
            $user->profile()->withTrashed()->forceDelete();

            $user->addresses()->delete();
            $user->preferences()->delete();
            $user->socialAccounts()->delete();

            // Security material and live access. Nothing here is evidence of
            // anything; it is all a way back into the account.
            $user->activeSessions()->delete();
            $user->trustedDevices()->delete();
            $user->loginOtps()->delete();
            $user->twoFactorRecoveryCodes()->delete();
            $user->tokens()->delete();

            // Login logs are deliberately KEPT. They are security records
            // with their own basis, and once the user row is anonymous the
            // rows are no longer about an identified person.

            $user->forceFill([
                'name' => 'Deleted user',

                // C62 — email is UNIQUE and NOT NULL, so it cannot simply be
                // nulled. It gets a per-account placeholder on the .invalid
                // TLD, which RFC 6761 reserves precisely so it can never
                // resolve or receive mail. The uuid keeps it unique without
                // saying anything about the person.
                'email' => sprintf('erased-%s@erased.invalid', $user->uuid),
                'email_verified_at' => null,

                'phone' => null,
                'phone_verified_at' => null,

                // Not a hash of anything anybody knows — the account must be
                // unreachable even by whoever held the old password.
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,

                'status' => UserAccountStatus::INACTIVE,

                'two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_provider' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_verified_at' => null,

                'last_login_ip' => null,
                'lock_reason' => null,

                'erased_at' => CarbonImmutable::now(),
            ])->save();

            // An erased account holds no permissions. Spatie stores these in
            // a pivot keyed on the user id, which survives everything above.
            $user->syncRoles([]);
            $user->syncPermissions([]);

            if ($user->deleted_at === null) {
                $user->delete();
            }

            // IDs only. See the comment on the event itself (C64).
            event(new AccountErased($user->id, (string) $user->uuid));

            return $user;
        });
    }

    // ------------------------------------------------------------------ config

    private function graceDays(): int
    {
        return max(0, (int) config('privacy.erasure_grace_days', 14));
    }

    /**
     * @return list<ErasureBlockerInterface>
     */
    private function blockers(): array
    {
        $blockers = [];

        /** @var list<class-string> $configured */
        $configured = (array) config('privacy.erasure_blockers', []);

        foreach ($configured as $class) {
            $blocker = app($class);

            if ($blocker instanceof ErasureBlockerInterface) {
                $blockers[] = $blocker;
            }
        }

        return $blockers;
    }
}
