<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Request;
use Hash;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
// *********************************************************************************************************************************************
    public function register(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Attribution des rôles
        $user->assignRole(User::count() === 1 ? 'Admin' : 'Auteur');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $user->only(['id', 'name', 'email']),
            'token'   => $token,
            'message' => 'Inscription réussie',
        ], 201)->cookie('auth_token', $token, 60 * 24 * 7, null, null, true, true);
    }

    // *********************************************************************************************************************************************
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email ou password est incorrect !!',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $user->only(['id', 'name', 'email']),
            'token'   => $token,
            'message' => 'Inscription réussie',
        ], 201)->cookie('auth_token', $token, 60 * 24 * 7, null, null, true, true);

    }

    // *********************************************************************************************************************************************
    public function logout(Request $request)
    {
        // 1. Récupération du token depuis les cookies
        $token = $request->cookie('auth_token');

        // 2. Vérification de la présence et validité du token
        if (! $token) {
            return response()->json([
                'success'       => false,
                'authenticated' => false,
                'message'       => 'Aucun token trouvé',
                'user'          => null,
            ], 200);
        }

        // 3. Recherche du token dans la base de données
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json([
                'success'       => false,
                'authenticated' => false,
                'message'       => 'Token invalide',
                'user'          => null,
            ], 200);
        }

        // 4. Suppression du token
        $accessToken->delete();

        // 5. Réponse JSON avec suppression du cookie
        return response()->json([
            'success'       => true,
            'authenticated' => false,
            'message'       => 'Déconnexion réussie',
            'user'          => null,
        ])->withoutCookie('auth_token');
    }

    // *********************************************************************************************************************************************

    public function isLogged(Request $request)
    {
        $token = $request->cookie("auth_token");

        if (! $token || ! $token = PersonalAccessToken::findToken($token)) {
            return response()->json([
                'authenticated' => false,
                'user'          => null,
                'message'       => 'Not authenticated',
            ], 200);
        }

        $user = $token->tokenable;
        $role = $user->getRoleNames()->toArray();
        return response()->json([
            'authenticated' => true,
            'user'          => $user,
            'roles'         => $user->getRoleNames()->toArray(),
            'role'         => $role[0],
        ]);
    }

    public function getUserRoles(Request $request)
    {
        if (! $request->user()) {
            return response()->json([]);
        }

        return response()->json($request->user()->getRoleNames());
    }
}
