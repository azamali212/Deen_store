<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'from_email',
        'to_email',
        'subject',
        'body',
    ];
    protected $dates = ['deleted_at'];

    public function isTrashed(): bool
    {
        return $this->trashed();
    }
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