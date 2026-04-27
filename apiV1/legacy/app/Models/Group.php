<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name','description'];

    // Define relationship with Coupons
    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }
}