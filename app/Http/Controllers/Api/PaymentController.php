<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\FirestoreVipService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Direct Visa/Mastercard payments via PayPal's Advanced Card Payments —
 * see PayPalService's class doc for the full explanation of how this
 * avoids redirecting to paypal.com, and the PCI-DSS note on what that
 * requires of this server.
 *
 * VIP plans are hardcoded here rather than user-supplied (never trust the
 * client to say what the price should be) — one plan for now, easy to
 * extend into an array if more tiers get added later.
 */
class PaymentController extends Controller
{
    private const PLANS = [
        'vip_30_days' => ['days' => 30, 'amount' => 4.99, 'currency' => 'USD', 'label' => 'VIP — 30 days'],
    ];

    public function __construct(
        private readonly PayPalService $payPal,
        private readonly FirestoreVipService $vip,
    ) {
    }

    public function payWithCard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uid' => ['required', 'string', 'max:191'],
            'plan' => ['required', 'string', 'in:' . implode(',', array_keys(self::PLANS))],
            'card_number' => ['required', 'string', 'regex:/^[0-9]{13,19}$/'],
            'expiry_month' => ['required', 'integer', 'between:1,12'],
            'expiry_year' => ['required', 'integer', 'min:' . date('Y')],
            'cvv' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
            'cardholder_name' => ['required', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = $request->input('uid');
        $planKey = $request->input('plan');
        $plan = self::PLANS[$planKey];

        $expiryMonth = str_pad((string) $request->input('expiry_month'), 2, '0', STR_PAD_LEFT);
        $expiryYear = (string) $request->input('expiry_year');
        if (strtotime("{$expiryYear}-{$expiryMonth}-01") < strtotime(date('Y-m-01'))) {
            return response()->json(['success' => false, 'errors' => ['expiry_month' => ['This card has expired.']]], 422);
        }

        try {
            $result = $this->payPal->createAndCaptureCardOrder(
                card: [
                    'number' => $request->input('card_number'),
                    'expiry' => "{$expiryYear}-{$expiryMonth}",
                    'security_code' => $request->input('cvv'),
                    'name' => $request->input('cardholder_name'),
                ],
                amount: $plan['amount'],
                currency: $plan['currency'],
                description: $plan['label'],
            );
        } catch (RuntimeException $e) {
            // $e's message is built by PayPalService to never include raw
            // card data — safe to log and to (trimmed) return to the client.
            Log::warning("PayPal payment failed for uid={$uid}, plan={$planKey}: {$e->getMessage()}");
            Payment::create([
                'uid' => $uid,
                'plan' => $planKey,
                'amount' => $plan['amount'],
                'currency' => $plan['currency'],
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 402);
        }

        $completed = $result['status'] === 'COMPLETED';

        Payment::create([
            'uid' => $uid,
            'plan' => $planKey,
            'amount' => $plan['amount'],
            'currency' => $plan['currency'],
            'paypal_order_id' => $result['orderId'],
            'status' => $completed ? 'completed' : 'failed',
            'failure_reason' => $completed ? null : "PayPal order status: {$result['status']}",
        ]);

        if (!$completed) {
            return response()->json([
                'success' => false,
                'message' => "Payment did not complete (status: {$result['status']}).",
            ], 402);
        }

        try {
            $this->vip->grantVip($uid, $plan['days']);
        } catch (RuntimeException $e) {
            // The charge already succeeded — do NOT tell the client this
            // failed (they were charged). Log loudly so this gets a manual
            // fix, and still report success since the payment itself did work.
            Log::error("Payment succeeded but VIP grant failed for uid={$uid}: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'order_id' => $result['orderId'],
            'plan' => $planKey,
            'vip_days' => $plan['days'],
        ]);
    }
}
