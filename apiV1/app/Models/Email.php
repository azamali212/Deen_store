<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'sender_id',
        'receiver_id',
    ];

    /**
     * Get the sender associated with the email.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver associated with the email.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the status of the email.
     */
    public function status()
    {
        return $this->hasOne(Email_Status::class, 'email_id'); // The correct foreign key is 'email_id'
    }
}