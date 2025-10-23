<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class FireAlarmController extends Controller
{
    protected $database;
    protected $totalSlaves = 63;
    protected $zonesPerSlave = 5;

    public function __construct()
    {
        try {
            $serviceAccountPath = storage_path('app/firebase-credentials.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new \Exception('Firebase credentials file not found');
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
        if (!$this->database) {
            $emptyData = $this->generateEmptySlaveData();
            $stats = $this->calculateStatistics($emptyData);
            
            return view('monitoring', [
                'slaveData' => $emptyData,
                'systemStatus' => 'DISCONNECTED',
                'bellStatus' => 'UNKNOWN',
                'stats' => $stats,
                'totalSlaves' => $this->totalSlaves,
                'totalZones' => $this->totalSlaves * $this->zonesPerSlave
            ]);
        }

        try {
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $systemStatus = $this->database->getReference('systemStatus')->getValue();
            $bellStatus = $this->database->getReference('bell_status')->getValue();

            $slaveData = [];
            if (isset($allSlaveData['raw_data'])) {
                $slaveData = $this->parseAllSlaveData($allSlaveData['raw_data']);
            } else {
                $slaveData = $this->generateEmptySlaveData();
            }

            // Hitung statistik
            $stats = $this->calculateStatistics($slaveData);

            return view('monitoring', compact(
                'slaveData', 
                'systemStatus',
                'bellStatus',
                'stats',
                'totalSlaves',
                'totalZones'
            ));

        } catch (\Exception $e) {
            \Log::error('Failed to fetch Firebase data: ' . $e->getMessage());
            
            $emptyData = $this->generateEmptySlaveData();
            $stats = $this->calculateStatistics($emptyData);
            
            return view('monitoring', [
                'slaveData' => $emptyData,
                'systemStatus' => 'ERROR',
                'bellStatus' => 'ERROR',
                'stats' => $stats,
                'totalSlaves' => $this->totalSlaves,
                'totalZones' => $this->totalSlaves * $this->zonesPerSlave
            ]);
        }
    }

    private function parseAllSlaveData($rawData)
    {
        \Log::info('=== START PARSING RAW DATA ===');
        \Log::info('Raw Data: ' . $rawData);
        
        if (empty($rawData)) {
            \Log::warning('Raw data is empty');
            return $this->generateEmptySlaveData();
        }

        // Extract semua data slave dari raw data
        preg_match_all('/<STX>(\d+)/', $rawData, $matches);
        
        \Log::info('Found ' . count($matches[1]) . ' STX blocks: ' . json_encode($matches[1]));

        $slaveData = [];
        
        foreach ($matches[1] as $index => $slaveRaw) {
            if ($index >= $this->totalSlaves) break;
            
            $slaveNumber = $index + 1;
            $parsedSlave = $this->parseSingleSlave($slaveNumber, $slaveRaw);
            $slaveData[] = $parsedSlave;
            
            \Log::info("Slave {$slaveNumber} parsed: " . $parsedSlave['status'] . " | Raw: " . $slaveRaw);
        }

        // Fill yang kosong dengan data offline
        while (count($slaveData) < $this->totalSlaves) {
            $slaveNumber = count($slaveData) + 1;
            $slaveData[] = $this->createOfflineSlave($slaveNumber);
            \Log::info("Slave {$slaveNumber} created as OFFLINE (fill empty)");
        }

        \Log::info('=== END PARSING - Total slaves: ' . count($slaveData) . ' ===');
        return $slaveData;
    }

    private function parseSingleSlave($slaveNumber, $rawData)
    {
        // Handle disconnected slave (data pendek)
        if (strlen($rawData) === 2) {
            \Log::info("Slave {$slaveNumber} - Disconnected (2-digit data): " . $rawData);
            return $this->createOfflineSlave($slaveNumber);
        }

        // Handle data lengkap 6 digit
        if (strlen($rawData) === 6) {
            $address = substr($rawData, 0, 2);
            $troubleByte = hexdec(substr($rawData, 2, 2));
            $alarmByte = hexdec(substr($rawData, 4, 2));
            
            \Log::info("Slave {$slaveNumber} - Parsing 6-digit: {$rawData} | Trouble: {$troubleByte} | Alarm: {$alarmByte}");
            
            $zones = [];
            $hasAlarm = false;
            $hasTrouble = false;
            $bellActive = ($alarmByte & 0x20) !== 0; // Bit 5 = Bell
            
            // Parse masing-masing zona (zona 1-5)
            for ($zoneNum = 1; $zoneNum <= 5; $zoneNum++) {
                $bitMask = 1 << ($zoneNum - 1); // Bit 0-4 untuk zona 1-5
                
                $alarm = ($alarmByte & $bitMask) !== 0;
                $trouble = ($troubleByte & $bitMask) !== 0;
                
                if ($alarm) $hasAlarm = true;
                if ($trouble) $hasTrouble = true;
                
                $status = 'NORMAL';
                if ($alarm) $status = 'ALARM';
                elseif ($trouble) $status = 'TROUBLE';
                
                $zones[] = [
                    'number' => $zoneNum,
                    'global_number' => (($slaveNumber - 1) * 5) + $zoneNum,
                    'status' => $status,
                    'alarm' => $alarm,
                    'trouble' => $trouble,
                    'bell' => $bellActive,
                    'display_text' => 'Z' . $zoneNum
                ];
                
                \Log::info("  Zone {$zoneNum}: {$status} (Alarm: " . ($alarm ? 'Y' : 'N') . ", Trouble: " . ($trouble ? 'Y' : 'N') . ")");
            }
            
            // Tentukan status overall slave
            $overallStatus = 'NORMAL';
            if ($hasAlarm) $overallStatus = 'ALARM';
            elseif ($hasTrouble) $overallStatus = 'TROUBLE';
            
            \Log::info("Slave {$slaveNumber} Overall Status: {$overallStatus} | Bell: " . ($bellActive ? 'ACTIVE' : 'INACTIVE'));

            return [
                'slave_number' => $slaveNumber,
                'address' => $address,
                'status' => $overallStatus,
                'bell_active' => $bellActive,
                'zones' => $zones,
                'raw_data' => $rawData,
                'online' => true,
                'display_name' => 'S' . $slaveNumber // ✅ NAMA SLAVE YANG BENAR
            ];
        }

        // Data tidak dikenali
        \Log::warning("Slave {$slaveNumber} - Unknown data format: " . $rawData);
        return $this->createOfflineSlave($slaveNumber);
    }

    private function createOfflineSlave($slaveNumber)
    {
        $zones = [];
        for ($zoneNum = 1; $zoneNum <= 5; $zoneNum++) {
            $zones[] = [
                'number' => $zoneNum,
                'global_number' => (($slaveNumber - 1) * 5) + $zoneNum,
                'status' => 'OFFLINE',
                'alarm' => false,
                'trouble' => false,
                'bell' => false,
                'display_text' => 'Z' . $zoneNum
            ];
        }
        
        return [
            'slave_number' => $slaveNumber,
            'address' => str_pad($slaveNumber, 2, '0', STR_PAD_LEFT),
            'status' => 'OFFLINE',
            'bell_active' => false,
            'zones' => $zones,
            'raw_data' => '',
            'online' => false,
            'display_name' => 'S' . $slaveNumber // ✅ NAMA SLAVE YANG BENAR
        ];
    }

    private function generateEmptySlaveData()
    {
        $slaves = [];
        for ($i = 1; $i <= $this->totalSlaves; $i++) {
            $slaves[] = $this->createOfflineSlave($i);
        }
        return $slaves;
    }

    private function calculateStatistics($slaveData)
    {
        $stats = ['alarm' => 0, 'trouble' => 0, 'normal' => 0, 'offline' => 0];
        
        foreach ($slaveData as $slave) {
            foreach ($slave['zones'] as $zone) {
                $stats[$zone['status']] = ($stats[$zone['status']] ?? 0) + 1;
            }
        }
        
        \Log::info('Statistics calculated: ' . json_encode($stats));
        return $stats;
    }

    public function getLiveStatus()
    {
        if (!$this->database) {
            return response()->json([
                'success' => false,
                'error' => 'Firebase not connected'
            ], 500);
        }

        try {
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $systemStatus = $this->database->getReference('systemStatus')->getValue();
            $bellStatus = $this->database->getReference('bell_status')->getValue();
            
            $slaveData = [];
            if (isset($allSlaveData['raw_data'])) {
                $slaveData = $this->parseAllSlaveData($allSlaveData['raw_data']);
            } else {
                $slaveData = $this->generateEmptySlaveData();
            }
            
            $stats = $this->calculateStatistics($slaveData);

            return response()->json([
                'success' => true,
                'slaveData' => $slaveData,
                'systemStatus' => $systemStatus,
                'bellStatus' => $bellStatus,
                'stats' => $stats,
                'lastUpdate' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Live status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}