<?php

namespace App\Services\Members;

use App\Models\Member;
use Illuminate\Http\Request;

class AuthenticatedMemberResolver
{
    public function resolve(Request $request): Member
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $member = Member::query()
            ->where('email', $user->email)
            ->whereNull('deleted_at')
            ->first();

        abort_if($member === null, 404, 'Member account profile not found.');

        return $member;
    }
}
