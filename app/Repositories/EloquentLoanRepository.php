<?php

namespace App\Repositories;

use App\Models\LoanDetail;
use Illuminate\Support\Facades\DB;

class EloquentLoanRepository implements LoanRepositoryInterface
{
    public function all()
    {
        return LoanDetail::all();
    }

    public function getMinFirstPaymentDate()
    {
        return LoanDetail::min('first_payment_date');
    }

    public function getMaxLastPaymentDate()
    {
        return LoanDetail::max('last_payment_date');
    }
}
