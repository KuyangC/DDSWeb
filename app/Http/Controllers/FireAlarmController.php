<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class FireAlarmController extends Controller
{
    protected $database;

    public function __construct()
    {
        try {
            $serviceAccountPath = storage_path('app/firebase-credentials.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new \Exception('Firebase credentials file not found at: ' . $serviceAccountPath);
            }

            $this->database = (new Factory)
                ->withServiceAccount($serviceAccountPath)
                ->withDatabaseUri('https://testing1do-default-rtdb.asia-southeast1.firebasedatabase.app')
                ->createDatabase();
                
        } catch (\Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->database = null;
        }
    }

    public function monitoring()
    {
        // Cek jika Firebase tidak terinisialisasi
        if (!$this->database) {
            return view('monitoring', [
                'zoneStatuses' => [],
                'projectInfo' => [],
                'allSlaveData' => [],
                'systemStatus' => 'DISCONNECTED',
                'bellStatus' => 'UNKNOWN',
                'error' => 'Firebase connection failed. Check credentials and connection.'
            ]);
        }

        try {
            // Ambil data REAL dari Firebase
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $projectInfo = $this->database->getReference('projectInfo')->getValue();
            $systemStatus = $this->database->getReference('systemStatus')->getValue();
            $bellStatus = $this->database->getReference('bell_status')->getValue();

            // Process data real
            $zoneStatuses = [];
            if (isset($allSlaveData['raw_data']) && isset($projectInfo['activeZone'])) {
                $zoneStatuses = $this->processIntegratedData(
                    $allSlaveData['raw_data'], 
                    $projectInfo['activeZone']
                );
            }

            return view('monitoring', compact(
                'zoneStatuses', 
                'projectInfo', 
                'allSlaveData',
                'systemStatus',
                'bellStatus'
            ));

        } catch (\Exception $e) {
            \Log::error('Failed to fetch Firebase data: ' . $e->getMessage());
            
            return view('monitoring', [
                'zoneStatuses' => [],
                'projectInfo' => [],
                'allSlaveData' => [],
                'systemStatus' => 'ERROR',
                'bellStatus' => 'ERROR',
                'error' => 'Failed to fetch data from Firebase: ' . $e->getMessage()
            ]);
        }
    }

    private function processIntegratedData($rawData, $activeZoneString)
{
    $zones = [];
    
    if (empty($rawData)) {
        return $zones;
    }

    // Extract zone names dari activeZone
    $zoneMap = $this->parseActiveZone($activeZoneString);
    
    // Extract STX blocks dari raw data - PERBAIKI REGEX
    preg_match_all('/<STX>(\d+)/', $rawData, $matches);
    
    // Mapping status codes - UPDATE UNTUK HANDLE FORMAT BERBEDA
    $statusCodes = [
        '0000' => ['status' => 'NORMAL', 'code' => 'fo'],
        '0122' => ['status' => 'ALARM', 'code' => 'al'],
        '0133' => ['status' => 'TROUBLE', 'code' => 'tr'],
        // ... lainnya
    ];

    foreach ($matches[1] as $index => $dataBlock) {
        $zoneNumber = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $zoneName = $zoneMap[$zoneNumber] ?? 'ZONE ' . ($index + 1);
        
        // ✅ FIX: Handle data 2 digit sebagai ZONE NUMBER, bukan status
        if (strlen($dataBlock) === 2) {
            // Data 2 digit = hanya zone number, status default NORMAL
            $zones[] = [
                'number' => $zoneNumber,
                'name' => $zoneName,
                'status' => 'NORMAL', // Default ke NORMAL
                'code' => 'uk', // Unknown code
                'raw_data' => $dataBlock,
                'note' => 'Data tidak lengkap' // Tambahkan note
            ];
        } 
        // Handle data 6 digit (format lengkap)
        else if (strlen($dataBlock) === 6) {
            $zoneCode = substr($dataBlock, 0, 2);
            $statusCode = substr($dataBlock, 2, 4);
            
            $status = $statusCodes[$statusCode] ?? ['status' => 'UNKNOWN', 'code' => 'uk'];
            
            $zones[] = [
                'number' => $zoneNumber,
                'name' => $zoneName,
                'status' => $status['status'],
                'code' => $status['code'],
                'raw_data' => $dataBlock
            ];
        }
        // Handle format lainnya
        else {
            $zones[] = [
                'number' => $zoneNumber,
                'name' => $zoneName,
                'status' => 'UNKNOWN',
                'code' => 'uk',
                'raw_data' => $dataBlock,
                'note' => 'Format data tidak dikenali'
            ];
        }
    }

    return $zones;
}
    private function parseActiveZone($activeZoneString)
    {
        $zoneMap = [];
        
        if (empty($activeZoneString)) {
            return $zoneMap;
        }
        
        $zones = explode(',', $activeZoneString);
        
        foreach ($zones as $zone) {
            $zone = trim($zone);
            if (preg_match('/#(\d+)#(.+)/', $zone, $matches)) {
                $zoneNumber = $matches[1];
                $zoneName = trim($matches[2]);
                $zoneMap[$zoneNumber] = $zoneName;
            }
        }
        
        return $zoneMap;
    }

    public function getLiveZoneStatus()
    {
        if (!$this->database) {
            return response()->json([
                'success' => false,
                'error' => 'Firebase not connected'
            ], 500);
        }

        try {
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $projectInfo = $this->database->getReference('projectInfo')->getValue();
            
            $zoneStatuses = [];
            if (isset($allSlaveData['raw_data']) && isset($projectInfo['activeZone'])) {
                $zoneStatuses = $this->processIntegratedData(
                    $allSlaveData['raw_data'], 
                    $projectInfo['activeZone']
                );
            }

            return response()->json([
                'success' => true,
                'zoneStatuses' => $zoneStatuses,
                'lastUpdate' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}