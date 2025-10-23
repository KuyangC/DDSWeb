<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDS Fire Alarm System - @yield('title', 'Monitoring')</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-database-compat.js"></script>
    
    <style>
        .system-normal { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
        .system-alarm { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); }
        .system-trouble { background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); }
        .zone-normal { background-color: #10B981; }
        .zone-alarm { background-color: #EF4444; animation: pulse 1s infinite; }
        .zone-trouble { background-color: #F59E0B; }
        .zone-fault { background-color: #6B7280; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    @yield('content')
    @yield('scripts')
</body>
</html>