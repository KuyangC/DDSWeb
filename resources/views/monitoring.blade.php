@extends('components.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 p-2">
    <!-- Header Stats -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-3 p-3">
        <div class="flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-800">FIRE ALARM MONITORING - 63 SLAVES (315 ZONES)</h1>
            <div class="flex items-center space-x-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span>System:</span>
                    <span class="font-semibold {{ $systemStatus === 'ACTIVE' ? 'text-green-600' : 'text-red-600' }}" 
                          id="system-status">
                        {{ is_string($systemStatus) ? $systemStatus : 'DISCONNECTED' }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span>Update:</span>
                    <span class="text-xs text-gray-500" id="last-update">{{ now()->format('H:i:s') }}</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-4 gap-2 mt-2 text-xs">
            <div class="text-center p-1 bg-green-100 rounded border border-green-200">
                <div class="font-bold">{{ $stats['normal'] ?? 0 }}/315</div>
                <div class="text-green-700">NORMAL</div>
            </div>
            <div class="text-center p-1 bg-red-100 rounded border border-red-200">
                <div class="font-bold">{{ $stats['alarm'] ?? 0 }}</div>
                <div class="text-red-700">ALARM</div>
            </div>
            <div class="text-center p-1 bg-yellow-100 rounded border border-yellow-200">
                <div class="font-bold">{{ $stats['trouble'] ?? 0 }}</div>
                <div class="text-yellow-700">TROUBLE</div>
            </div>
            <div class="text-center p-1 bg-gray-100 rounded border border-gray-200">
                <div class="font-bold">{{ $stats['offline'] ?? 0 }}</div>
                <div class="text-gray-700">OFFLINE</div>
            </div>
        </div>
    </div>

    <!-- Main Grid - 63 Slaves -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
        <!-- Slave Headers -->
        <div class="grid grid-cols-9 gap-1 mb-2">
            @for($i = 1; $i <= 63; $i++)
                <div class="text-center text-xs font-bold bg-blue-50 py-1 rounded border">
                    S{{ $i }}
                </div>
            @endfor
        </div>

        <!-- Zone Grid - 5 zones per slave -->
        <div class="grid grid-cols-9 gap-1">
            @foreach($slaveData as $slave)
            <div class="border rounded p-1 bg-gray-50 slave-container" data-slave="{{ $slave['slave_number'] }}">
                <!-- Zone Status Indicators WITH TEXT -->
                <div class="grid grid-cols-5 gap-1">
                    @foreach($slave['zones'] as $zone)
                    <div class="zone-indicator status-{{ strtolower($zone['status']) }} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center"
                         data-slave="{{ $slave['slave_number'] }}"
                         data-zone="{{ $zone['number'] }}"
                         data-status="{{ $zone['status'] }}"
                         data-global="{{ $zone['global_number'] }}"
                         onclick="showZoneDetail({{ $slave['slave_number'] }}, {{ $zone['number'] }}, {{ $zone['global_number'] }}, '{{ $zone['status'] }}', {{ $zone['alarm'] ? 'true' : 'false' }}, {{ $zone['trouble'] ? 'true' : 'false' }}, {{ $zone['bell'] ? 'true' : 'false' }}, '{{ $slave['raw_data'] }}')"
                         title="Slave {{ $slave['slave_number'] }} - Zone {{ $zone['number'] }} (Global: {{ $zone['global_number'] }}) - {{ $zone['status'] }}">
                         
                         <!-- TEXT INSIDE ZONE -->
                         <span class="zone-text text-xs font-bold {{ $zone['status'] === 'ALARM' ? 'text-white' : 'text-gray-800' }}">
                             {{ $zone['display_text'] ?? 'Z' . $zone['number'] }}
                         </span>
                    </div>
                    @endforeach
                </div>
                
                <!-- Slave Status Badge -->
                <div class="text-center mt-1">
                    <span class="text-xs px-1 rounded status-badge-{{ strtolower($slave['status']) }}">
                        {{ $slave['bell_active'] ? '🔔' : '' }}{{ $slave['status'] }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Debug Info -->
    <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
        <div class="text-sm text-yellow-800">
            <strong>Debug Info:</strong> 
            Total Slaves: {{ count($slaveData) }}, 
            System Status: {{ $systemStatus ?? 'NULL' }},
            Bell Status: {{ $bellStatus ?? 'NULL' }}
        </div>
    </div>

    <!-- Zone Detail Panel -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-3 p-3">
        <div class="flex justify-between items-center mb-2">
            <h2 class="font-semibold text-sm text-gray-700">ZONE DETAIL</h2>
            <div class="flex space-x-2">
                <button onclick="testConnection()" class="text-xs bg-blue-500 hover:bg-blue-400 text-white px-2 py-1 rounded">
                    Test Connection
                </button>
                <button onclick="clearEventLog()" class="text-xs bg-gray-500 hover:bg-gray-400 text-white px-2 py-1 rounded">
                    Clear Log
                </button>
            </div>
        </div>
        <div id="zone-detail">
            <div class="text-center text-gray-500 text-sm py-4">
                Click on any zone to see details
            </div>
        </div>
    </div>

    <!-- Event Log -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-3">
        <div class="bg-gray-800 text-green-400 font-mono text-xs p-2 rounded-t-lg h-20 overflow-y-auto" id="eventLog">
            <div class="text-gray-400">// System initialized at {{ now()->format('H:i:s') }}</div>
            <div class="text-gray-400">// Total slaves loaded: {{ count($slaveData) }}</div>
            <div class="text-gray-400">// System status: {{ $systemStatus ?? 'unknown' }}</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Show zone details when clicked
function showZoneDetail(slave, zone, globalZone, status, alarm, trouble, bell, rawData) {
    const detailPanel = document.getElementById('zone-detail');
    
    const statusColors = {
        'NORMAL': 'text-green-600 bg-green-50',
        'ALARM': 'text-red-600 bg-red-50 animate-pulse',
        'TROUBLE': 'text-yellow-600 bg-yellow-50',
        'OFFLINE': 'text-gray-600 bg-gray-50'
    };

    detailPanel.innerHTML = `
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="border rounded p-2">
                <div class="text-gray-500 text-xs">Slave</div>
                <div class="font-bold text-lg">${slave}</div>
            </div>
            <div class="border rounded p-2">
                <div class="text-gray-500 text-xs">Zone</div>
                <div class="font-bold text-lg">${zone} (Global: ${globalZone})</div>
            </div>
            <div class="border rounded p-2 ${statusColors[status]}">
                <div class="text-gray-500 text-xs">Status</div>
                <div class="font-bold text-lg">${status} ${bell ? '🔔' : ''}</div>
            </div>
            <div class="border rounded p-2">
                <div class="text-gray-500 text-xs">Raw Data</div>
                <div class="font-mono text-lg">${rawData || 'N/A'}</div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-3 text-xs">
            <div class="text-center p-2 rounded ${alarm ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500'}">
                ALARM: ${alarm ? 'ACTIVE' : 'INACTIVE'}
            </div>
            <div class="text-center p-2 rounded ${trouble ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'}">
                TROUBLE: ${trouble ? 'ACTIVE' : 'INACTIVE'}
            </div>
        </div>
    `;

    // Add to event log
    addToEventLog(`Slave ${slave} Zone ${zone} - ${status} ${bell ? 'BELL ACTIVE' : ''}`);
}

// Test connection
function testConnection() {
    addToEventLog('Testing connection to Firebase...');
    
    fetch('/api/live-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addToEventLog('✓ Connection successful! Data updated.');
                updateDisplay(data);
            } else {
                addToEventLog('✗ Connection failed: ' + data.error);
            }
        })
        .catch(error => {
            addToEventLog('✗ Connection error: ' + error.message);
        });
}

// Add message to event log
function addToEventLog(message) {
    const eventLog = document.getElementById('eventLog');
    const timestamp = new Date().toLocaleTimeString();
    const newEntry = `<div class="mb-1"><span class="text-gray-400">[${timestamp}]</span> ${message}</div>`;
    
    eventLog.innerHTML = newEntry + eventLog.innerHTML;
    
    // Keep log manageable
    if (eventLog.children.length > 20) {
        eventLog.removeChild(eventLog.lastChild);
    }
    
    eventLog.scrollTop = 0;
}

// Clear event log
function clearEventLog() {
    document.getElementById('eventLog').innerHTML = '<div class="text-gray-400">// Event log cleared</div>';
}

// Auto-refresh data
function refreshData() {
    fetch('/api/live-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDisplay(data);
                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                addToEventLog('Data refreshed automatically');
            }
        })
        .catch(error => {
            console.error('Refresh failed:', error);
        });
}

