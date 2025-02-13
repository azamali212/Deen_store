<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['name', 'content'];

    // You can fetch the template content by its name
    public static function getTemplateByName($name)
    {
        return self::where('name', $name)->first();
    }
}
