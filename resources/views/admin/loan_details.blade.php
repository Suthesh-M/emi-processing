@extends('layouts.app')

@section('content')
    <div class="card p-3">
        <h3>Loan Details</h3>
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Client ID</th>
                    <th>No of Payments</th>
                    <th>First Payment Date</th>
                    <th>Last Payment Date</th>
                    <th>Loan Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loans as $loan)
                    <tr>
                        <td>{{ $loan->clientid }}</td>
                        <td>{{ $loan->num_of_payment }}</td>
                        <td>{{ Str::of($loan->first_payment_date)->before(' ') }}</td>
                        <td>{{ Str::of($loan->last_payment_date)->before(' ') }}</td>
                        <td>{{ number_format($loan->loan_amount,2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <form method="POST" action="{{ route('admin.process') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Process Data</button>
        </form>
    </div>

    <div class="card mt-4 p-3">
        <h3>EMI Details</h3>

        {{-- If user didn't click Process Data, don't fetch or show EMI table --}}
        @if(!session('show_emi'))
            <div class="text-muted">EMI details will be displayed after you click <strong>Process Data</strong>.</div>

        {{-- If we asked for EMI but table not present or empty --}}
        @elseif(!$emiDetails)
            <div class="text-muted">No EMI data found after processing. If you expected results, try processing again or check logs.</div>

        {{-- Show the table when emiDetails exists --}}
        @else
            <div style="overflow:auto">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            @foreach(array_keys($emiDetails[0]) as $col)
                                <th>{{ $col === 'clientid' ? 'Client_ID' : $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emiDetails as $row)
                            <tr>
                                @foreach($row as $col => $val)
                                    <td>{{ (is_numeric($val) && $col !== 'clientid' ? number_format($val,2) : $val) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
