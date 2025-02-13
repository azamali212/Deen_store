<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    use HasFactory;

    protected $fillable = ['user_id', 'email', 'template_name', 'sent_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
