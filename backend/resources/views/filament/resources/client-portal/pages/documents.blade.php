<!-- Client Portal Documents (Knowledge Base) -->
<div class="fi-page-content">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Base de Conocimiento</h2>
        <x-filament::button tag="a" href="{{ route('filament.client.documents.upload') }}">
            Subir Documento
        </x-filament::button>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <p class="text-gray-500 mb-4">
            Gestiona los documentos que el chatbot usará para responder preguntas sobre tus servicios.
        </p>
        
        <div class="text-center text-gray-400 py-8">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>No hay documentos aún</p>
            <p class="text-sm">Sube PDFs, TXT o documentos para que el chatbot consulte</p>
        </div>
    </div>
</div>