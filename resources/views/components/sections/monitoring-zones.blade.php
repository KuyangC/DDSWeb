<!-- Zones Status Section -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mx-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-800">ZONE STATUS</h2>
        <div class="text-sm text-gray-500">
            Last Update: <span id="zonesLastUpdate">{{ \Carbon\Carbon::parse($projectInfo['lastUpdateTime'] ?? now())->format('H:i:s') }}</span>
        </div>
    </div>

    <!-- Raw Data Display -->
    <div class="mb-4 p-3 bg-gray-50 rounded border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Raw Slave Data:</div>
        <div id="slaveData" class="font-mono text-xs text-gray-800 break-all">
            {{ $allSlaveData['raw_data'] ?? 'Waiting for data...' }}
        </div>
    </div>

    <!-- Zones Container -->
    <div id="zonesContainer">
        @if(isset($zoneStatuses) && count($zoneStatuses) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($zoneStatuses as $zone)
                <div class="zone-card p-4 rounded-lg border-2 text-white 
                    {{ $zone['status'] === 'ALARM' ? 'zone-alarm' :
                       ($zone['status'] === 'FAULT' ? 'zone-fault' :
                       ($zone['status'] === 'SUPERVISORY' ? 'zone-trouble' : 'zone-normal')) }}">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-semibold">{{ $zone['name'] }}</div>
                            <div class="text-xs opacity-80">#{{ $zone['number'] }}</div>
                        </div>
                        <i class="fas 
                            {{ $zone['status'] === 'ALARM' ? 'fa-fire' :
                               ($zone['status'] === 'FAULT' ? 'fa-ban' :
                               ($zone['status'] === 'SUPERVISORY' ? 'fa-exclamation-triangle' : 'fa-check-circle')) }}"></i>
                    </div>
                    <div class="text-sm mt-2 opacity-90">
                        {{ $zone['status'] }} ({{ $zone['code'] }})
                    </div>
                    @if(isset($zone['raw_data']))
                    <div class="text-xs mt-1 opacity-70 truncate" title="{{ $zone['raw_data'] }}">
                        {{ $zone['raw_data'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500">No zone data available</p>
                <p class="text-sm text-gray-400 mt-2">Waiting for Firebase data...</p>
            </div>
        @endif
    </div>
</div>