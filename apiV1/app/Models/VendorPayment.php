<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayment extends Model
{
    protected $fillable = ['vendor_id', 'amount', 'transaction_id', 'payment_method', 'status'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}