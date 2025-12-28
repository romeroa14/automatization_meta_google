<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leads = \App\Models\Lead::latest()->paginate(20);
        return \App\Http\Resources\LeadResource::collection($leads);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lead = \App\Models\Lead::findOrFail($id);
        return new \App\Http\Resources\LeadResource($lead);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $lead = \App\Models\Lead::findOrFail($id);
        
        $validated = $request->validate([
            'stage' => 'sometimes|string|in:nuevo,contactado,interesado,cliente',
            'intent' => 'sometimes|string',
            'confidence_score' => 'sometimes|numeric|min:0|max:1',
        ]);
        
        $lead->update($validated);
        
        return new \App\Http\Resources\LeadResource($lead);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
    /**
     * Get conversations for a lead
     */
    public function conversations(string $id)
    {
        $lead = \App\Models\Lead::findOrFail($id);
        // Ordenar por timestamp/created_at ASC para mostrar las conversaciones en orden cronológico
        $conversations = $lead->conversations()
            ->get()
            ->sortBy(function($conv) {
                // Priorizar created_at, luego timestamp, luego id
                if ($conv->created_at) {
                    return $conv->created_at->timestamp;
                }
                if ($conv->timestamp) {
                    try {
                        return \Carbon\Carbon::parse($conv->timestamp)->timestamp;
                    } catch (\Exception $e) {
                        return $conv->id;
                    }
                }
                return $conv->id;
            })
            ->values();
        
        // Log para debug - ANTES de serializar
        \Log::info('📤 Conversations API - ANTES de Resource', [
            'lead_id' => $id,
            'total_conversations' => $conversations->count(),
            'conversations_with_response' => $conversations->filter(fn($c) => !empty($c->response))->count(),
            'conversations_detail' => $conversations->map(fn($c) => [
                'id' => $c->id,
                'is_client_message' => $c->is_client_message,
                'has_response' => !empty($c->response),
                'has_message_text' => !empty($c->message_text),
                'response_length' => strlen($c->response ?? ''),
                'message_text_length' => strlen($c->message_text ?? ''),
                'response_preview' => substr($c->response ?? '', 0, 100),
            ])->toArray(),
        ]);
        
        $resource = \App\Http\Resources\ConversationResource::collection($conversations);
        
        // Log DESPUÉS de serializar para ver qué se está enviando
        try {
            $serialized = $resource->response()->getData(true);
            \Log::info('📤 Conversations API - DESPUÉS de Resource (lo que se envía al frontend)', [
                'lead_id' => $id,
                'data_count' => count($serialized['data'] ?? []),
                'data_detail' => array_map(function($item) {
                    return [
                        'id' => $item['id'] ?? null,
                        'is_client_message' => $item['is_client_message'] ?? null,
                        'has_response' => !empty($item['response']),
                        'has_message_text' => !empty($item['message_text']),
                        'response_length' => strlen($item['response'] ?? ''),
                        'message_text_length' => strlen($item['message_text'] ?? ''),
                        'response_preview' => substr($item['response'] ?? '', 0, 100),
                        'response_value' => $item['response'] ?? null, // Incluir el valor completo para debug
                    ];
                }, $serialized['data'] ?? []),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error serializando resource', ['error' => $e->getMessage()]);
        }
        
        return $resource;
    }
}
