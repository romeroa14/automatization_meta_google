<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update the user profile.
     */
    public function update(Request $request)
    {
        $request->validate([
            'whatsapp_number' => 'nullable|string|max:20|unique:users,whatsapp_number,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->update([
            'whatsapp_number' => $request->whatsapp_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'user' => $user,
        ]);
    }

    /**
     * Get the user profile.
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Generate a new API Token for n8n integration.
     */
    public function generateToken(Request $request)
    {
        $user = $request->user();
        
        // Revoke existing 'n8n-integration' tokens to keep it clean (optional, but good for single-token usage)
        $user->tokens()->where('name', 'n8n-integration')->delete();

        $token = $user->createToken('n8n-integration')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'message' => 'Token generado correctamente. Guárdalo, no podrás verlo de nuevo.',
        ]);
    }
}
