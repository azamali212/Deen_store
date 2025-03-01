<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\Email_Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Email>
 */
class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory()->create()->id, // Get the actual user ID
        'receiver_id' => User::factory()->create()->id,
            'subject' => $this->faker->sentence(6),
            'body' => $this->faker->paragraph(3),
        ];
    }
   
}
