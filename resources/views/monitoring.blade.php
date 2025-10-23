@extends('components.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 p-2">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-3 p-3">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-lg font-bold text-gray-800" id="project-name">
                    {{ $projectInfo['projectName'] ?? 'DDS - FIRE ALARM MONITORING' }}
                </h1>
                <p class="text-sm text-gray-600" id="project-location">
                    {{ $projectInfo['panelType'] ?? 'Fire Alarm Monitoring System' }}
                </p>
            </div>
            <div class="flex items-center space-x-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span>System:</span>
                    <span class="font-semibold {{ $systemStatus === 'ACTIVE' ? 'text-green-600' : 'text-red-600' }}"
                        id="system-status">
                        {{ $projectInfo['panelType'] ?? 'N/A' }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span>Update:</span>
                    <span class="text-xs text-gray-500" id="last-update">{{ now()->format('H:i:s') }}</span>
                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse" id="refresh-indicator"></div>
                </div>
            </div>
        </div>

        <!-- Quick Stats - Lampu Indikator -->
        <div class="flex justify-center space-x-6 py-3 bg-gray-100 rounded border border-gray-300" id="master-indicators">
            @php
                $indicators = [
                    'ac_power' => ['label' => 'AC POWER', 'color' => 'green'],
                    'dc_power' => ['label' => 'DC POWER', 'color' => 'green'],
                    'alarm_active' => ['label' => 'ALARM', 'color' => 'red'],
                    'trouble_active' => ['label' => 'TROUBLE', 'color' => 'yellow'],
                    'drill' => ['label' => 'DRILL', 'color' => 'blue'],
                    'silenced' => ['label' => 'SILENCED', 'color' => 'orange'],
                    'disabled' => ['label' => 'DISABLED', 'color' => 'gray'],
                ];
            @endphp

            @foreach ($indicators as $key => $indicator)
                <div class="text-center transition-all duration-300 indicator-container" data-indicator="{{ $key }}">
                    <!-- Lampu Indikator -->
                    <div class="w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light
                        {{ $masterStatus[$key] ?? false ? 'bg-' . $indicator['color'] . '-500 border-' . $indicator['color'] . '-600' : 'bg-gray-300 border-gray-400' }} 
                        {{ $masterStatus[$key] ?? false && in_array($key, ['alarm_active', 'trouble_active']) ? 'animate-pulse' : '' }}"
                        id="indicator-{{ $key }}">
                    </div>
                    <!-- Label -->
                    <div class="text-xs font-semibold indicator-label 
                        {{ $masterStatus[$key] ?? false ? 'text-gray-800' : 'text-gray-500' }}"
                        id="label-{{ $key }}">
                        {{ $indicator['label'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Grid - 63 Slaves -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
        <!-- Zone Grid - 63 slaves dengan 5 zones each -->
        <div class="grid grid-cols-9 gap-2" id="slave-grid">
            @foreach ($slaveData as $slave)
            <div class="border rounded-lg p-2 bg-gray-50 slave-container"
                data-slave="{{ $slave['slave_number'] }}" data-status="{{ $slave['status'] }}">

                <!-- Slave Header -->
                <div class="text-center mb-2">
                    <div class="text-xs font-bold text-gray-700 {{ $slave['status'] === 'ALARM' ? 'animate-pulse text-red-600' : '' }}"
                        id="slave-name-{{ $slave['slave_number'] }}">
                        {{ $slave['display_name'] }}
                    </div>
                    @if ($slave['bell_active'])
                    <div class="text-xs text-red-600 font-bold" id="slave-bell-{{ $slave['slave_number'] }}">🔔 BELL</div>
                    @else
                    <div class="text-xs text-red-600 font-bold hidden" id="slave-bell-{{ $slave['slave_number'] }}"></div>
                    @endif
                </div>

                <!-- Zone Status Indicators WITH TEXT -->
                <div class="grid grid-cols-5 gap-1">
                    @foreach ($slave['zones'] as $zone)
                    <div class="zone-indicator status-{{ strtolower($zone['status']) }} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center"
                        id="zone-{{ $slave['slave_number'] }}-{{ $zone['number'] }}"
                        data-slave="{{ $slave['slave_number'] }}" data-zone="{{ $zone['number'] }}"
                        data-status="{{ $zone['status'] }}"
                        onclick="showZoneDetail({{ $slave['slave_number'] }}, {{ $zone['number'] }}, {{ $zone['global_number'] }}, '{{ $zone['status'] }}', {{ $zone['alarm'] ? 'true' : 'false' }}, {{ $zone['trouble'] ? 'true' : 'false' }}, {{ $zone['bell'] ? 'true' : 'false' }}, '{{ $slave['raw_data'] }}')"
                        title="Slave {{ $slave['slave_number'] }} - Zone {{ $zone['number'] }} - {{ $zone['status'] }}">

                        <!-- TEXT INSIDE ZONE -->
                        <span class="zone-text text-xs font-bold {{ $zone['status'] === 'ALARM' ? 'text-white' : 'text-gray-800' }}">
                            {{ $zone['display_text'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Event Log -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-3">
        <div class="bg-gray-800 text-green-400 font-mono text-xs p-2 rounded-t-lg h-40 overflow-y-auto" id="eventLog">
            <div class="text-gray-400">// System initialized at {{ now()->format('H:i:s') }}</div>
            <div class="text-gray-400">// Total slaves loaded: {{ count($slaveData) }}</div>
            <div class="text-gray-400">// System status: {{ $systemStatus ?? 'unknown' }}</div>
            <div class="text-gray-400">// Master status: {{ json_encode($masterStatus) }}</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Show zone details when clicked
function showZoneDetail(slave, zone, globalZone, status, alarm, trouble, bell, rawData) {
    const statusColors = {
        'NORMAL': 'text-green-600 bg-green-50',
        'ALARM': 'text-red-600 bg-red-50 animate-pulse',
        'TROUBLE': 'text-yellow-600 bg-yellow-50',
        'OFFLINE': 'text-gray-600 bg-gray-50'
    };

    // Create or update detail panel
    let detailPanel = document.getElementById('zone-detail-panel');
    if (!detailPanel) {
        detailPanel = document.createElement('div');
        detailPanel.id = 'zone-detail-panel';
        detailPanel.className = 'fixed bottom-4 left-4 right-4 bg-white p-4 rounded-lg shadow-lg border border-gray-300 z-50';
        document.body.appendChild(detailPanel);
    }

    detailPanel.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold text-lg">Zone Details</h3>
            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>
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

    addToEventLog(`Slave ${slave} Zone ${zone} - ${status} ${bell ? 'BELL ACTIVE' : ''}`);
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

// Auto-refresh data - FIXED VERSION
function refreshData() {
    console.log('🔄 Refreshing data...');
    
    fetch('/api/live-status')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Data received:', data);
            if (data.success) {
                updateDisplay(data);
                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                // addToEventLog('Data refreshed'); // Optional: kurangi log yang berulang
            } else {
                console.error('❌ API error:', data.error);
                addToEventLog('Refresh failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('❌ Refresh failed:', error);
            addToEventLog('Refresh error: ' + error.message);
        });
}

// Update display with new data - FIXED VERSION
function updateDisplay(data) {
    console.log('🎯 Updating display with:', data);

    // Update project info
    if (data.projectInfo) {
        const projectNameEl = document.getElementById('project-name');
        if (projectNameEl && data.projectInfo.projectName) {
            projectNameEl.textContent = data.projectInfo.projectName;
        }

        const projectLocationEl = document.getElementById('project-location');
        if (projectLocationEl && data.projectInfo.panelType) {
            projectLocationEl.textContent = data.projectInfo.panelType + ' - Fire Alarm System';
        }
    }

    // Update system status
    if (data.systemStatus) {
        const systemEl = document.getElementById('system-status');
        if (systemEl) {
            systemEl.textContent = data.systemStatus;
            systemEl.className = 'font-semibold ' + (data.systemStatus === 'ACTIVE' ? 'text-green-600' : 'text-red-600');
        }
    }

    // Update master status indicators - FIXED
    if (data.masterStatus) {
        console.log('💡 Updating master indicators:', data.masterStatus);
        
        const indicatorsConfig = {
            'ac_power': { color: 'green', pulse: false },
            'dc_power': { color: 'green', pulse: false },
            'alarm_active': { color: 'red', pulse: true },
            'trouble_active': { color: 'yellow', pulse: true },
            'drill': { color: 'blue', pulse: false },
            'silenced': { color: 'orange', pulse: false },
            'disabled': { color: 'gray', pulse: false }
        };

        Object.keys(indicatorsConfig).forEach(key => {
            const indicator = document.getElementById(`indicator-${key}`);
            const label = document.getElementById(`label-${key}`);
            
            if (indicator && data.masterStatus[key] !== undefined) {
                const config = indicatorsConfig[key];
                const isActive = data.masterStatus[key];
                
                console.log(`   ${key}: ${isActive ? 'ON' : 'OFF'}`);
                
                // Update lampu dengan class yang benar
                if (isActive) {
                    indicator.className = `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-${config.color}-500 border-${config.color}-600 ${config.pulse ? 'animate-pulse' : ''}`;
                } else {
                    indicator.className = `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-gray-300 border-gray-400`;
                }
                
                // Update label
                if (label) {
                    label.className = `text-xs font-semibold indicator-label ${isActive ? 'text-gray-800' : 'text-gray-500'}`;
                }
            }
        });
    }

    // Update slave zones (existing code)
    if (data.slaveData && Array.isArray(data.slaveData)) {
        data.slaveData.forEach(slave => {
            if (slave.zones && Array.isArray(slave.zones)) {
                slave.zones.forEach(zone => {
                    const indicator = document.querySelector(`[data-slave="${slave.slave_number}"][data-zone="${zone.number}"]`);
                    if (indicator) {
                        indicator.className = `zone-indicator status-${zone.status.toLowerCase()} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center`;
                        indicator.setAttribute('data-status', zone.status);
                        
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

// Initialize - FIXED
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 System initializing...');
    
    // Auto refresh every 3 seconds (bisa disesuaikan)
    setInterval(refreshData, 3000);
    
    // Initial refresh setelah 1 detik
    setTimeout(refreshData, 1000);
    
    addToEventLog('System initialized - Auto refresh enabled (3s)');
});

// Manual refresh function (bisa dipanggil dari button jika perlu)
function manualRefresh() {
    addToEventLog('Manual refresh triggered');
    refreshData();
}
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

/* Text inside zones */
.zone-text {
    text-shadow: 0px 0px 2px rgba(255,255,255,0.8);
    user-select: none;
}

/* Compact grid */
.grid-cols-9 { grid-template-columns: repeat(9, minmax(0, 1fr)); }

/* Animation for alarm & indicators */
.animate-pulse { 
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; 
}

@keyframes pulse {
    0%, 100% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.7;
        transform: scale(1.05);
    }
}

.slave-container {
    min-height: 60px;
}

/* Indicator lights */
.indicator-light {
    transition: all 0.3s ease;
}

.indicator-container:hover .indicator-light {
    transform: scale(1.1);
}
</style>
@endsection