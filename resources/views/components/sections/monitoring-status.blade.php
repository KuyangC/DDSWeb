<!-- System Status Banner -->
<div class="mx-6 mt-6">
    <div id="systemStatusBanner" class="system-status-banner p-8 rounded-lg text-white text-center system-normal">
        <h2 class="text-4xl font-bold mb-2">SYSTEM NORMAL</h2>
        <p class="text-xl opacity-90">✅ All zones operating normally</p>
    </div>

    <!-- Quick Status Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-sm text-gray-500 mb-1">System Status</div>
            <div id="systemStatus" class="text-2xl font-bold text-green-600">NORMAL</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-sm text-gray-500 mb-1">Bell Status</div>
            <div id="bellStatus" class="text-2xl font-bold text-gray-600">OFF</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-sm text-gray-500 mb-1">Last Update</div>
            <div id="lastUpdateTime" class="text-lg font-semibold text-gray-700">
                {{ \Carbon\Carbon::parse($projectInfo['lastUpdateTime'] ?? now())->format('d M Y H:i:s') }}
            </div>
        </div>
    </div>
</div>