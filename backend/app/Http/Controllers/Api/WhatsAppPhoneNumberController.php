<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsAppPhoneNumberResource;
use App\Models\Organization;
use App\Models\WhatsAppPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppPhoneNumberController extends Controller
{
    /**
     * Display a listing of phone numbers for an organization.
     */
    public function index(Request $request, Organization $organization)
    {
        if (!$organization->isMember($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $phoneNumbers = $organization->whatsappPhoneNumbers()
            ->withCount(['leads', 'conversations'])
            ->get();

        return WhatsAppPhoneNumberResource::collection($phoneNumbers);
    }

    /**
     * Store a newly created phone number.
     */
    public function store(Request $request, Organization $organization)
    {
        if (!$organization->isAdmin($request->user())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|unique:whatsapp_phone_numbers,phone_number',
            'display_name' => 'nullable|string|max:255',
            'phone_number_id' => 'required|string',
            'waba_id' => 'required|string',
            'access_token' => 'required|string',
            'verify_token' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_default' => 'boolean',
        ]);

        try {
            $validated['organization_id'] = $organization->id;
            $validated['status'] = 'pending';

            $phoneNumber = WhatsAppPhoneNumber::create($validated);

            if ($validated['is_default'] ?? false) {
                $phoneNumber->setAsDefault();
            }

            Log::info('WhatsApp phone number created', [
                'phone_number_id' => $phoneNumber->id,
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return new WhatsAppPhoneNumberResource($phoneNumber);
        } catch (\Exception $e) {
            Log::error('Error creating WhatsApp phone number', [
                'error' => $e->getMessage(),
                'organization_id' => $organization->id,
            ]);

            return response()->json([
                'message' => 'Error al crear número de WhatsApp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified phone number.
     */
    public function show(Request $request, Organization $organization, WhatsAppPhoneNumber $phoneNumber)
    {
        if (!$organization->isMember($request->user()) || $phoneNumber->organization_id !== $organization->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return new WhatsAppPhoneNumberResource($phoneNumber->load('organization'));
    }

    /**
     * Update the specified phone number.
     */
    public function update(Request $request, Organization $organization, WhatsAppPhoneNumber $phoneNumber)
    {
        if (!$organization->isAdmin($request->user()) || $phoneNumber->organization_id !== $organization->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'access_token' => 'sometimes|string',
            'verify_token' => 'sometimes|string',
            'webhook_url' => 'sometimes|url',
            'status' => 'sometimes|in:pending,active,suspended,inactive',
            'quality_rating' => 'sometimes|in:green,yellow,red',
            'capabilities' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'is_default' => 'sometimes|boolean',
        ]);

        try {
            $phoneNumber->update($validated);

            if (isset($validated['is_default']) && $validated['is_default']) {
                $phoneNumber->setAsDefault();
            }

            Log::info('WhatsApp phone number updated', [
                'phone_number_id' => $phoneNumber->id,
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return new WhatsAppPhoneNumberResource($phoneNumber);
        } catch (\Exception $e) {
            Log::error('Error updating WhatsApp phone number', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumber->id,
            ]);

            return response()->json([
                'message' => 'Error al actualizar número de WhatsApp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified phone number.
     */
    public function destroy(Request $request, Organization $organization, WhatsAppPhoneNumber $phoneNumber)
    {
        if (!$organization->isAdmin($request->user()) || $phoneNumber->organization_id !== $organization->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $phoneNumber->delete();

            Log::info('WhatsApp phone number deleted', [
                'phone_number_id' => $phoneNumber->id,
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Número de WhatsApp eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error deleting WhatsApp phone number', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumber->id,
            ]);

            return response()->json([
                'message' => 'Error al eliminar número de WhatsApp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify the phone number with WhatsApp.
     */
    public function verify(Request $request, Organization $organization, WhatsAppPhoneNumber $phoneNumber)
    {
        if (!$organization->isAdmin($request->user()) || $phoneNumber->organization_id !== $organization->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            // TODO: Implement actual verification with WhatsApp API
            $phoneNumber->update([
                'verified_at' => now(),
                'status' => 'active',
            ]);

            return new WhatsAppPhoneNumberResource($phoneNumber);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar número',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set as default phone number for the organization.
     */
    public function setDefault(Request $request, Organization $organization, WhatsAppPhoneNumber $phoneNumber)
    {
        if (!$organization->isAdmin($request->user()) || $phoneNumber->organization_id !== $organization->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $phoneNumber->setAsDefault();

            return new WhatsAppPhoneNumberResource($phoneNumber);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al establecer número por defecto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
