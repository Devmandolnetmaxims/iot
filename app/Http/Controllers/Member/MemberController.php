<?php

namespace App\Http\Controllers\Member;

use App\Http\Repository\Member\MemberRepository;
use App\Http\Requests\Registration\UserRegisterRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function register(UserRegisterRequest $request) {
        return MemberRepository::register($request);
    }

    public function memberLists(Request $request) {
        return MemberRepository::MemberLists($request);
    }

    public function memberDetail($id) {
        return MemberRepository::MemberDetail($id);
    }

    public function memberUpdate(Request $request, $id) {
        return MemberRepository::Memberupdate($request, $id);
    }

    public function memberStateUpdate(Request $request, $id) {
        return MemberRepository::MemberStateUpdate($request, $id);
    }

    public function memberDelete($id) {
        return MemberRepository::MemberDelete($id);
    }
}
