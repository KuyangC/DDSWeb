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

            // SLAVE DATA
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
                'masterStatus' => $masterStatus,
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

            return [
                'ac_power' => ($value & 0x40) == 0,
                'dc_power' => ($value & 0x20) == 0,
                'alarm_active' => ($value & 0x10) == 0,
                'trouble_active' => ($value & 0x08) == 0,
                'drill' => ($value & 0x04) == 0,
                'silenced' => ($value & 0x02) == 0,
                'disabled' => ($value & 0x01) == 0,
            ];
        }

        return $defaultStatus;
    }

    private function parseCompletePoolingData($rawData)
{
    if (empty($rawData)) {
        return $this->generateEmptySlaveData();
    }

    // CHECK DRILL MODE
    $isDrillMode = session('drill_mode', false);
    
    if ($isDrillMode) {
        \Log::info('🎯 DRILL MODE ACTIVE - Forcing all slaves to ALARM');
        return $this->generateDrillModeData();
    }

    \Log::info("Raw data to parse: " . $rawData);

    // CARA YANG BENAR: Ambil 2 digit pertama sebagai address DECIMAL, bukan HEX
    $segments = [];
    $parts = explode('<STX>', $rawData);
    
    foreach ($parts as $part) {
        // Ambil 6 digit pertama setelah <STX>
        if (strlen($part) >= 6) {
            $segment = substr($part, 0, 6);
            if (preg_match('/^[0-9]{6}$/', $segment)) { // Hanya angka, bukan hex
                $segments[] = $segment;
            }
        }
    }

    \Log::info('Found slave segments: ' . json_encode($segments));

    $slaveData = [];
    $processedAddresses = [];

    foreach ($segments as $segment) {
        // AMBIL 2 DIGIT PERTAMA SEBAGAI DECIMAL
        $address = substr($segment, 0, 2);
        $slaveNumber = intval($address); // Convert ke integer, bukan hexdec

        \Log::info("Processing slave {$slaveNumber} from segment: {$segment} (address: {$address})");

        // Skip jika slave number diluar range 1-63
        if ($slaveNumber < 1 || $slaveNumber > 63) {
            \Log::warning("Slave {$slaveNumber} out of range");
            continue;
        }

        // Skip jika sudah diproses
        if (in_array($slaveNumber, $processedAddresses)) {
            \Log::warning("Duplicate slave: {$slaveNumber}");
            continue;
        }

        $processedAddresses[] = $slaveNumber;
        
        // Parse data slave
        $troubleByte = hexdec(substr($segment, 2, 2));
        $alarmByte = hexdec(substr($segment, 4, 2));
        
        $parsedSlave = $this->parseSlaveStatus($slaveNumber, $address, $troubleByte, $alarmByte, $segment);
        $slaveData[] = $parsedSlave;
    }

    \Log::info("Successfully parsed slaves: " . implode(', ', $processedAddresses));

    // Fill sisanya dengan offline slaves
    $finalSlaveData = [];
    for ($slaveNumber = 1; $slaveNumber <= $this->totalSlaves; $slaveNumber++) {
        $found = false;
        foreach ($slaveData as $slave) {
            if ($slave['slave_number'] == $slaveNumber) {
                $finalSlaveData[] = $slave;
                $found = true;
                break;
            }
        }
        if (!$found) {
            \Log::warning("Slave {$slaveNumber} not found in data");
            $finalSlaveData[] = $this->createOfflineSlave($slaveNumber);
        }
    }

    \Log::info('Final slave count: ' . count($finalSlaveData));
    return $finalSlaveData;
}

    private function generateDrillModeData()
    {
        $slaves = [];
        for ($i = 1; $i <= $this->totalSlaves; $i++) {
            $slaves[] = $this->createDrillSlave($i);
        }
        return $slaves;
    }

    private function createDrillSlave($slaveNumber)
    {
        $zones = [];
        for ($zoneNum = 1; $zoneNum <= 5; $zoneNum++) {
            $zones[] = [
                'number' => $zoneNum,
                'global_number' => (($slaveNumber - 1) * 5) + $zoneNum,
                'status' => 'ALARM',
                'alarm' => true,
                'trouble' => false,
                'bell' => true,
                'display_text' => '#' . $zoneNum
            ];
        }

        return [
            'slave_number' => $slaveNumber,
            'address' => str_pad($slaveNumber, 2, '0', STR_PAD_LEFT),
            'status' => 'ALARM',
            'bell_active' => true,
            'zones' => $zones,
            'raw_data' => 'DRILL_MODE',
            'online' => true,
            'display_name' => '' . $slaveNumber
        ];
    }

    private function parseSlaveSegment($slaveNumber, $segment)
    {
        // Handle slave online dengan status (data 6-digit)
        if (strlen($segment) === 6) {
            $address = substr($segment, 0, 2);
            $troubleByte = hexdec(substr($segment, 2, 2));
            $alarmByte = hexdec(substr($segment, 4, 2));

            \Log::info("Processing slave {$slaveNumber}: TroubleByte={$troubleByte}, AlarmByte={$alarmByte}");

            return $this->parseSlaveStatus($slaveNumber, $address, $troubleByte, $alarmByte, $segment);
        }

        // Handle slave offline (data 2-digit) - mungkin tidak diperlukan
        if (strlen($segment) === 2) {
            return $this->createOfflineSlave($slaveNumber);
        }

        // Data tidak dikenali
        \Log::warning("Unrecognized segment format: {$segment}");
        return $this->createOfflineSlave($slaveNumber);
    }

    private function parseSlaveStatus($slaveNumber, $address, $troubleByte, $alarmByte, $rawData)
    {
        $zones = [];
        $hasAlarm = false;
        $hasTrouble = false;

        // DEBUG: Log the byte values
        \Log::info("Slave {$slaveNumber} - TroubleByte: " . decbin($troubleByte) . " ({$troubleByte}), AlarmByte: " . decbin($alarmByte) . " ({$alarmByte})");

        // Bell active jika bit 5 (0x20) pada alarmByte aktif
        $bellActive = ($alarmByte & 0x20) !== 0;
        \Log::info("Slave {$slaveNumber} - Bell active: " . ($bellActive ? 'YES' : 'NO'));

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

            \Log::info("Slave {$slaveNumber} Zone {$zoneNum} - Status: {$status}, Alarm: " . ($alarm ? 'YES' : 'NO') . ", Trouble: " . ($trouble ? 'YES' : 'NO'));

            $zones[] = [
                'number' => $zoneNum,
                'global_number' => (($slaveNumber - 1) * 5) + $zoneNum,
                'status' => $status,
                'alarm' => $alarm,
                'trouble' => $trouble,
                'bell' => $bellActive,
                'display_text' => '' . $zoneNum
            ];
        }

        $overallStatus = 'NORMAL';
        if ($hasAlarm) {
            $overallStatus = 'ALARM';
        } elseif ($hasTrouble) {
            $overallStatus = 'TROUBLE';
        }

        \Log::info("Slave {$slaveNumber} Overall Status: {$overallStatus}");

        return [
            'slave_number' => $slaveNumber,
            'address' => $address,
            'status' => $overallStatus,
            'bell_active' => $bellActive,
            'zones' => $zones,
            'raw_data' => $rawData,
            'online' => true,
            'display_name' => '#' . $slaveNumber
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
                'display_text' => '' . $zoneNum
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
            'display_name' => '#' . $slaveNumber
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

            // SLAVE DATA
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
                'masterStatus' => $masterStatus,
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

    public function sendCommand(Request $request)
    {
        try {
            $command = $request->input('command');
            $timestamp = $request->input('timestamp');

            \Log::info("Command received: {$command} at {$timestamp}");

            $success = true;
            $message = '';

            if ($command === 'DRILL') {
                $message = 'Drill mode activated locally';
                \Log::info('🚨 DRILL MODE ACTIVATED LOCALLY');
            } elseif ($command === 'SYSTEM_RESET') {
                $message = 'System reset completed locally';
                \Log::info('🔄 SYSTEM RESET LOCALLY');
            } elseif ($command === 'SILENCE') {
                $message = 'Alarms silenced locally';
                \Log::info('🔇 SILENCE LOCALLY');
            } elseif ($command === 'ACKNOWLEDGE') {
                $message = 'Alarms acknowledged locally';
                \Log::info('✅ ACKNOWLEDGE LOCALLY');
            } else {
                $message = 'Unknown command';
                $success = false;
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'command' => $command,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Command error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Command execution failed'
            ], 500);
        }
    }

    // METHOD UNTUK DRILL MODE
    private function triggerDrillMode()
    {
        \Log::info('🚨 DRILL MODE ACTIVATED - All slaves to ALARM');

        // Simulate semua slave menjadi ALARM
        $drillData = [
            'drill_active' => true,
            'activated_at' => now()->toISOString(),
            'all_slaves_alarm' => true
        ];

        // Save ke Firebase
        if ($this->database) {
            $this->database->getReference('system_mode')->set($drillData);
        }

        session(['drill_mode' => true]);
    }

    // METHOD UNTUK SYSTEM RESET
    private function resetSystem()
    {
        \Log::info('🔄 SYSTEM RESET - All slaves to NORMAL');

        // Reset semua ke normal
        if ($this->database) {
            $this->database->getReference('system_mode')->set([
                'drill_active' => false,
                'reset_at' => now()->toISOString()
            ]);
        }

        session(['drill_mode' => false]);
    }

    // METHOD UNTUK SILENCE
    private function silenceAlarms()
    {
        \Log::info('🔇 SILENCE - Bells silenced');

        if ($this->database) {
            $this->database->getReference('system_mode/silenced')->set(true);
        }
    }

    // METHOD UNTUK ACKNOWLEDGE  
    private function acknowledgeAlarms()
    {
        \Log::info('✅ ACKNOWLEDGE - Alarms acknowledged');

        if ($this->database) {
            $this->database->getReference('system_mode/acknowledged')->set(true);
        }
    }

    // METHOD UNTUK CHECK CONNECTION
    public function checkConnection()
    {
        return response()->json([
            'connected' => $this->firebaseConnected,
            'timestamp' => now()->toISOString()
        ]);
    }

    // METHOD UNTUK COMMAND HISTORY
    public function getCommandHistory()
    {
        if (!$this->database) {
            return response()->json([
                'success' => false,
                'error' => 'Database not connected'
            ]);
        }

        try {
            $commands = $this->database->getReference('commands')
                ->orderByKey()
                ->limitToLast(10)
                ->getValue();

            return response()->json([
                'success' => true,
                'commands' => $commands ?: []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}