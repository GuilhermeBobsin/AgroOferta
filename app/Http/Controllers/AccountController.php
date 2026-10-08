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
        $user = $request->user();
        $data = $request->validated();

        $newCity = array_key_exists('city', $data) ? trim((string) $data['city']) : trim((string) $user->city);
        $newState = array_key_exists('state', $data) ? $data['state'] : $user->state;
        $locationChanged = mb_strtolower($newCity) !== mb_strtolower(trim((string) $user->city))
            || $newState !== $user->state;

        $coordinatesUnchanged = $user->latitude !== null && $user->longitude !== null
            && isset($data['latitude'], $data['longitude'])
            && (float) $data['latitude'] === (float) $user->latitude
            && (float) $data['longitude'] === (float) $user->longitude;

        if ($locationChanged && $coordinatesUnchanged) {
            $data['latitude'] = null;
            $data['longitude'] = null;
        }

        $user->update($data);

        return back()->with('status', 'Contato atualizado.');
    }
}
