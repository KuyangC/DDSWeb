<!-- Project Header -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mx-6 mt-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Left Side - Project Info -->
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-xl font-bold text-gray-800" id="projectName">
                    {{ $projectInfo['projectName'] ?? 'RUMAH BAHLIL' }}
                </h1>
                <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-medium" id="panelType">
                    {{ $projectInfo['panelType'] ?? 'DDS-ADD-P1' }}
                </span>
            </div>
            <div class="text-gray-500 text-xs">
                Updated: <span id="lastUpdate">{{ $projectInfo['lastUpdateTime'] ?? '2025-10-23T21:55:25.184749' }}</span>
            </div>
        </div>

        <!-- Right Side - Stats -->
        <div class="flex gap-4">
            <div class="text-center">
                <div class="font-bold text-blue-600" id="loopCount">{{ $projectInfo['loop'] ?? '1' }}</div>
                <div class="text-gray-500 text-xs">LOOP</div>
            </div>
            <div class="text-center">
                <div class="font-bold text-blue-600" id="moduleCount">{{ $projectInfo['numberOfModules'] ?? '32' }}</div>
                <div class="text-gray-500 text-xs">MODULES</div>
            </div>
            <div class="text-center">
                <div class="font-bold text-blue-600" id="zoneCount">{{ $projectInfo['numberOfZones'] ?? '160' }}</div>
                <div class="text-gray-500 text-xs">ZONES</div>
            </div>
        </div>
    </div>

    <!-- Active Zones -->
    <div class="border-t border-gray-200 mt-3 pt-3">
        <div class="text-sm">
            <span class="text-gray-700 font-medium">Active: </span>
            <span class="text-green-500" id="activeZones">
                {{ $projectInfo['activeZone'] ?? '#001#Zone 01, #002#Zone 02, #003#Zone 03, #004#Zone 04, #005#Zone 05, #006#Zone 06, #007#Zone 07, #008#Zone 08, #009#Zone 09, #010#Zone 10' }}
            </span>
        </div>
    </div>
</div>