<?php

namespace App\Services;

use App\Repositories\LoanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmiProcessingService
{
    protected $loanRepo;

    public function __construct(LoanRepositoryInterface $loanRepo)
    {
        $this->loanRepo = $loanRepo;
    }

    public function process()
    {
        $minDate = $this->loanRepo->getMinFirstPaymentDate();
        $maxDate = $this->loanRepo->getMaxLastPaymentDate();

        if (!$minDate || !$maxDate) {
            return ['error' => 'No loan data'];
        }

        $start = Carbon::parse($minDate)->startOfMonth();
        $end = Carbon::parse($maxDate)->startOfMonth();

        // build months list inclusive
        $months = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $months[] = $cursor->format('Y_M'); // e.g. 2019_Feb
            $cursor->addMonth();
        }

        // Build CREATE TABLE raw SQL
        $columnsSql = [];
        $columnsSql[] = '`clientid` INT NOT NULL';
        foreach ($months as $m) {
            // backticked column name because it starts with digit
            $columnsSql[] = "`$m` DECIMAL(15,2) NOT NULL DEFAULT 0.00";
        }
        $columnsSql[] = 'PRIMARY KEY (`clientid`)';
        $createSql = "DROP TABLE IF EXISTS `emi_details`;";
        $createSql .= " CREATE TABLE `emi_details` (" . implode(',', $columnsSql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        // Execute raw SQL (drop + create)
        DB::unprepared($createSql);

        // Process rows and prepare inserts (use raw insert for each row)
        $loans = $this->loanRepo->all();

        foreach ($loans as $loan) {
            $clientId = $loan->clientid;
            $numPayments = (int)$loan->num_of_payment;
            $loanAmount = (float)$loan->loan_amount;
            $firstPaymentDate = Carbon::parse($loan->first_payment_date)->startOfMonth();

            // Base EMI rounded to 2 decimals
            $baseEmi = round($loanAmount / $numPayments, 2);
            // Recompute remainder and adjust last EMI to make sum exact
            $sumBase = round($baseEmi * $numPayments, 2);
            $remainder = round($loanAmount - $sumBase, 2); // can be negative, zero or positive

            // Build values array keyed by month name
            $rowValues = array_fill_keys($months, '0.00');

            // assign EMIs to num_of_payment consecutive months starting from firstPaymentDate's month
            $monthCursor = $firstPaymentDate->copy();
            for ($i = 0; $i < $numPayments; $i++) {
                $colName = $monthCursor->format('Y_M');
                if (in_array($colName, $months)) {
                    // by default baseEmi; remainder applied to last payment (i == numPayments-1)
                    $value = $baseEmi;
                    if ($i === $numPayments - 1 && $remainder != 0.0) {
                        $value = round($baseEmi + $remainder, 2);
                    }
                    // Sum rounding safe
                    $rowValues[$colName] = number_format($value, 2, '.', '');
                }
                $monthCursor->addMonth();
            }

            // build raw insert SQL
            $columns = array_merge(['clientid'], $months);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $bindings = array_merge([$clientId], array_values($rowValues));
            $insertSql = "INSERT INTO `emi_details` (`" . implode('`,`', $columns) . "`) VALUES ($placeholders)";
            DB::insert($insertSql, $bindings);
        }

        return [
            'months' => $months,
            'processed_count' => $loans->count()
        ];
    }

    /**
     * Return emi_details data for display (if table exists)
     */
    public function getEmiDetails()
    {
        if (!DB::select("SHOW TABLES LIKE 'emi_details'")) {
            return null;
        }
        $rows = DB::select("SELECT * FROM `emi_details` ORDER BY `clientid` ASC");
        
        return json_decode(json_encode($rows), true);
    }
}
