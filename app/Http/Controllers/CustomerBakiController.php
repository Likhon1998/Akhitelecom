<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\BakiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerBakiController extends Controller
{
    public function __construct(
        protected BakiService $baki,
    ) {}

    public function index()
    {
        $shopId = Auth::user()->shop_id;

        $customers = Customer::where('shop_id', $shopId)
            ->where('baki_balance', '>', 0)
            ->orderByDesc('baki_balance')
            ->get();

        $totalOutstanding = (float) $customers->sum('baki_balance');

        return view('customers.baki-index', compact('customers', 'totalOutstanding'));
    }

    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $entries = $customer->bakiEntries()
            ->with(['order:id,invoice_no', 'user:id,name'])
            ->latest('id')
            ->paginate(40);

        return view('customers.baki-show', compact('customer', 'entries'));
    }

    public function pay(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', Rule::in(['cash', 'card', 'bkash'])],
            'note' => 'nullable|string|max:255',
        ]);

        $amount = round((float) $validated['amount'], 2);
        $balance = round((float) $customer->baki_balance, 2);

        if ($amount > $balance + 0.009) {
            return back()->withInput()->with('error', 'Payment cannot exceed baki balance (৳'.number_format($balance, 2).').');
        }

        try {
            $user = Auth::user();
            $counterId = $user->counter_id ?: ($user->isAdminUser() ? $user->adminOpenPosSession()?->counter_id : null);

            $cash = $card = $mobile = null;
            match ($validated['method']) {
                'cash' => $cash = $amount,
                'card' => $card = $amount,
                'bkash' => $mobile = $amount,
            };

            $this->baki->collectPayment(
                customer: $customer,
                amount: $amount,
                method: $validated['method'],
                note: $validated['note'] ?? 'Baki collection',
                order: null,
                userId: $user->id,
                counterId: $counterId ? (int) $counterId : null,
                cash: $cash,
                card: $card,
                mobile: $mobile,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('customers.baki.show', $customer)
            ->with('success', '৳'.number_format($amount, 2).' collected. Remaining baki: ৳'.number_format((float) $customer->fresh()->baki_balance, 2).'.');
    }

    private function authorizeCustomer(Customer $customer): void
    {
        if ((int) $customer->shop_id !== (int) Auth::user()->shop_id) {
            abort(403);
        }
    }
}
