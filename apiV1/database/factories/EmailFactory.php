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
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        return [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'from_email' => $sender->email, // Store sender's email
            'to_email' => $receiver->email, // Store receiver's email
            'subject' => $this->faker->sentence(6),
            'body' => $this->faker->paragraph(3),
        ];
    }
   
}
