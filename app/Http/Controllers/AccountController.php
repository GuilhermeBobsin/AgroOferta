<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.contact', ['user' => $request->user()]);
    }

    public function update(ContactRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->with('status', 'Contato atualizado.');
    }
}
