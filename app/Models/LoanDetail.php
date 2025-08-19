<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDetail extends Model
{
    /**
     * Primary key for the model.
     * @var string
     */
    protected $primaryKey = 'entity_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'clientid',
        'num_of_payment',
        'first_payment_date',
        'last_payment_date',
        'loan_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
    return [
        'first_payment_date' => 'date',
        'last_payment_date' => 'date',
        'loan_amount' => 'decimal:2',
    ];
    }
}