// Update display with new data
function updateDisplay(data) {
    // Update system status
    if (data.systemStatus) {
        const systemEl = document.getElementById('system-status');
        systemEl.textContent = data.systemStatus;
        systemEl.className = 'font-semibold ' + (data.systemStatus === 'ACTIVE' ? 'text-green-600' : 'text-red-600');
    }
    
    // Update zone indicators
    if (data.slaveData && Array.isArray(data.slaveData)) {
        data.slaveData.forEach(slave => {
            if (slave.zones && Array.isArray(slave.zones)) {
                slave.zones.forEach(zone => {
                    const indicator = document.querySelector(`[data-slave="${slave.slave_number}"][data-zone="${zone.number}"]`);
                    if (indicator) {
                        indicator.className = `zone-indicator status-${zone.status.toLowerCase()} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center`;
                        indicator.setAttribute('data-status', zone.status);
                        
                        // Update text inside
                        const textEl = indicator.querySelector('.zone-text');
                        if (textEl) {
                            textEl.className = `zone-text text-xs font-bold ${zone.status === 'ALARM' ? 'text-white' : 'text-gray-800'}`;
                        }
                    }
                });
            }
        });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setInterval(refreshData, 5000); // Refresh every 5 seconds
    setTimeout(refreshData, 2000);  // Initial refresh
    
    addToEventLog('System initialized - Auto refresh enabled');
});
</script>

<style>
.zone-indicator {
    height: 25px;
    border-width: 1px;
    min-width: 25px;
}

/* Status Colors */
.status-normal { background-color: #10b981; border-color: #059669; }
.status-alarm { background-color: #ef4444; border-color: #dc2626; }
.status-trouble { background-color: #f59e0b; border-color: #d97706; }
.status-offline { background-color: #9ca3af; border-color: #6b7280; }

/* Status Badges */
.status-badge-normal { background-color: #d1fae5; color: #065f46; }
.status-badge-alarm { background-color: #fee2e2; color: #991b1b; }
.status-badge-trouble { background-color: #fef3c7; color: #92400e; }
.status-badge-offline { background-color: #f3f4f6; color: #374151; }

/* Text inside zones */
.zone-text {
    text-shadow: 0px 0px 2px rgba(255,255,255,0.8);
    user-select: none;
}

/* Compact grid */
.grid-cols-9 { grid-template-columns: repeat(9, minmax(0, 1fr)); }

/* Animation for alarm */
.animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.slave-container {
    min-height: 60px;
}
</style>
@endsection