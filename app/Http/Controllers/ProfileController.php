<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ReputationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, ReputationService $reputation): View
    {
        $user = $reputation->loadSummary($request->user());

        return view('profile.edit', [
            'user' => $user,
            'reviews' => $reputation->paginateReviews($user),
        ]);
    }

    /**
     * Display a public user profile.
     */
    public function show(User $user, ReputationService $reputation): View
    {
        $user = $reputation->loadSummary($user);

        return view('profile.show', [
            'user' => $user,
            'reviews' => $reputation->paginateReviews($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        Storage::disk('public')->delete(
            $user->listings()->whereNotNull('image_path')->pluck('image_path')->all(),
        );

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
