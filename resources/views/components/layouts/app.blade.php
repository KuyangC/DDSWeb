<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fire Alarm Monitoring')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Zone Status Styles -->
    <style>
        .zone-normal { 
            background: linear-gradient(135deg, #10B981, #059669); 
            border-color: #059669; 
        }
        .zone-alarm { 
            background: linear-gradient(135deg, #EF4444, #DC2626); 
            border-color: #DC2626; 
        }
        .zone-trouble { 
            background: linear-gradient(135deg, #F59E0B, #D97706); 
            border-color: #D97706; 
        }
        .zone-supervisory { 
            background: linear-gradient(135deg, #8B5CF6, #7C3AED); 
            border-color: #7C3AED; 
        }
        .zone-fault { 
            background: linear-gradient(135deg, #EC4899, #DB2777); 
            border-color: #DB2777; 
        }
        .zone-disconnected { 
            background: linear-gradient(135deg, #6B7280, #4B5563); 
            border-color: #4B5563; 
        }
        .zone-unknown { 
            background: linear-gradient(135deg, #9CA3AF, #6B7280); 
            border-color: #6B7280; 
        }
        
        /* System Status Banner */
        .system-normal { 
            background: linear-gradient(135deg, #10B981, #059669); 
        }
        .system-alarm { 
            background: linear-gradient(135deg, #EF4444, #DC2626); 
        }
        .system-trouble { 
            background: linear-gradient(135deg, #F59E0B, #D97706); 
        }
    </style>
</head>
<body>
    @yield('content')
    @yield('scripts')
</body>
</html>