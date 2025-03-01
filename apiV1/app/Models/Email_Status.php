<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email_Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'status',
        'read_status',
        'archive_status',
    ];

    /**
     * Get the email associated with the status.
     */
    public function email()
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}