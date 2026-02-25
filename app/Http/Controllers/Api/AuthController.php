<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Parents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'password' => 'required',
        ]);

        // Rechercher l'utilisateur via la table parents
        $parent = \App\Models\Parents::where('telephone', $request->telephone)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone non trouvé'
            ], 401);
        }

        $user = User::find($parent->user_id);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ], 401);
        }

        if (!$user->actif) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'actif' => $user->actif,
                'email_verified_at' => $user->email_verified_at,
            ],
            'parent' => [
                'id' => $parent->id,
                'telephone' => $parent->telephone,
                'adresse' => $parent->adresse ?? null,
                'nom' => $parent->nom,
                'prenom' => $parent->prenom,
                // Ajoutez d'autres champs du parent si nécessaire
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }
}
