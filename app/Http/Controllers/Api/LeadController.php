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
        $conversations = $lead->conversations()->orderBy('created_at', 'desc')->get();
        return \App\Http\Resources\ConversationResource::collection($conversations);
    }
}
