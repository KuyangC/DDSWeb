<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Factory;

class TestFirebaseCreds extends Command
{
    protected $signature = 'firebase:creds-test';
    protected $description = 'Test Firebase with Service Account Credentials';

    public function handle()
    {
        $this->info('🔐 Testing Firebase with Service Account...');
        
        try {
            // Path ke credentials file
            $serviceAccountPath = base_path(env('FIREBASE_CREDENTIALS'));
            
            if (!file_exists($serviceAccountPath)) {
                $this->error('❌ Credentials file not found: ' . $serviceAccountPath);
                return;
            }
            
            $this->info('✅ Credentials file found: ' . $serviceAccountPath);
            
            // Initialize Firebase dengan Service Account
            $firebase = (new Factory)
                ->withServiceAccount($serviceAccountPath)
                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
            
            $database = $firebase->createDatabase();
            
            $this->info('✅ Firebase initialized with Service Account!');
            
            // Test read data
            $this->info("\n📊 Reading data from Firebase...");
            
            // System Status
            $systemStatus = $database->getReference('systemStatus')->getValue();
            $this->info("🔧 System Status: " . $this->formatValue($systemStatus));
            
            // Slave Data
            $slaveData = $database->getReference('all_slave_data')->getValue();
            $this->info("🔢 Slave Data: " . $this->formatValue($slaveData));
            
            // Bell Status
            $bellStatus = $database->getReference('bell_status')->getValue();
            $this->info("🔔 Bell Status: " . $this->formatValue($bellStatus));
            
            // Last Update
            $lastUpdate = $database->getReference('lastUpdateTime')->getValue();
            $this->info("⏰ Last Update: " . $this->formatValue($lastUpdate));
            
            // Activity Log (bisa array)
            $activityLog = $database->getReference('activityLog')->getValue();
            $this->info("📝 Activity Log: " . $this->formatValue($activityLog));
            
            // List all root nodes
            $this->info("\n📁 Root nodes in Firebase:");
            $references = $database->getReference('/')->getSnapshot()->getValue();
            
            if ($references) {
                foreach (array_keys($references) as $key) {
                    $this->info("   - {$key}");
                }
                
                // Debug: Tampilkan tipe data setiap node
                $this->info("\n🔍 Data types:");
                foreach ($references as $key => $value) {
                    $type = gettype($value);
                    if (is_array($value)) {
                        $type .= ' (' . count($value) . ' items)';
                    }
                    $this->info("   - {$key}: {$type}");
                }
            }
            
            $this->info("\n🎉 Firebase credentials test completed successfully!");
            
        } catch (\Exception $e) {
            $this->error('❌ Firebase credentials test failed: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        }
    }
    
    /**
     * Format value untuk handle array dan null
     */
    private function formatValue($value)
    {
        if (is_array($value)) {
            return 'Array: ' . json_encode($value);
        }
        
        if (is_null($value)) {
            return 'No data';
        }
        
        return $value;
    }
}