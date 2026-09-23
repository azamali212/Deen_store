<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Enums\AddressType;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
final class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AddressType::HOME,
            'is_default' => false,
            'label' => 'Home',
            'recipient_name' => fake()->name(),
            'phone' => '+9230'.fake()->numerify('#########'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'state' => null,
            'postal_code' => fake()->postcode(),
            'country_code' => 'PK',
            'latitude' => null,
            'longitude' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
