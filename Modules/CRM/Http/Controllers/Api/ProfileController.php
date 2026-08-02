<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\CRM\Http\Requests\UpdatePasswordRequest;
use Modules\CRM\Http\Requests\UpdateProfileRequest;
use Modules\CRM\Http\Resources\CustomerResource;

class ProfileController extends Controller
{
    // All routes here are behind auth:customer (see routes/api.php) — no
    // {id} in the URL on purpose, "profile" always means "mine".

    public function show(): CustomerResource
    {
        return new CustomerResource(Auth::guard('customer')->user());
    }

    public function update(UpdateProfileRequest $request): CustomerResource
    {
        $customer = Auth::guard('customer')->user();
        $customer->update($request->validated());

        return new CustomerResource($customer->fresh());
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        $customer->update(['password' => Hash::make($request->string('password'))]);

        return response()->json(['data' => ['message' => 'Password updated.']]);
    }
}
