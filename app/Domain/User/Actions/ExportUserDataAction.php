<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserDataExported;
use App\Domain\User\Contracts\AvatarStorageInterface;
use App\Models\User;

/**
 * Builds a GDPR-style "download my data" export: everything this domain
 * holds about ONE user — account, profile, addresses, preferences — as a
 * plain array ready to be JSON-encoded.
 *
 * Deliberately scoped to User-domain data only. It does NOT include
 * password hashes, 2FA secrets/recovery codes, or raw session/login-log
 * rows — those are security material, not personal data the user needs
 * handed back to them, and returning them in a downloadable file would be
 * a real security risk if that file were ever intercepted or misplaced.
 * (Auth-domain activity history — login logs, sessions — could be added
 * as a later, separate export if that's ever needed.)
 */
final readonly class ExportUserDataAction
{
    public function __construct(
        private GetProfileAction $getProfile,
        private ListAddressesAction $listAddresses,
        private GetPreferencesAction $getPreferences,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $profile = $this->getProfile->execute($user->id);
        $addresses = $this->listAddresses->execute($user->id);
        $preferences = $this->getPreferences->execute($user->id);

        $export = [
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
                'status' => $user->status->value,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $profile === null ? null : [
                'username' => $profile->username,
                'date_of_birth' => $profile->date_of_birth,
                'gender' => $profile->gender?->value,
                'bio' => $profile->bio,
                'avatar_url' => $profile->avatar_path !== null
                    ? app(AvatarStorageInterface::class)->url($profile->avatar_path)
                    : null,
                'website_url' => $profile->website_url,
                'occupation' => $profile->occupation,
                'company_name' => $profile->company_name,
                'country_code' => $profile->country_code,
                'timezone' => $profile->timezone,
                'locale' => $profile->locale,
                'profile_visibility' => $profile->profile_visibility?->value,
            ],
            'addresses' => $addresses->map(static fn ($address): array => [
                'type' => $address->type->value,
                'is_default' => $address->is_default,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
                'country_code' => $address->country_code,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
            ])->values()->all(),
            'preferences' => $preferences === null ? null : [
                'language' => $preferences->language,
                'currency' => $preferences->currency,
                'timezone' => $preferences->timezone,
                'theme' => $preferences->theme,
                'email_notifications' => $preferences->email_notifications,
                'sms_notifications' => $preferences->sms_notifications,
                'push_notifications' => $preferences->push_notifications,
                'marketing_notifications' => $preferences->marketing_notifications,
            ],
        ];

        event(new UserDataExported($user));

        return $export;
    }
}
