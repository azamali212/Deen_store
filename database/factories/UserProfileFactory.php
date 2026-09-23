<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Enums\Gender;
use App\Domain\User\Enums\ProfileVisibility;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
final class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->userName(),
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases()),
            'bio' => fake()->sentence(),
            'avatar_path' => null,
            'avatar_provider' => 'local',
            'website_url' => fake()->url(),
            'occupation' => fake()->jobTitle(),
            'company_name' => fake()->company(),
            'country_code' => 'PK',
            'timezone' => 'Asia/Karachi',
            'locale' => 'en',
            'profile_visibility' => ProfileVisibility::PUBLIC,
            'profile_completion' => 100,
        ];
    }
}
