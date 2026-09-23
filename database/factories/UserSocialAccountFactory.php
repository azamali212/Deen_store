<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSocialAccount>
 */
final class UserSocialAccountFactory extends Factory
{
    protected $model = UserSocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_user_id' => (string) fake()->unique()->numerify('##################'),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
