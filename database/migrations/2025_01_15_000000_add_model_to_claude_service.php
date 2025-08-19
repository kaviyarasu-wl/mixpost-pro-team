<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Models\Service;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing Claude service configurations to include default model
        $claudeServices = Service::where('name', 'claude')->get();
        
        foreach ($claudeServices as $service) {
            $configuration = $service->configuration ?? collect();
            
            // Add default model if not present
            if (!$configuration->has('model')) {
                $configuration->put('model', 'claude-3-5-sonnet-20241022');
                
                $service->configuration = $configuration;
                $service->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove model from Claude service configurations
        $claudeServices = Service::where('name', 'claude')->get();
        
        foreach ($claudeServices as $service) {
            $configuration = $service->configuration ?? collect();
            
            // Remove model field
            $configuration->forget('model');
            
            $service->configuration = $configuration;
            $service->save();
        }
    }
};