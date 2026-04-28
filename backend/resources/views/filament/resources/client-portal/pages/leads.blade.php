<!-- Client Portal Leads List -->
<div class="fi-page-content">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Leads</h2>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        {{ $this->getTable()->render() }}
    </div>
</div>