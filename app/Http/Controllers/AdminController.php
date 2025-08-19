<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmiProcessingService;
use App\Repositories\LoanRepositoryInterface;

class AdminController extends Controller
{
    protected $emiService;
    protected $loanRepo;

    public function __construct(EmiProcessingService $emiService, LoanRepositoryInterface $loanRepo)
    {
        $this->emiService = $emiService;
        $this->loanRepo = $loanRepo;
    }

    public function index()
    {
        $loans = $this->loanRepo->all();
        $emiDetails = null;
        if (request()->session()->get('show_emi', false)) {
            $emiDetails = $this->emiService->getEmiDetails(); // may be null if table missing
        }
        return view('admin.loan_details', compact('loans', 'emiDetails'));
    }

    public function processData(Request $request)
    {
        $result = $this->emiService->process();
        return redirect()->route('admin.index')->with([
                'status', 'Processed: ' . ($result['processed_count'] ?? 0) . ' rows',
                'show_emi' => true,
            ]
        );
    }
}
