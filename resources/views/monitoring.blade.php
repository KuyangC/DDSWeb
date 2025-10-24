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
                    <!-- Connection Status -->
                    <div class="flex items-center space-x-2">
                        <span>Connection:</span>
                        <span class="font-semibold {{ $firebaseConnected ? 'text-green-600' : 'text-red-600' }}"
                            id="connection-status">
                            {{ $firebaseConnected ? 'CONNECTED' : 'DISCONNECTED' }}
                        </span>
                        <div class="w-2 h-2 rounded-full {{ $firebaseConnected ? 'bg-green-500' : 'bg-red-500' }}"
                            id="connection-indicator"></div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span>Update:</span>
                        <span class="text-xs text-gray-500" id="last-update">{{ now()->format('H:i:s') }}</span>
                        <div class="w-2 h-2 bg-blue-500 rounded-full" id="refresh-indicator"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats - Lampu Indikator -->
            <div class="flex justify-center space-x-6 py-3 bg-gray-100 rounded border border-gray-300"
                id="master-indicators">
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
                    <div class="text-center transition-all duration-300 indicator-container"
                        data-indicator="{{ $key }}">
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

            <!-- TOMBOL CONTROL PANEL -->
            <div class="flex justify-center space-x-4 py-3 bg-blue-50 rounded border border-blue-200 mt-3">
                <button onclick="systemReset()"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold text-sm command-btn">
                    SYSTEM RESET
                </button>
                <button onclick="drill()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm command-btn">
                    DRILL
                </button>
                <button onclick="acknowledge()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold text-sm command-btn">
                    ACKNOWLEDGE
                </button>
                <button onclick="silence()"
                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-semibold text-sm command-btn">
                    SILENCE
                </button>
            </div>
        </div>

        <!-- Main Grid - 63 Slaves -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <!-- Zone Grid - 63 slaves dengan 5 zones each -->
            <div class="grid grid-cols-7 gap-2" id="slave-grid">
                @foreach ($slaveData as $slave)
                    <div class="border rounded-lg p-2 bg-gray-50 slave-container" data-slave="{{ $slave['slave_number'] }}"
                        data-status="{{ $slave['status'] }}">

                        <!-- Slave Header -->
                        <div class="text-center mb-2">
                            <div class="text-xs font-bold text-gray-700 {{ $slave['status'] === 'ALARM' ? 'animate-pulse text-red-600' : '' }}"
                                id="slave-name-{{ $slave['slave_number'] }}">
                                {{ $slave['display_name'] }}
                            </div>
                            @if ($slave['bell_active'])
                                <div class="text-xs text-red-600 font-bold" id="slave-bell-{{ $slave['slave_number'] }}">🔔
                                    BELL</div>
                            @else
                                <div class="text-xs text-red-600 font-bold hidden"
                                    id="slave-bell-{{ $slave['slave_number'] }}"></div>
                            @endif
                        </div>

                        <!-- Zone Status Indicators WITH TEXT -->
                        <div class="grid grid-cols-5 gap-0">
                            @foreach ($slave['zones'] as $zone)
                                <div class="zone-indicator status-{{ strtolower($zone['status']) }} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center"
                                    id="zone-{{ $slave['slave_number'] }}-{{ $zone['number'] }}"
                                    data-slave="{{ $slave['slave_number'] }}" data-zone="{{ $zone['number'] }}"
                                    data-status="{{ $zone['status'] }}"
                                    onclick="showZoneDetail({{ $slave['slave_number'] }}, {{ $zone['number'] }}, {{ $zone['global_number'] }}, '{{ $zone['status'] }}', {{ $zone['alarm'] ? 'true' : 'false' }}, {{ $zone['trouble'] ? 'true' : 'false' }}, {{ $zone['bell'] ? 'true' : 'false' }}, '{{ $slave['raw_data'] }}')"
                                    title="Slave {{ $slave['slave_number'] }} - Zone {{ $zone['number'] }} - {{ $zone['status'] }}">

                                    <!-- TEXT INSIDE ZONE -->
                                    <span
                                        class="zone-text text-xs font-bold {{ $zone['status'] === 'ALARM' ? 'text-white' : 'text-gray-800' }}">
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

    <!-- AUDIO ELEMENTS UNTUK SOUND ALERT -->
    <audio id="alarm-sound" preload="auto">
        <source src="{{ asset('sounds/alarm.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/alarm.wav') }}" type="audio/wav">
    </audio>

    <audio id="trouble-sound" preload="auto">
        <source src="{{ asset('sounds/trouble.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/trouble.wav') }}" type="audio/wav">
    </audio>
