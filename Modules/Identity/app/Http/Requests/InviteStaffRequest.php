<?php

declare(strict_types=1);

namespace Modules\Identity\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled via policy in controller
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }
}