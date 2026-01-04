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
        // IMPORTANTE: Cada registro tiene SOLO message_text (cliente) O response (bot), no ambos
        // El orden debe ser cronológico: primero message_text, luego response
        $conversations = $lead->conversations()
            ->get()
            ->sortBy(function($conv) {
                // Priorizar created_at (más confiable y preciso)
                if ($conv->created_at) {
                    return $conv->created_at->timestamp;
                }
                
                // Si no hay created_at, intentar parsear timestamp
                if ($conv->timestamp) {
                    try {
                        // Intentar parsear timestamp (puede ser string o datetime)
                        $parsed = \Carbon\Carbon::parse($conv->timestamp);
                        return $parsed->timestamp;
                    } catch (\Exception $e) {
                        // Si falla, usar id como fallback
                        return $conv->id;
                    }
                }
                
                // Si no hay timestamp ni created_at, usar id
                return $conv->id;
            })
            ->values();
        
        // Log para debug - Verificar orden cronológico
        \Log::info('📤 Conversations API - Orden de conversaciones', [
            'lead_id' => $id,
            'total_conversations' => $conversations->count(),
            'conversations_order' => $conversations->map(function($c, $index) {
                $orderKey = null;
                if ($c->created_at) {
                    $orderKey = $c->created_at->timestamp;
                } elseif ($c->timestamp) {
                    try {
                        $orderKey = \Carbon\Carbon::parse($c->timestamp)->timestamp;
                    } catch (\Exception $e) {
                        $orderKey = $c->id;
                    }
                } else {
                    $orderKey = $c->id;
                }
                
                return [
                    'position' => $index + 1,
                    'id' => $c->id,
                    'type' => $c->message_text ? 'CLIENTE (message_text)' : 'BOT (response)',
                    'has_message_text' => !empty($c->message_text),
                    'has_response' => !empty($c->response),
                    'message_text_preview' => substr($c->message_text ?? '', 0, 50),
                    'response_preview' => substr($c->response ?? '', 0, 50),
                    'created_at' => $c->created_at?->toDateTimeString(),
                    'timestamp' => $c->timestamp,
                    'order_key' => $orderKey,
                ];
            })->toArray(),
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