@endsection

@section('scripts')
    <script>
        // Sound alert variables
        let alarmSound = document.getElementById('alarm-sound');
        let troubleSound = document.getElementById('trouble-sound');
        let isAlarmPlaying = false;
        let isTroublePlaying = false;

        // Control Panel Functions - LOCAL MODE
        function systemReset() {
            if (confirm('🔄 RESET SYSTEM?\nAll alarms will be cleared.')) {
                addToEventLog('🔄 SYSTEM RESET command sent');

                // LOCAL RESET - Set semua ke normal
                resetAllSlavesToNormal();

                // Matikan SEMUA lampu indicator
                updateMasterIndicator('drill', false);
                updateMasterIndicator('silenced', false);
                updateMasterIndicator('alarm_active', false);
                updateMasterIndicator('trouble_active', false);
                updateMasterIndicator('ac_power', false);
                updateMasterIndicator('dc_power', false);
                updateMasterIndicator('disabled', false);

                stopAllSounds();
                addToEventLog('🔇 All alarms silenced - System reset locally');

                // Show visual feedback
                showCommandFeedback('SYSTEM_RESET', 'success');
            }
        }

        function drill() {
            if (confirm('🚨 ACTIVATE DRILL MODE?\nAll slaves will go to ALARM state.')) {
                addToEventLog('🔊 DRILL command sent - Activating all slaves to ALARM');

                // LOCAL DRILL - Set semua ke ALARM
                setAllSlavesToAlarm();
                updateMasterIndicator('drill', true);
                updateMasterIndicator('alarm_active', true);

                // Juga nyalakan AC POWER dan DC POWER untuk simulasi real panel
                updateMasterIndicator('ac_power', true);
                updateMasterIndicator('dc_power', true);

                // Auto play alarm sound untuk drill
                setTimeout(() => {
                    playAlarmSound();
                }, 500);

                // Show visual feedback
                showCommandFeedback('DRILL', 'success');
            }
        }

        function acknowledge() {
            addToEventLog('✅ ACKNOWLEDGE command sent');

            // LOCAL ACKNOWLEDGE - Trigger lampu acknowledge saja
            // Jangan matikan alarm_active, cukup flash saja
            flashMasterIndicator('alarm_active');

            // Stop alarm sound sementara (5 detik)
            if (isAlarmPlaying) {
                stopAlarmSound();
                addToEventLog('🔇 Alarm sound silenced temporarily (5s)');
                setTimeout(() => {
                    if (window.hasSystemAlarm) {
                        playAlarmSound();
                        addToEventLog('🔊 Alarm sound resumed');
                    }
                }, 5000);
            }

            // Show visual feedback
            showCommandFeedback('ACKNOWLEDGE', 'success');
        }

        function silence() {
            addToEventLog('🔇 SILENCE command sent');

            // LOCAL SILENCE - Trigger lampu silence saja
            updateMasterIndicator('silenced', true);
            flashMasterIndicator('silenced');

            stopAllSounds();
            addToEventLog('🔇 All sounds silenced locally');

            // Show visual feedback
            showCommandFeedback('SILENCE', 'success');
        }

        // Fungsi untuk set semua slave ke ALARM (LOCAL)
        function setAllSlavesToAlarm() {
            const slaveContainers = document.querySelectorAll('.slave-container');
            const zoneIndicators = document.querySelectorAll('.zone-indicator');

            // Update semua slave container
            slaveContainers.forEach(slave => {
                slave.setAttribute('data-status', 'ALARM');
                const slaveName = slave.querySelector('[id^="slave-name-"]');
                const slaveBell = slave.querySelector('[id^="slave-bell-"]');

                if (slaveName) {
                    slaveName.classList.add('animate-pulse', 'text-red-600');
                }
                if (slaveBell) {
                    slaveBell.classList.remove('hidden');
                    slaveBell.textContent = '🔔 BELL';
                }
            });

            // Update semua zone indicator
            zoneIndicators.forEach(zone => {
                zone.className =
                    'zone-indicator status-alarm rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center';
                zone.setAttribute('data-status', 'ALARM');

                const textEl = zone.querySelector('.zone-text');
                if (textEl) {
                    textEl.className = 'zone-text text-xs font-bold text-white';
                }
            });

            // Set system alarm flag
            window.hasSystemAlarm = true;
        }

        // Fungsi untuk reset semua slave ke NORMAL (LOCAL)
        function resetAllSlavesToNormal() {
            const slaveContainers = document.querySelectorAll('.slave-container');
            const zoneIndicators = document.querySelectorAll('.zone-indicator');

            // Update semua slave container
            slaveContainers.forEach(slave => {
                slave.setAttribute('data-status', 'NORMAL');
                const slaveName = slave.querySelector('[id^="slave-name-"]');
                const slaveBell = slave.querySelector('[id^="slave-bell-"]');

                if (slaveName) {
                    slaveName.classList.remove('animate-pulse', 'text-red-600');
                }
                if (slaveBell) {
                    slaveBell.classList.add('hidden');
                    slaveBell.textContent = '';
                }
            });

            // Update semua zone indicator
            zoneIndicators.forEach(zone => {
                zone.className =
                    'zone-indicator status-normal rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center';
                zone.setAttribute('data-status', 'NORMAL');

                const textEl = zone.querySelector('.zone-text');
                if (textEl) {
                    textEl.className = 'zone-text text-xs font-bold text-gray-800';
                }
            });

            // Reset system alarm flag
            window.hasSystemAlarm = false;
        }

        // Fungsi untuk update master indicator
        function updateMasterIndicator(indicatorKey, isActive) {
            const indicator = document.getElementById(`indicator-${indicatorKey}`);
            const label = document.getElementById(`label-${indicatorKey}`);

            if (!indicator) return;

            const indicatorConfig = {
                'ac_power': {
                    color: 'green',
                    pulse: false
                },
                'dc_power': {
                    color: 'green',
                    pulse: false
                },
                'alarm_active': {
                    color: 'red',
                    pulse: true
                },
                'trouble_active': {
                    color: 'yellow',
                    pulse: true
                },
                'drill': {
                    color: 'blue',
                    pulse: false
                },
                'silenced': {
                    color: 'orange',
                    pulse: false
                },
                'disabled': {
                    color: 'gray',
                    pulse: false
                }
            };

            const config = indicatorConfig[indicatorKey];

            if (isActive) {
                // Aktif - gunakan warna sesuai config
                indicator.className =
                    `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-${config.color}-500 border-${config.color}-600 ${config.pulse ? 'animate-pulse' : ''}`;
            } else {
                // Nonaktif - gunakan gray
                indicator.className =
                    `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-gray-300 border-gray-400`;
            }

            // Update label
            if (label) {
                label.className = `text-xs font-semibold indicator-label ${isActive ? 'text-gray-800' : 'text-gray-500'}`;
            }
        }

        // Fungsi untuk flash indicator (untuk acknowledge/silence)
        function flashMasterIndicator(indicatorKey) {
            const indicator = document.getElementById(`indicator-${indicatorKey}`);
            if (!indicator) return;

            // Flash effect dengan animasi yang lebih smooth
            indicator.classList.add('flash');
            setTimeout(() => {
                indicator.classList.remove('flash');
            }, 900);
        }

        // Fungsi untuk reset tombol ke state semula
        function resetButton(button, originalText) {
            button.textContent = originalText;
            button.disabled = false;
            button.classList.remove('sending', 'bg-green-500', 'bg-red-500');

            // Reset ke warna asli berdasarkan command
            if (originalText === 'SYSTEM RESET') {
                button.classList.add('bg-red-600');
            } else if (originalText === 'DRILL') {
                button.classList.add('bg-blue-600');
            } else if (originalText === 'ACKNOWLEDGE') {
                button.classList.add('bg-green-600');
            } else if (originalText === 'SILENCE') {
                button.classList.add('bg-orange-600');
            }
        }

        // MODIFIED: Fungsi untuk kirim command - LOCAL MODE
        function sendCommand(command) {
            console.log(`Sending LOCAL command: ${command}`);

            // Show loading state
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Sending...';
            button.disabled = true;
            button.classList.add('sending');

            // Add sending indicator
            addToEventLog(`📤 Sending LOCAL command: ${command}`);

            // LOCAL MODE: Langsung execute tanpa fetch ke server
            setTimeout(() => {
                // Simulate success response
                const success = true;

                if (success) {
                    button.textContent = '✅ Sent';
                    button.classList.remove('sending');
                    button.classList.add('bg-green-500');
                    addToEventLog(`✅ ${command} executed successfully (LOCAL)`);

                    // Show visual feedback
                    showCommandFeedback(command, 'success');

                    // Reset button after successful execution
                    setTimeout(() => {
                        resetButton(button, originalText);
                    }, 2000);
                }
            }, 800); // Simulate network delay
        }

        // Fungsi untuk menampilkan feedback visual
        function showCommandFeedback(command, status) {
            // Create or update feedback element
            let feedbackEl = document.getElementById('command-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.id = 'command-feedback';
                feedbackEl.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50';
                document.body.appendChild(feedbackEl);
            }

            const bgColor = status === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = status === 'success' ? '✅' : '❌';

            feedbackEl.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 text-white ${bgColor}`;
            feedbackEl.innerHTML = `
        <div class="flex items-center space-x-2">
            <span class="text-lg">${icon}</span>
            <span class="font-semibold">${command} ${status === 'success' ? 'Executed' : 'Failed'}</span>
        </div>
    `;

            // Auto hide setelah 3 detik
            setTimeout(() => {
                if (feedbackEl.parentNode) {
                    feedbackEl.parentNode.removeChild(feedbackEl);
                }
            }, 3000);
        }

        // Sound Alert Functions
        function playAlarmSound() {
            if (alarmSound && !isAlarmPlaying) {
                alarmSound.loop = true;
                alarmSound.play().then(() => {
                    isAlarmPlaying = true;
                    console.log('🚨 ALARM sound playing');
                }).catch(error => {
                    console.log('Alarm sound play failed:', error);
                });
            }
        }

        function playTroubleSound() {
            if (troubleSound && !isTroublePlaying) {
                troubleSound.loop = true;
                troubleSound.play().then(() => {
                    isTroublePlaying = true;
                    console.log('⚠️ TROUBLE sound playing');
                }).catch(error => {
                    console.log('Trouble sound play failed:', error);
                });
            }
        }

        function stopAlarmSound() {
            if (alarmSound && isAlarmPlaying) {
                alarmSound.pause();
                alarmSound.currentTime = 0;
                isAlarmPlaying = false;
                console.log('🔇 ALARM sound stopped');
            }
        }

        function stopTroubleSound() {
            if (troubleSound && isTroublePlaying) {
                troubleSound.pause();
                troubleSound.currentTime = 0;
                isTroublePlaying = false;
                console.log('🔇 TROUBLE sound stopped');
            }
        }

        function stopAllSounds() {
            stopAlarmSound();
            stopTroubleSound();
        }

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
                detailPanel.className =
                    'fixed bottom-4 left-4 right-4 bg-white p-4 rounded-lg shadow-lg border border-gray-300 z-50';
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

        // Auto-refresh data dengan pengecekan connection
        function refreshData() {
            console.log('🔄 Refreshing data...');

            // Show refreshing indicator
            const indicator = document.getElementById('refresh-indicator');
            if (indicator) {
                indicator.classList.add('bg-blue-500', 'animate-pulse');
            }

            fetch('/api/live-status')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (indicator) {
                        indicator.classList.remove('bg-blue-500', 'animate-pulse');
                        indicator.classList.add('bg-green-500');
                    }

                    if (data.success) {
                        updateDisplay(data);
                        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();

                        // Reset indicator setelah 2 detik
                        setTimeout(() => {
                            if (indicator) {
                                indicator.classList.remove('bg-green-500');
                            }
                        }, 2000);
                    } else {
                        console.error('❌ API error:', data.error);
                        addToEventLog('Refresh failed: ' + (data.error || 'Unknown error'));
                        if (indicator) {
                            indicator.classList.add('bg-red-500');
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Refresh failed:', error);
                    addToEventLog('Refresh error: ' + error.message);
                    if (indicator) {
                        indicator.classList.add('bg-red-500');
                    }
                });
        }

        // Update display with new data
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

            // Update connection status
            const connectionStatusEl = document.getElementById('connection-status');
            const connectionIndicatorEl = document.getElementById('connection-indicator');
            if (connectionStatusEl && connectionIndicatorEl) {
                if (data.success) {
                    connectionStatusEl.textContent = 'CONNECTED';
                    connectionStatusEl.className = 'font-semibold text-green-600';
                    connectionIndicatorEl.className = 'w-2 h-2 rounded-full bg-green-500';
                } else {
                    connectionStatusEl.textContent = 'DISCONNECTED';
                    connectionStatusEl.className = 'font-semibold text-red-600';
                    connectionIndicatorEl.className = 'w-2 h-2 rounded-full bg-red-500';
                }
            }

            // ✅ CHECK SLAVE DATA UNTUK SYSTEM ALARM/TROUBLE
            let hasSystemAlarm = false;
            let hasSystemTrouble = false;

            if (data.slaveData && Array.isArray(data.slaveData)) {
                data.slaveData.forEach(slave => {
                    if (slave.status === 'ALARM') {
                        hasSystemAlarm = true;
                        console.log(`🚨 SYSTEM ALARM from Slave ${slave.slave_number}`);
                    } else if (slave.status === 'TROUBLE') {
                        hasSystemTrouble = true;
                        console.log(`⚠️ SYSTEM TROUBLE from Slave ${slave.slave_number}`);
                    }
                });
            }

            // 🆕 SOUND ALERT MANAGEMENT
            window.hasSystemAlarm = hasSystemAlarm;
            window.hasSystemTrouble = hasSystemTrouble;

            if (hasSystemAlarm && !isAlarmPlaying) {
                playAlarmSound();
                addToEventLog('🚨 ALARM DETECTED - Sound activated');
            } else if (!hasSystemAlarm && isAlarmPlaying) {
                stopAlarmSound();
                addToEventLog('🔇 ALARM CLEARED - Sound stopped');
            }

            if (hasSystemTrouble && !isTroublePlaying) {
                playTroubleSound();
                addToEventLog('⚠️ TROUBLE DETECTED - Sound activated');
            } else if (!hasSystemTrouble && isTroublePlaying) {
                stopTroubleSound();
                addToEventLog('🔇 TROUBLE CLEARED - Sound stopped');
            }

            // Update master status indicators - WITH SYSTEM STATUS
            const indicatorsConfig = {
                'ac_power': {
                    color: 'green',
                    pulse: false
                },
                'dc_power': {
                    color: 'green',
                    pulse: false
                },
                'alarm_active': {
                    color: 'red',
                    pulse: true
                },
                'trouble_active': {
                    color: 'yellow',
                    pulse: true
                },
                'drill': {
                    color: 'blue',
                    pulse: false
                },
                'silenced': {
                    color: 'orange',
                    pulse: false
                },
                'disabled': {
                    color: 'gray',
                    pulse: false
                }
            };

            Object.keys(indicatorsConfig).forEach(key => {
                const indicator = document.getElementById(`indicator-${key}`);
                const label = document.getElementById(`label-${key}`);

                if (indicator) {
                    const config = indicatorsConfig[key];
                    let isActive = false;

                    // Tentukan status berdasarkan data yang ada
                    if (key === 'alarm_active') {
                        isActive = hasSystemAlarm;
                    } else if (key === 'trouble_active') {
                        isActive = hasSystemTrouble;
                    } else if (data.masterStatus && data.masterStatus[key] !== undefined) {
                        isActive = data.masterStatus[key];
                    }

                    console.log(`   ${key}: ${isActive ? 'ON' : 'OFF'}`);

                    // Update lampu
                    if (isActive) {
                        indicator.className =
                            `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-${config.color}-500 border-${config.color}-600 ${config.pulse ? 'animate-pulse' : ''}`;
                    } else {
                        indicator.className =
                            `w-6 h-6 rounded-full mx-auto mb-1 border-2 shadow-sm indicator-light bg-gray-300 border-gray-400`;
                    }

                    // Update label
                    if (label) {
                        label.className =
                            `text-xs font-semibold indicator-label ${isActive ? 'text-gray-800' : 'text-gray-500'}`;
                    }
                }
            });

            // Update system status
            if (data.systemStatus) {
                const systemEl = document.getElementById('system-status');
                if (systemEl) {
                    systemEl.textContent = data.systemStatus;
                    systemEl.className = 'font-semibold ' + (data.systemStatus === 'ACTIVE' ? 'text-green-600' :
                        'text-red-600');
                }
            }

            // Update slave zones
            if (data.slaveData && Array.isArray(data.slaveData)) {
                data.slaveData.forEach(slave => {
                    if (slave.zones && Array.isArray(slave.zones)) {
                        slave.zones.forEach(zone => {
                            const indicator = document.querySelector(
                                `[data-slave="${slave.slave_number}"][data-zone="${zone.number}"]`);
                            if (indicator) {
                                indicator.className =
                                    `zone-indicator status-${zone.status.toLowerCase()} rounded border cursor-pointer hover:shadow-sm transition-all flex items-center justify-center`;
                                indicator.setAttribute('data-status', zone.status);

                                const textEl = indicator.querySelector('.zone-text');
                                if (textEl) {
                                    textEl.className =
                                        `zone-text text-xs font-bold ${zone.status === 'ALARM' ? 'text-white' : 'text-gray-800'}`;
                                }
                            }
                        });
                    }
                });
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 System initializing...');

            // Auto refresh every 3 seconds
            setInterval(refreshData, 3000);

            // Initial refresh setelah 1 detik
            setTimeout(refreshData, 1000);

            addToEventLog('System initialized - Auto refresh enabled (3s)');
            addToEventLog('🔊 Sound alerts ready');
        });

        // Manual refresh function
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
        .status-normal {
            background-color: #10b981;
            border-color: #059669;
        }

        .status-alarm {
            background-color: #ef4444;
            border-color: #dc2626;
        }

        .status-trouble {
            background-color: #f59e0b;
            border-color: #d97706;
        }

        .status-offline {
            background-color: #9ca3af;
            border-color: #6b7280;
        }

        /* Text inside zones */
        .zone-text {
            text-shadow: 0px 0px 2px rgba(255, 255, 255, 0.8);
            user-select: none;
        }

        /* Compact grid */
        .grid-cols-9 {
            grid-template-columns: repeat(9, minmax(0, 1fr));
        }

        /* Animation for alarm & indicators */
        .animate-pulse {
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
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

        /* Styles untuk tombol command */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Transition untuk perubahan status */
        button {
            transition: all 0.3s ease;
        }

        /* Animation untuk sending state */
        .sending {
            animation: pulse 1s infinite;
            opacity: 0.8;
        }

        /* Flash animation untuk indicators */
        @keyframes flash {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.1);
            }
        }

        .flash {
            animation: flash 0.3s ease-in-out 2;
        }

        /* Smooth transitions untuk semua perubahan */
        .slave-container,
        .zone-indicator,
        .indicator-light {
            transition: all 0.3s ease-in-out;
        }

        /* Pastikan warna Tailwind terbaca */
        .bg-green-500 {
            background-color: #10B981;
        }

        .bg-red-500 {
            background-color: #EF4444;
        }

        .bg-yellow-500 {
            background-color: #F59E0B;
        }

        .bg-blue-500 {
            background-color: #3B82F6;
        }

        .bg-orange-500 {
            background-color: #F97316;
        }

        .bg-gray-500 {
            background-color: #6B7280;
        }

        .bg-gray-300 {
            background-color: #D1D5DB;
        }

        .border-green-600 {
            border-color: #059669;
        }

        .border-red-600 {
            border-color: #DC2626;
        }

        .border-yellow-600 {
            border-color: #D97706;
        }

        .border-blue-600 {
            border-color: #2563EB;
        }

        .border-orange-600 {
            border-color: #EA580C;
        }

        .border-gray-600 {
            border-color: #4B5563;
        }

        .border-gray-400 {
            border-color: #9CA3AF;
        }
    </style>
@endsection
