<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory;

    // Add all the relevant fields to the $fillable array
    protected $fillable = ['supplier_id', 'amount', 'payment_method', 'status'];

    /**
     * A payment belongs to a supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
