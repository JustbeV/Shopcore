<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CRM\Http\Requests\ForgotPasswordRequest;
use Modules\CRM\Http\Requests\ResetPasswordRequest;
use Modules\CRM\Services\CustomerPasswordResetService;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly CustomerPasswordResetService $resets,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        // Same response whether or not the email exists — deliberately not
        // returning the token itself in the HTTP response (that's only for
        // the email/notification layer to use); see the service's docblock.
        $this->resets->requestReset($request->string('email'));

        return response()->json([
            'data' => ['message' => 'If an account exists for that email, a reset link has been sent.'],
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $ok = $this->resets->reset(
            $request->string('email'),
            $request->string('token'),
            $request->string('password'),
        );

        if (! $ok) {
            return response()->json([
                'error' => ['code' => 'INVALID_RESET_TOKEN', 'message' => 'This password reset link is invalid or has expired.'],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Password has been reset.']]);
    }
}
