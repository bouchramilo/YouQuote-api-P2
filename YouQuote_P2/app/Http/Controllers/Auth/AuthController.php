<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Request;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        $user = User::create([
            'name'     => $fields['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($fields['password']),
        ]);

        if (User::count() === 1) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('auteur');
        }

        $token = $user->createToken('auth_token');

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return [
                'message' => 'Email ou password est incorrect !!',
            ];
        }

        $token = $user->createToken('auth_token');

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return ['message' => 'Vous êtes déconnecté avec succès'];
    }
}
