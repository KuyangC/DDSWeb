<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Factory;

class TestFirebaseSimple extends Command
{
    protected $signature = 'firebase:simple-test';
    protected $description = 'Simple Firebase test without Service class';

    public function handle()
    {
        $this->info('🧪 Testing Firebase simple connection...');
        
        try {
            // Langsung pakai Factory
            $factory = new Factory();
            $database = $factory
                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'))
                ->createDatabase();
            
            $this->info('✅ Firebase connected!');
            
            // Test read data
            $systemStatus = $database->getReference('systemStatus')->getValue();
            $this->info("🔧 System Status: " . ($systemStatus ?? 'No data'));
            
            $slaveData = $database->getReference('all_slave_data')->getValue();
            $this->info("🔢 Slave Data: " . ($slaveData ?? 'No data'));
            
            $this->info("🎉 Simple test completed!");
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}