<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $organizations = $user->organizations()
            ->withCount(['whatsappPhoneNumbers', 'users'])
            ->get();

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'plan' => 'nullable|in:free,basic,pro,enterprise',
        ]);

        try {
            $organization = Organization::create($validated);
            
            // Attach the creator as owner
            $organization->users()->attach($request->user()->id, ['role' => 'owner']);
            
            Log::info('Organization created', [
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return new OrganizationResource($organization->load('users'));
        } catch (\Exception $e) {
            Log::error('Error creating organization', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Error al crear organización',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified organization.
     */
    public function show(Request $request, Organization $organization)
    {
        // Check if user belongs to this organization
        if (!$organization->isMember($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return new OrganizationResource(
            $organization->load(['whatsappPhoneNumbers', 'users'])
        );
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization)
    {
        // Check if user is admin
        if (!$organization->isAdmin($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'website' => 'nullable|url',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $organization->update($validated);
            
            Log::info('Organization updated', [
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return new OrganizationResource($organization);
        } catch (\Exception $e) {
            Log::error('Error updating organization', [
                'error' => $e->getMessage(),
                'organization_id' => $organization->id,
            ]);

            return response()->json([
                'message' => 'Error al actualizar organización',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Request $request, Organization $organization)
    {
        // Only owner can delete
        if (!$organization->isOwner($request->user())) {
            return response()->json(['message' => 'Solo el propietario puede eliminar la organización'], 403);
        }

        try {
            $organization->delete();
            
            Log::info('Organization deleted', [
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Organización eliminada correctamente']);
        } catch (\Exception $e) {
            Log::error('Error deleting organization', [
                'error' => $e->getMessage(),
                'organization_id' => $organization->id,
            ]);

            return response()->json([
                'message' => 'Error al eliminar organización',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a user to the organization.
     */
    public function addUser(Request $request, Organization $organization)
    {
        if (!$organization->isAdmin($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,admin,owner',
        ]);

        try {
            $organization->users()->attach($validated['user_id'], ['role' => $validated['role']]);
            
            return response()->json(['message' => 'Usuario agregado correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al agregar usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(Request $request, Organization $organization, $userId)
    {
        if (!$organization->isAdmin($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $organization->users()->detach($userId);
            
            return response()->json(['message' => 'Usuario removido correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al remover usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
