<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class FireAlarmController extends Controller
{
    protected $database;
    protected $totalSlaves = 63;
    protected $zonesPerSlave = 5;
    protected $firebaseConnected = false;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    private function initializeFirebase()
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

            $this->firebaseConnected = true;
            \Log::info('Firebase connected successfully');

        } catch (\Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->database = null;
            $this->firebaseConnected = false;
        }
    }

    public function monitoring()
    {
        if (!$this->firebaseConnected || !$this->database) {
            $emptyData = $this->generateEmptySlaveData();
            $stats = $this->calculateStatistics($emptyData);

            return view('monitoring', [
                'slaveData' => $emptyData,
                'systemStatus' => 'DISCONNECTED',
                'bellStatus' => 'UNKNOWN',
                'projectInfo' => [
                    'projectName' => 'DDS - FIRE ALARM MONITORING',
                    'panelType' => 'N/A',
                    'location' => 'Fire Alarm Monitoring System'
                ],
                'masterStatus' => [ // DEFAULT STATUS
                    'ac_power' => false,
                    'dc_power' => false,
                    'alarm_active' => false,
                    'trouble_active' => false,
                    'drill' => false,
                    'silenced' => false,
                    'disabled' => false
                ],
                'stats' => $stats,
                'totalSlaves' => $this->totalSlaves,
                'totalZones' => $this->totalSlaves * $this->zonesPerSlave,
                'firebaseConnected' => false
            ]);
        }

        try {
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $systemStatusRaw = $this->database->getReference('systemStatus')->getValue();
            $bellStatusRaw = $this->database->getReference('bell_status')->getValue();
            $projectInfo = $this->database->getReference('projectInfo')->getValue();

            // Handle systemStatus
            $systemStatus = 'UNKNOWN';
            if (is_string($systemStatusRaw)) {
                $systemStatus = $systemStatusRaw;
            } elseif (is_array($systemStatusRaw) && isset($systemStatusRaw['status'])) {
                $systemStatus = $systemStatusRaw['status'];
            }

            // Handle bellStatus
            $bellStatus = 'UNKNOWN';
            if (is_string($bellStatusRaw)) {
                $bellStatus = $bellStatusRaw;
            }

            // PARSE MASTER STATUS DARI RAW DATA
            $masterStatus = [];
            if (isset($allSlaveData['raw_data'])) {
                $masterStatus = $this->parseMasterStatus($allSlaveData['raw_data']);
                \Log::info('Parsed Master Status: ' . json_encode($masterStatus));
            } else {
                $masterStatus = [
                    'ac_power' => false,
                    'dc_power' => false,
                    'alarm_active' => false,
                    'trouble_active' => false,
                    'drill' => false,
                    'silenced' => false,
                    'disabled' => false
                ];
            }

            // SLAVE DATA - JANGAN DIUBAH (existing code)
            $slaveData = [];
            if (isset($allSlaveData['raw_data'])) {
                $slaveData = $this->parseCompletePoolingData($allSlaveData['raw_data']);
            } else {
                $slaveData = $this->generateEmptySlaveData();
            }

            $stats = $this->calculateStatistics($slaveData);

            // Handle projectInfo
            $projectInfoData = [
                'projectName' => $projectInfo['projectName'] ?? 'DDS - FIRE ALARM MONITORING',
                'panelType' => $projectInfo['panelType'] ?? 'N/A',
                'location' => $projectInfo['location'] ?? 'Fire Alarm Monitoring System'
            ];

            return view('monitoring', [
                'slaveData' => $slaveData,
                'systemStatus' => $systemStatus,
                'bellStatus' => $bellStatus,
                'projectInfo' => $projectInfoData,
                'masterStatus' => $masterStatus, // ← DATA BARU
                'stats' => $stats,
                'totalSlaves' => $this->totalSlaves,
                'totalZones' => $this->totalSlaves * $this->zonesPerSlave,
                'firebaseConnected' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch Firebase data: ' . $e->getMessage());

            $emptyData = $this->generateEmptySlaveData();
            $stats = $this->calculateStatistics($emptyData);

            return view('monitoring', [
                'slaveData' => $emptyData,
                'systemStatus' => 'ERROR',
                'bellStatus' => 'ERROR',
                'projectInfo' => [
                    'projectName' => 'DDS - FIRE ALARM MONITORING',
                    'panelType' => 'N/A',
                    'location' => 'Fire Alarm Monitoring System'
                ],
                'masterStatus' => [
                    'ac_power' => false,
                    'dc_power' => false,
                    'alarm_active' => false,
                    'trouble_active' => false,
                    'drill' => false,
                    'silenced' => false,
                    'disabled' => false
                ],
                'stats' => $stats,
                'totalSlaves' => $this->totalSlaves,
                'totalZones' => $this->totalSlaves * $this->zonesPerSlave,
                'firebaseConnected' => false
            ]);
        }
    }

    private function parseMasterStatus($rawData)
{
    $defaultStatus = [
        'ac_power' => false,
        'dc_power' => false, 
        'alarm_active' => false,
        'trouble_active' => false,
        'drill' => false,
        'silenced' => false,
        'disabled' => false
    ];

    // Cari pattern 40XX (4 digit hex master status)
    if (preg_match('/40([0-9A-F]{2})/i', $rawData, $matches)) {
        $statusByte = $matches[1];
        $value = hexdec($statusByte);
        
        \Log::info("Master Status Hex: {$statusByte}, Decimal: {$value}, Binary: " . str_pad(decbin($value), 8, '0', STR_PAD_LEFT));
        
        // KOREKSI: AKTIF LOW - 0 = ON/ACTIVE, 1 = OFF/INACTIVE
        // Tapi untuk tampilan UI, kita ingin: true = NYALA/HIJAU, false = MATI/ABU
        return [
            'ac_power' => ($value & 0x40) == 0,      // Bit 6: 0=ON→true, 1=OFF→false
            'dc_power' => ($value & 0x20) == 0,      // Bit 5: 0=ON→true, 1=OFF→false  
            'alarm_active' => ($value & 0x10) == 0,  // Bit 4: 0=ACTIVE→true, 1=INACTIVE→false
            'trouble_active' => ($value & 0x08) == 0,// Bit 3: 0=ACTIVE→true, 1=INACTIVE→false
            'drill' => ($value & 0x04) == 0,         // Bit 2: 0=ACTIVE→true, 1=INACTIVE→false
            'silenced' => ($value & 0x02) == 0,      // Bit 1: 0=ACTIVE→true, 1=INACTIVE→false
            'disabled' => ($value & 0x01) == 0,      // Bit 0: 0=ACTIVE→true, 1=INACTIVE→false
        ];
    }
    
    return $defaultStatus;
}
    private function parseCompletePoolingData($rawData)
    {
        if (empty($rawData)) {
            return $this->generateEmptySlaveData();
        }

        // Cari semua data slave
        preg_match_all('/<STX>([0-9A-F]{2,6})/i', $rawData, $matches);

        \Log::info('Found slave segments: ' . json_encode($matches[1]));

        $slaveData = [];
        $processedAddresses = [];

        foreach ($matches[1] as $segment) {
            // Determine slave number from ADDRESS, bukan urutan
            $address = substr($segment, 0, 2);
            $slaveNumber = hexdec($address); // Convert hex address to decimal

            \Log::info("Segment: {$segment} -> Address: {$address} -> Slave: {$slaveNumber}");

            // Skip jika slave number diluar range 1-63
            if ($slaveNumber < 1 || $slaveNumber > 63) {
                \Log::warning("Invalid slave number: {$slaveNumber} from address: {$address}");
                continue;
            }

            // Skip jika sudah diproses
            if (in_array($slaveNumber, $processedAddresses)) {
                continue;
            }

            $processedAddresses[] = $slaveNumber;
            $parsedSlave = $this->parseSlaveSegment($slaveNumber, $segment);
            $slaveData[] = $parsedSlave;
        }

        // Fill sisanya dengan offline slaves berdasarkan urutan yang benar
        $finalSlaveData = [];
        for ($slaveNumber = 1; $slaveNumber <= $this->totalSlaves; $slaveNumber++) {
            $found = false;

            // Cari slave yang sudah diproses
            foreach ($slaveData as $slave) {
                if ($slave['slave_number'] == $slaveNumber) {
                    $finalSlaveData[] = $slave;
                    $found = true;
                    break;
                }
            }

            // Jika tidak ditemukan, buat slave offline
            if (!$found) {
                $finalSlaveData[] = $this->createOfflineSlave($slaveNumber);
            }
        }

        \Log::info('Final slave count: ' . count($finalSlaveData));
        return $finalSlaveData;
    }

    private function parseSlaveSegment($slaveNumber, $segment)
    {
        // Handle slave online dengan status (data 6-digit)
        if (strlen($segment) === 6) {
            $address = substr($segment, 0, 2);
            $troubleByte = hexdec(substr($segment, 2, 2));
            $alarmByte = hexdec(substr($segment, 4, 2));

            return $this->parseSlaveStatus($slaveNumber, $address, $troubleByte, $alarmByte, $segment);
        }

        // Handle slave offline (data 2-digit)
        if (strlen($segment) === 2) {
            return $this->createOfflineSlave($slaveNumber);
        }

        // Data tidak dikenali
        return $this->createOfflineSlave($slaveNumber);
    }

    private function parseSlaveStatus($slaveNumber, $address, $troubleByte, $alarmByte, $rawData)
    {
        $zones = [];
        $hasAlarm = false;
        $hasTrouble = false;
        $bellActive = ($alarmByte & 0x20) !== 0;

        // OPTIMIZED: Loop tanpa logging berlebihan
        for ($zoneNum = 1; $zoneNum <= 5; $zoneNum++) {
            $bitMask = 1 << ($zoneNum - 1);

            $trouble = ($troubleByte & $bitMask) !== 0;
            $alarm = ($alarmByte & $bitMask) !== 0;

            if ($alarm)
                $hasAlarm = true;
            if ($trouble)
                $hasTrouble = true;

            $status = 'NORMAL';
            if ($alarm) {
                $status = 'ALARM';
            } elseif ($trouble) {
                $status = 'TROUBLE';
            }

            $zones[] = [
                'number' => $zoneNum,
                'global_number' => (($slaveNumber - 1) * 5) + $zoneNum,
                'status' => $status,
                'alarm' => $alarm,
                'trouble' => $trouble,
                'bell' => $bellActive,
                'display_text' => 'Z' . $zoneNum
            ];
        }

        $overallStatus = 'NORMAL';
        if ($hasAlarm) {
            $overallStatus = 'ALARM';
        } elseif ($hasTrouble) {
            $overallStatus = 'TROUBLE';
        }

        return [
            'slave_number' => $slaveNumber,
            'address' => $address,
            'status' => $overallStatus,
            'bell_active' => $bellActive,
            'zones' => $zones,
            'raw_data' => $rawData,
            'online' => true,
            'display_name' => 'S' . $slaveNumber
        ];
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
            'display_name' => 'S' . $slaveNumber
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

        return $stats;
    }

    public function getLiveStatus()
    {
        if (!$this->firebaseConnected) {
            return response()->json([
                'success' => false,
                'error' => 'Firebase not connected'
            ], 500);
        }

        try {
            $allSlaveData = $this->database->getReference('all_slave_data')->getValue();
            $systemStatusRaw = $this->database->getReference('systemStatus')->getValue();
            $bellStatusRaw = $this->database->getReference('bell_status')->getValue();
            $projectInfo = $this->database->getReference('projectInfo')->getValue();

            // Convert systemStatus to string
            $systemStatus = 'UNKNOWN';
            if (is_string($systemStatusRaw)) {
                $systemStatus = $systemStatusRaw;
            }

            // Convert bellStatus to string  
            $bellStatus = 'UNKNOWN';
            if (is_string($bellStatusRaw)) {
                $bellStatus = $bellStatusRaw;
            }

            // PARSE MASTER STATUS
            $masterStatus = [];
            if (isset($allSlaveData['raw_data'])) {
                $masterStatus = $this->parseMasterStatus($allSlaveData['raw_data']);
            }

            // SLAVE DATA - JANGAN DIUBAH
            $slaveData = [];
            if (isset($allSlaveData['raw_data'])) {
                $slaveData = $this->parseCompletePoolingData($allSlaveData['raw_data']);
            } else {
                $slaveData = $this->generateEmptySlaveData();
            }

            $stats = $this->calculateStatistics($slaveData);

            return response()->json([
                'success' => true,
                'slaveData' => $slaveData,
                'systemStatus' => $systemStatus,
                'bellStatus' => $bellStatus,
                'projectInfo' => $projectInfo,
                'masterStatus' => $masterStatus, // ← TAMBAH INI
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