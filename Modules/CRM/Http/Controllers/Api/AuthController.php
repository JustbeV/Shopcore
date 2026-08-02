<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CRM\Http\Requests\LoginCustomerRequest;
use Modules\CRM\Http\Requests\RegisterCustomerRequest;
use Modules\CRM\Http\Resources\CustomerResource;
use Modules\CRM\Services\CustomerAuthService;
use Modules\Sales\Http\Controllers\Concerns\ResolvesCart;

class AuthController extends Controller
{
    use ResolvesCart;

    public function __construct(
        private readonly CustomerAuthService $auth,
    ) {}

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        // Resolved BEFORE registration logs the customer in — resolveCart()
        // checks the customer guard first, and we need the *guest* cart
        // (identified by the X-Cart-Token header) here, not a customer cart
        // that doesn't exist yet.
        $guestCart = $this->resolveCart($request);

        $customer = $this->auth->register($request->validated(), $guestCart);

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $guestCart = $this->resolveCart($request);

        $customer = $this->auth->login(
            $request->string('email'),
            $request->string('password'),
            $guestCart,
        );

        if (! $customer) {
            // Generic message — no user enumeration, per §6.2.
            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'These credentials do not match our records.'],
            ], 422);
        }

        return new CustomerResource($customer);
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }
}
