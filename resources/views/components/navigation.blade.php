<nav class="w-64 bg-white shadow-lg min-h-screen">
    <div class="p-6">
        <div class="system-status p-4 rounded-lg text-center text-white mb-6 system-normal" id="systemStatusHeader">
            <i class="fas fa-shield-alt text-2xl mb-2"></i>
            <h3 class="font-bold text-lg">SYSTEM NORMAL</h3>
        </div>

        <div class="status-grid grid grid-cols-2 gap-2 mb-6 text-xs">
            <div class="bg-green-100 text-green-800 p-2 rounded text-center">
                <i class="fas fa-bolt"></i>
                <div>AC POWER</div>
            </div>
            <div class="bg-green-100 text-green-800 p-2 rounded text-center">
                <i class="fas fa-car-battery"></i>
                <div>DC POWER</div>
            </div>
            <div class="bg-red-100 text-red-800 p-2 rounded text-center">
                <i class="fas fa-fire"></i>
                <div>ALARM</div>
            </div>
            <div class="bg-yellow-100 text-yellow-800 p-2 rounded text-center">
                <i class="fas fa-exclamation-triangle"></i>
                <div>TROUBLE</div>
            </div>
        </div>

        <div class="navigation-menu space-y-2">
            <a href="{{ route('monitoring') }}" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-500 text-white">
                <i class="fas fa-home w-6"></i>
                <span>Monitoring</span>
            </a>
        </div>
    </div>
</nav>