<!-- Di monitoring-zones.blade.php -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="zonesContainer">
    @foreach($zoneStatuses as $zone)
    <div class="zone-card p-4 rounded-lg border-2 text-white 
        {{ $zone['status'] === 'ALARM' ? 'zone-alarm' :
           ($zone['status'] === 'TROUBLE' ? 'zone-trouble' :
           ($zone['status'] === 'SUPERVISORY' ? 'zone-supervisory' :
           ($zone['status'] === 'FAULT' ? 'zone-fault' :
           ($zone['status'] === 'DISCONNECTED' ? 'zone-disconnected' : 'zone-normal')))) }}">
        
        <div class="flex justify-between items-center">
            <div>
                <div class="font-semibold">{{ $zone['name'] }}</div>
                <div class="text-xs opacity-80">#{{ $zone['number'] }}</div>
            </div>
            <i class="fas 
                {{ $zone['status'] === 'ALARM' ? 'fa-fire' :
                   ($zone['status'] === 'TROUBLE' ? 'fa-exclamation-triangle' :
                   ($zone['status'] === 'SUPERVISORY' ? 'fa-eye' :
                   ($zone['status'] === 'FAULT' ? 'fa-ban' :
                   ($zone['status'] === 'DISCONNECTED' ? 'fa-unlink' : 'fa-check-circle')))) }}"></i>
        </div>
        
        <div class="text-sm mt-2 opacity-90">
            {{ $zone['status'] }} ({{ $zone['code'] }})
        </div>
        
        @if(isset($zone['note']))
        <div class="text-xs mt-1 opacity-70">
            ⚠️ {{ $zone['note'] }}
        </div>
        @endif
        
        <div class="text-xs text-gray-300 mt-1 truncate" title="{{ $zone['raw_data'] }}">
            Data: {{ $zone['raw_data'] }}
        </div>
    </div>
    @endforeach
</div>