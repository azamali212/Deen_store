<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\Email_Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class EmailStatusFactory extends Factory
{
    protected $model = Email_Status::class;

    public function definition(): array
    {
        return [
            'email_id' => Email::factory(), // Create an email record
            'status' => $this->faker->randomElement(['sent', 'received']),
            'read_status' => $this->faker->randomElement(['read', 'unread']),
            'archive_status' => $this->faker->randomElement(['archived', 'unarchived']),
        ];
    }
}
