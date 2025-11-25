<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Gas Leakage Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .gauge-container {
            position: relative;
            width: 200px;
            height: 100px;
            overflow: hidden;
            margin: 0 auto;
        }
        .gauge-arc {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background-color: #e5e7eb;
            position: absolute;
            top: 0;
            left: 0;
        }
        .gauge-fill {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 0;
            background: conic-gradient(from 270deg, #22c55e 0%, #eab308 50%, #ef4444 100%);
            transform-origin: center;
            transform: rotate(-180deg); 
            transition: transform 1s ease-out;
            clip-path: polygon(0 0, 100% 0, 100% 50%, 0 50%);
        }
        .gauge-cover {
            width: 160px;
            height: 160px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        /* Pulse animation for danger */
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .danger-pulse {
            animation: pulse-red 2s infinite;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fa-solid fa-fire-flame-simple text-orange-500 text-2xl mr-3"></i>
                    <h1 class="text-xl font-bold text-slate-800">IoT Gas Monitor</h1>
                </div>
                <div class="flex items-center">
                    <span id="connectionStatus" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <span class="w-2 h-2 mr-1 bg-gray-400 rounded-full"></span>
                        Disconnected
                    </span>
                    <button onclick="toggleSettings()" class="ml-4 text-slate-500 hover:text-slate-700">
                        <i class="fa-solid fa-gear text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Settings Modal -->
    <div id="settingsPanel" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Configuration</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ThingSpeak Channel ID</label>
                    <input type="text" id="channelIdInput" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="e.g., 123456">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Read API Key</label>
                    <input type="text" id="apiKeyInput" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="e.g., ABC123XYZ">
                </div>
                <div class="flex justify-end gap-2">
                    <button onclick="toggleSettings()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Close</button>
                    <button onclick="saveSettings()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save & Connect</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Setup Prompt (Only visible if no keys) -->
        <div id="setupPrompt" class="hidden bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-circle-info text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Please click the gear icon <i class="fa-solid fa-gear"></i> in the top right to configure your ThingSpeak Channel ID and Read API Key.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Real-time Status Card -->
            <div id="statusCard" class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-gray-300 transition-colors duration-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div id="statusIconBg" class="rounded-md bg-gray-100 p-3 transition-colors duration-500">
                                <i id="statusIcon" class="fa-solid fa-wind text-gray-500 text-2xl transition-colors duration-500"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Status</dt>
                                <dd class="flex items-baseline">
                                    <div id="statusText" class="text-2xl font-semibold text-gray-900">Waiting for data...</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-gray-500">Last updated: </span>
                        <span id="lastUpdated" class="font-medium text-gray-900">Never</span>
                    </div>
                </div>
            </div>

            <!-- Gauge Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mb-4">Gas Concentration</h3>
                    
                    <div class="gauge-container">
                        <div class="gauge-arc"></div>
                        <div id="gaugeFill" class="gauge-fill" style="transform: rotate(-180deg);"></div>
                        <div class="gauge-cover">
                            <span id="gasValueDisplay" class="text-3xl font-bold text-slate-800">0</span>
                            <span class="text-xs text-slate-500">PPM</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between text-xs text-gray-400 mt-2 px-8">
                        <span>0</span>
                        <span>2000</span>
                        <span>4095</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="mt-8">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Readings</h3>
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gas Value</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTable" class="bg-white divide-y divide-gray-200">
                                    <!-- Rows generated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const THRESHOLD = 2500; // Must match your Arduino Code
        const UPDATE_INTERVAL = 15000; // 15 seconds (Thingspeak limit)
        let refreshTimer = null;

        // DOM Elements
        const els = {
            channelId: document.getElementById('channelIdInput'),
            apiKey: document.getElementById('apiKeyInput'),
            settingsPanel: document.getElementById('settingsPanel'),
            setupPrompt: document.getElementById('setupPrompt'),
            statusCard: document.getElementById('statusCard'),
            statusText: document.getElementById('statusText'),
            statusIcon: document.getElementById('statusIcon'),
            statusIconBg: document.getElementById('statusIconBg'),
            gasValueDisplay: document.getElementById('gasValueDisplay'),
            gaugeFill: document.getElementById('gaugeFill'),
            lastUpdated: document.getElementById('lastUpdated'),
            connectionStatus: document.getElementById('connectionStatus'),
            historyTable: document.getElementById('historyTable')
        };

        // Initialize
        function init() {
            const storedChannel = localStorage.getItem('ts_channel_id');
            const storedKey = localStorage.getItem('ts_api_key');

            if (storedChannel && storedKey) {
                els.channelId.value = storedChannel;
                els.apiKey.value = storedKey;
                startMonitoring();
            } else {
                els.setupPrompt.classList.remove('hidden');
                toggleSettings();
            }
        }

        function toggleSettings() {
            els.settingsPanel.classList.toggle('hidden');
        }

        function saveSettings() {
            const channel = els.channelId.value.trim();
            const key = els.apiKey.value.trim();

            if (!channel || !key) {
                alert("Please enter both Channel ID and API Key");
                return;
            }

            localStorage.setItem('ts_channel_id', channel);
            localStorage.setItem('ts_api_key', key);
            
            toggleSettings();
            els.setupPrompt.classList.add('hidden');
            startMonitoring();
        }

        function startMonitoring() {
            if (refreshTimer) clearInterval(refreshTimer);
            fetchData();
            refreshTimer = setInterval(fetchData, UPDATE_INTERVAL);
        }

        async function fetchData() {
            const channel = localStorage.getItem('ts_channel_id');
            const key = localStorage.getItem('ts_api_key');
            
            // UI Loading state
            els.connectionStatus.innerHTML = '<span class="w-2 h-2 mr-1 bg-yellow-400 rounded-full animate-pulse"></span> Updating...';

            try {
                // Fetch last 5 results to populate history and current status
                const response = await fetch(`https://api.thingspeak.com/channels/${channel}/feeds.json?api_key=${key}&results=5`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                
                if (data.feeds && data.feeds.length > 0) {
                    updateDashboard(data.feeds);
                    els.connectionStatus.innerHTML = '<span class="w-2 h-2 mr-1 bg-green-500 rounded-full"></span> Live';
                    els.connectionStatus.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800";
                } else {
                    els.statusText.innerText = "No Data Yet";
                }

            } catch (error) {
                console.error("Error fetching data:", error);
                els.connectionStatus.innerHTML = '<span class="w-2 h-2 mr-1 bg-red-500 rounded-full"></span> Error';
                els.connectionStatus.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800";
            }
        }

        function updateDashboard(feeds) {
            const latest = feeds[feeds.length - 1];
            const gasValue = parseInt(latest.field1) || 0;
            const date = new Date(latest.created_at);
            
            // 1. Update Numeric Displays
            els.gasValueDisplay.innerText = gasValue;
            els.lastUpdated.innerText = date.toLocaleTimeString();

            // 2. Update Gauge (Map 0-4095 to -180 to 0 degrees)
            // 0 = -180deg, 4095 = 0deg
            const maxVal = 4095;
            const percentage = Math.min(gasValue / maxVal, 1);
            const rotation = -180 + (percentage * 180);
            els.gaugeFill.style.transform = `rotate(${rotation}deg)`;

            // 3. Update Status (Safe vs Danger)
            const isDanger = gasValue >= THRESHOLD;
            
            if (isDanger) {
                // Danger State
                els.statusCard.className = "bg-white overflow-hidden shadow rounded-lg border-t-4 border-red-500 transition-colors duration-500 danger-pulse";
                els.statusText.innerText = "LEAK DETECTED";
                els.statusText.className = "text-2xl font-bold text-red-600";
                els.statusIcon.className = "fa-solid fa-triangle-exclamation text-red-500 text-2xl";
                els.statusIconBg.className = "rounded-md bg-red-100 p-3";
            } else {
                // Safe State
                els.statusCard.className = "bg-white overflow-hidden shadow rounded-lg border-t-4 border-green-500 transition-colors duration-500";
                els.statusText.innerText = "Air Quality Normal";
                els.statusText.className = "text-2xl font-semibold text-green-700";
                els.statusIcon.className = "fa-solid fa-check text-green-500 text-2xl";
                els.statusIconBg.className = "rounded-md bg-green-100 p-3";
            }

            // 4. Update History Table
            els.historyTable.innerHTML = '';
            // Show new records at top
            [...feeds].reverse().forEach(feed => {
                const val = parseInt(feed.field1);
                const isHigh = val >= THRESHOLD;
                const rowTime = new Date(feed.created_at).toLocaleString();
                
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${rowTime}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${val}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${isHigh ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                                ${isHigh ? 'Danger' : 'Safe'}
                            </span>
                        </td>
                    </tr>
                `;
                els.historyTable.innerHTML += row;
            });
        }

        // Start
        init();

    </script>
</body>
</html>