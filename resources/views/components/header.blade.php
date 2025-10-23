<!-- Project Header dengan Tailwind CSS -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
    <!-- Project Title dan Info -->
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            {{ $projectInfo['projectName'] ?? 'RUMAH BAHLIL' }}
        </h1>
        <div class="flex flex-wrap items-center gap-4">
            <span class="bg-blue-600 text-white px-3 py-1 rounded-md text-sm font-medium">
                {{ $projectInfo['panelType'] ?? 'DDS-ADD-P1' }}
            </span>
            <span class="text-gray-500 text-sm">
                Last Update: {{ \Carbon\Carbon::parse($projectInfo['lastUpdateTime'] ?? '2025-10-23T21:55:25.184749')->format('d M Y H:i:s') }}
            </span>
        </div>
    </div>

    <!-- Active Zones -->
    <div class="border-t border-gray-200 pt-4 mb-4">
        <div class="flex items-start gap-2">
            <span class="text-gray-700 font-semibold whitespace-nowrap pt-1">Active Zones:</span>
            <span class="text-green-500 font-medium text-sm leading-relaxed">
                {{ $projectInfo['activeZone'] ?? '#001#Zone 01, #002#Zone 02, #003#Zone 03, #004#Zone 04, #005#Zone 05, #006#Zone 06, #007#Zone 07, #008#Zone 08, #009#Zone 09, #010#Zone 10' }}
            </span>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">
                {{ $projectInfo['loop'] ?? '1' }}
            </div>
            <div class="text-gray-500 text-sm uppercase tracking-wide">
                Loop
            </div>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">
                {{ $projectInfo['numberOfModules'] ?? '32' }}
            </div>
            <div class="text-gray-500 text-sm uppercase tracking-wide">
                Modules
            </div>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">
                {{ $projectInfo['numberOfZones'] ?? '160' }}
            </div>
            <div class="text-gray-500 text-sm uppercase tracking-wide">
                Zones
            </div>
        </div>
    </div>
</div>