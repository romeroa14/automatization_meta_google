<!-- Client Portal Chatbot Config -->
<div class="fi-page-content">
    <div class="max-w-3xl">
        {{ form($this->getFormSchema()) }}
        
        <div class="mt-6">
            {{ $this->getActionFormFields() }}
        </div>
    </div>
</div>