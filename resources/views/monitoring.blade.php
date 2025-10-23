@extends('components.layouts.app')

@section('title', 'Monitoring')

@section('content')
    @include('components.header')
    
    <div class="flex">
        @include('components.navigation')
        
        <main class="flex-1">
            <!-- Connection Status -->
            <div id="connectionStatus" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mx-6 mt-6 rounded">
                <p>🔄 Connecting to Firebase...</p>
            </div>

            <!-- Error Message -->
            @if(isset($error))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-6 rounded">
                <p>❌ {{ $error }}</p>
            </div>
            @endif

            <!-- Real Data Sections -->
            @if(!isset($error) || $systemStatus !== 'DISCONNECTED')
                @include('components.sections.monitoring-hero')
                @include('components.sections.monitoring-status')
                @include('components.sections.monitoring-zones')
            @else
                <!-- No Data Available -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mx-6 mt-6 text-center">
                    <div class="text-gray-500 text-lg mb-4">
                        🔌 No Data Available
                    </div>
                    <p class="text-gray-400">
                        Firebase connection failed. Please check your credentials and internet connection.
                    </p>
                </div>
            @endif
        </main>
    </div>

    @include('components.footer')
@endsection

@section('scripts')
<script>
    // Firebase Configuration - REAL CREDENTIALS
    const firebaseConfig = {
        databaseURL: "https://testing1do-default-rtdb.asia-southeast1.firebasedatabase.app"
    };

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

    // Real-time Firebase Listeners
    function initializeFirebaseListeners() {
        // Listen to ALL data changes
        database.ref().on('value', (snapshot) => {
            const allData = snapshot.val();
            if (allData) {
                updateAllData(allData);
                updateConnectionStatus('connected');
            }
        });

        // Handle connection state
        database.ref('.info/connected').on('value', (snapshot) => {
            if (snapshot.val() !== true) {
                updateConnectionStatus('disconnected');
            }
        });
    }

    // Update All Data from Firebase
    function updateAllData(data) {
        // System Status
        if (data.systemStatus) {
            updateSystemStatus(data.systemStatus);
        }
        
        // Slave Data
        if (data.all_slave_data) {
            updateSlaveData(data.all_slave_data);
        }
        
        // Bell Status
        if (data.bell_status) {
            updateBellStatus(data.bell_status);
        }
        
        // Last Update
        if (data.lastUpdateTime) {
            updateLastUpdate(data.lastUpdateTime);
        }
    }

    // ... (rest of your existing JavaScript code)
</script>
@endsection