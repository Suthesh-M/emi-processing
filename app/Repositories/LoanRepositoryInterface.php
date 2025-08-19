<?php

namespace App\Repositories;

interface LoanRepositoryInterface
{
    public function all();
    public function getMinFirstPaymentDate();
    public function getMaxLastPaymentDate();
}
