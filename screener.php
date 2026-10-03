<?php

/**
 * 🚀 DEMAND & SUPPLY SWING TRADING SCREENER (AJAX VERSION)
 * Modern Web Dashboard - No Timeouts
 */

$API_URL = "https://ravionpharma.in/angelone_mcp_http.php";
$API_KEY = "8a92d05db7b5c3a8be47f87ba16c2bb7";

// ----------------------------------------------------------------------
// BACKEND API LOGIC
// ----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'scan') {
    header('Content-Type: application/json');
    
    $sym = $_GET['symbol'] ?? '';
    $token = $_GET['token'] ?? '';
    $segment = $_GET['segment'] ?? 'Unknown';
    
    if (empty($sym) || empty($token)) {
        echo json_encode(['error' => 'Missing symbol or token']);
        exit;
    }
    
    function fetch_historical_data($token, $from_date, $to_date) {
        global $API_URL, $API_KEY;
        
        $payload = [
            "jsonrpc" => "2.0",
            "method" => "tools/call",
            "id" => 1,
            "params" => [
                "name" => "get_historical_data",
                "arguments" => [
                    "symbol_token" => $token,
                    "interval" => "ONE_DAY",
                    "from_date" => $from_date,
                    "to_date" => $to_date
                ]
            ]
        ];
        
        $ch = curl_init($API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code != 200 || !$response) return null;
        
        $data = json_decode($response, true);
        if (isset($data['result']['content'][0]['text'])) {
            $content = json_decode($data['result']['content'][0]['text'], true);
            if (isset($content['candles'])) return $content['candles'];
        }
        return null;
    }
    
    function calculate_atr(&$df) {
        $period = 14;
        for ($i = 0; $i < count($df); $i++) {
            if ($i == 0) {
                $df[$i]['tr'] = $df[$i]['high'] - $df[$i]['low'];
                $df[$i]['atr'] = $df[$i]['tr'];
            } else {
                $hl = $df[$i]['high'] - $df[$i]['low'];
                $hc = abs($df[$i]['high'] - $df[$i - 1]['close']);
                $lc = abs($df[$i]['low'] - $df[$i - 1]['close']);
                $df[$i]['tr'] = max($hl, $hc, $lc);
                
                if ($i < $period) {
                    $sum = 0;
                    for ($j = 0; $j <= $i; $j++) $sum += $df[$j]['tr'];
                    $df[$i]['atr'] = $sum / ($i + 1);
                } else {
                    $sum = 0;
                    for ($j = $i - $period + 1; $j <= $i; $j++) $sum += $df[$j]['tr'];
                    $df[$i]['atr'] = $sum / $period;
                }
            }
        }
    }
    
    function add_supply_demand_zones(&$df) {
        for ($i = 0; $i < count($df); $i++) {
            $range = max($df[$i]['high'] - $df[$i]['low'], 0.01);
            $body = abs($df[$i]['close'] - $df[$i]['open']);
            $body_pct = $body / $range;
            
            $df[$i]['range'] = $range;
            $df[$i]['body_pct'] = $body_pct;
            
            $atr = isset($df[$i]['atr']) ? $df[$i]['atr'] : $range;
            
            $df[$i]['is_erc'] = ($body_pct > 0.5 && $range > (1.2 * $atr));
            $df[$i]['erc_dir'] = ($df[$i]['close'] > $df[$i]['open']) ? 1 : -1;
            $df[$i]['is_base'] = ($body_pct <= 0.5);
            
            $df[$i]['demand_created'] = 0;
            $df[$i]['demand_proximal'] = null;
            $df[$i]['demand_distal'] = null;
        }
        
        for ($i = 14; $i < count($df); $i++) {
            if (!$df[$i]['is_erc'] || $df[$i]['erc_dir'] != 1) continue;
            
            $base_count = 0;
            $base_high = 0;
            $base_low = PHP_INT_MAX;
            $valid_pattern = false;
            
            for ($j = $i - 1; $j >= max(0, $i - 7); $j--) {
                if ($df[$j]['is_base']) {
                    $base_count++;
                    $body_high = max($df[$j]['open'], $df[$j]['close']);
                    $base_high = max($base_high, $body_high);
                    $base_low = min($base_low, $df[$j]['low']);
                } elseif ($df[$j]['is_erc']) {
                    if ($base_count >= 1 && $base_count <= 6) $valid_pattern = true;
                    break;
                } else {
                    break;
                }
            }
            
            if ($valid_pattern) {
                $df[$i]['demand_created'] = 1;
                $df[$i]['demand_proximal'] = $base_high;
                $df[$i]['demand_distal'] = $base_low;
            }
        }
    }
    
    $to_date = date("Y-m-d H:i");
    $from_date = date("Y-m-d H:i", strtotime("-150 days"));
    
    $candles = fetch_historical_data($token, $from_date, $to_date);
    if (!$candles || count($candles) < 50) {
        echo json_encode(['symbol' => $sym, 'status' => 'No Data']);
        exit;
    }
    
    $df = [];
    foreach ($candles as $c) {
        $df[] = [
            'timestamp' => $c[0],
            'open' => $c[1],
            'high' => $c[2],
            'low' => $c[3],
            'close' => $c[4],
            'volume' => $c[5]
        ];
    }
    
    calculate_atr($df);
    add_supply_demand_zones($df);
    
    $current_price = $df[count($df) - 1]['close'];
    
    $active_zones = [];
    for ($i = 0; $i < count($df); $i++) {
        $row = $df[$i];
        if ($row['demand_created'] == 1) {
            $active_zones[] = [
                'proximal' => $row['demand_proximal'],
                'distal' => $row['demand_distal'],
                'created_date' => date('Y-m-d', strtotime($row['timestamp']))
            ];
        }
        
        $valid_zones = [];
        foreach ($active_zones as $zone) {
            $sl_price = $zone['distal'] * 0.99;
            if ($row['low'] < $sl_price) continue;
            $valid_zones[] = $zone;
        }
        $active_zones = $valid_zones;
    }
    
    if (empty($active_zones)) {
        echo json_encode(['symbol' => $sym, 'status' => 'None']);
        exit;
    }
    
    $best_zone = $active_zones[count($active_zones) - 1];
    $entry_price = $best_zone['proximal'];
    $sl_price = $best_zone['distal'] * 0.99;
    $risk = $entry_price - $sl_price;
    $target = $entry_price + (3.0 * $risk);
    
    $dist_to_entry = (($current_price - $entry_price) / $entry_price) * 100;
    
    $status = "None";
    if ($dist_to_entry <= 2.0 && $dist_to_entry >= 0) {
        $status = "Actionable";
    } elseif ($dist_to_entry < 0 && $current_price > $sl_price) {
        $status = "Inside Zone";
    } elseif ($dist_to_entry > 2.0 && $dist_to_entry <= 5.0) {
        $status = "Approaching";
    }
    
    if ($status !== "None") {
        $created_ts = strtotime($best_zone['created_date']);
        $age_days = floor((time() - $created_ts) / (60 * 60 * 24));
        
        echo json_encode([
            'symbol' => $sym,
            'segment' => $segment,
            'status' => $status,
            'cmp' => $current_price,
            'entry' => $entry_price,
            'sl' => $sl_price,
            'target' => $target,
            'age' => $age_days
        ]);
    } else {
        echo json_encode(['symbol' => $sym, 'status' => 'None']);
    }
    exit;
}

// ----------------------------------------------------------------------
// FRONTEND UI (HTML/JS)
// ----------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Demand & Supply Screener</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --surface-color: rgba(30, 41, 59, 0.7);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-orange: #f97316;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, var(--bg-color));
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease-out;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .glass-panel {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--accent-blue), #2563eb);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }

        .btn:disabled {
            background: #475569;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .progress-container {
            flex-grow: 1;
            margin: 0 2rem;
        }

        .progress-bar-bg {
            background: rgba(255, 255, 255, 0.1);
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, var(--accent-blue), #8b5cf6);
            height: 100%;
            width: 0%;
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .status-text {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.actionable { background: rgba(16, 185, 129, 0.2); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge.inside { background: rgba(245, 158, 11, 0.2); color: var(--accent-yellow); border: 1px solid rgba(245, 158, 11, 0.4); }
        .badge.approaching { background: rgba(249, 115, 22, 0.2); color: var(--accent-orange); border: 1px solid rgba(249, 115, 22, 0.4); }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Demand & Supply Screener</h1>
            <p class="subtitle">Real-time Institutional Zone Scanner</p>
        </header>

        <div class="glass-panel controls">
            <button id="startBtn" class="btn">Start Live Scan</button>
            <div class="progress-container">
                <div class="progress-bar-bg">
                    <div id="progressBar" class="progress-bar-fill"></div>
                </div>
                <div id="statusText" class="status-text">Ready to scan 104 stocks.</div>
            </div>
        </div>

        <div class="glass-panel">
            <table>
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Status</th>
                        <th>CMP</th>
                        <th>Entry</th>
                        <th>Stop Loss</th>
                        <th>Target (1:3)</th>
                        <th>Zone Age</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                    <tr><td colspan="7" class="empty-state">No results yet. Click "Start Live Scan" to begin.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const STOCKS = [{"symbol": "RELIANCE-EQ", "token": "2885", "name": "Reliance Industries", "segment": "Large Cap", "sector": "Energy"}, {"symbol": "TCS-EQ", "token": "11536", "name": "Tata Consultancy Services", "segment": "Large Cap", "sector": "IT"}, {"symbol": "HDFCBANK-EQ", "token": "1333", "name": "HDFC Bank", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "ICICIBANK-EQ", "token": "4963", "name": "ICICI Bank", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "INFOSYS-EQ", "token": "1594", "name": "Infosys", "segment": "Large Cap", "sector": "IT"}, {"symbol": "HINDUNILVR-EQ", "token": "1394", "name": "Hindustan Unilever", "segment": "Large Cap", "sector": "FMCG"}, {"symbol": "ITC-EQ", "token": "1660", "name": "ITC", "segment": "Large Cap", "sector": "FMCG"}, {"symbol": "SBIN-EQ", "token": "3045", "name": "State Bank of India", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "BHARTIARTL-EQ", "token": "10604", "name": "Bharti Airtel", "segment": "Large Cap", "sector": "Telecom"}, {"symbol": "KOTAKBANK-EQ", "token": "1922", "name": "Kotak Mahindra Bank", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "BAJFINANCE-EQ", "token": "317", "name": "Bajaj Finance", "segment": "Large Cap", "sector": "NBFC"}, {"symbol": "LT-EQ", "token": "11483", "name": "Larsen & Toubro", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "HCLTECH-EQ", "token": "7229", "name": "HCL Technologies", "segment": "Large Cap", "sector": "IT"}, {"symbol": "MARUTI-EQ", "token": "10999", "name": "Maruti Suzuki", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "AXISBANK-EQ", "token": "5900", "name": "Axis Bank", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "SUNPHARMA-EQ", "token": "3351", "name": "Sun Pharma", "segment": "Large Cap", "sector": "Pharma"}, {"symbol": "TITAN-EQ", "token": "3506", "name": "Titan Company", "segment": "Large Cap", "sector": "Consumer"}, {"symbol": "NTPC-EQ", "token": "11630", "name": "NTPC", "segment": "Large Cap", "sector": "Power"}, {"symbol": "POWERGRID-EQ", "token": "14977", "name": "Power Grid", "segment": "Large Cap", "sector": "Power"}, {"symbol": "TATAMOTORS-EQ", "token": "3456", "name": "Tata Motors", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "ADANIENT-EQ", "token": "25", "name": "Adani Enterprises", "segment": "Large Cap", "sector": "Conglomerate"}, {"symbol": "ADANIPORTS-EQ", "token": "15083", "name": "Adani Ports", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "COALINDIA-EQ", "token": "20374", "name": "Coal India", "segment": "Large Cap", "sector": "Mining"}, {"symbol": "TATASTEEL-EQ", "token": "3499", "name": "Tata Steel", "segment": "Large Cap", "sector": "Metals"}, {"symbol": "M&M-EQ", "token": "2031", "name": "Mahindra & Mahindra", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "WIPRO-EQ", "token": "3787", "name": "Wipro", "segment": "Large Cap", "sector": "IT"}, {"symbol": "ONGC-EQ", "token": "2475", "name": "ONGC", "segment": "Large Cap", "sector": "Energy"}, {"symbol": "JSWSTEEL-EQ", "token": "11723", "name": "JSW Steel", "segment": "Large Cap", "sector": "Metals"}, {"symbol": "HINDALCO-EQ", "token": "1363", "name": "Hindalco Industries", "segment": "Large Cap", "sector": "Metals"}, {"symbol": "BPCL-EQ", "token": "526", "name": "BPCL", "segment": "Large Cap", "sector": "Energy"}, {"symbol": "HDFCAMC-EQ", "token": "4244", "name": "HDFCAMC", "segment": "Large Cap", "sector": "Finance"}, {"symbol": "CHOLAFIN-EQ", "token": "685", "name": "CHOLAFIN", "segment": "Large Cap", "sector": "Finance"}, {"symbol": "BAJAJFINSV-EQ", "token": "16675", "name": "BAJAJFINSV", "segment": "Large Cap", "sector": "Finance"}, {"symbol": "INDUSINDBK-EQ", "token": "5258", "name": "INDUSINDBK", "segment": "Large Cap", "sector": "Banking"}, {"symbol": "NESTLEIND-EQ", "token": "17963", "name": "NESTLEIND", "segment": "Large Cap", "sector": "FMCG"}, {"symbol": "BRITANNIA-EQ", "token": "547", "name": "BRITANNIA", "segment": "Large Cap", "sector": "FMCG"}, {"symbol": "GODREJCP-EQ", "token": "10099", "name": "GODREJCP", "segment": "Large Cap", "sector": "FMCG"}, {"symbol": "EICHERMOT-EQ", "token": "910", "name": "EICHERMOT", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "HEROMOTOCO-EQ", "token": "1348", "name": "HEROMOTOCO", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "BAJAJ-AUTO-EQ", "token": "16669", "name": "BAJAJ-AUTO", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "TVSMOTOR-EQ", "token": "8479", "name": "TVSMOTOR", "segment": "Large Cap", "sector": "Auto"}, {"symbol": "DRREDDY-EQ", "token": "881", "name": "DRREDDY", "segment": "Large Cap", "sector": "Pharma"}, {"symbol": "CIPLA-EQ", "token": "694", "name": "CIPLA", "segment": "Large Cap", "sector": "Pharma"}, {"symbol": "DIVISLAB-EQ", "token": "10940", "name": "DIVISLAB", "segment": "Large Cap", "sector": "Pharma"}, {"symbol": "APOLLOHOSP-EQ", "token": "157", "name": "APOLLOHOSP", "segment": "Large Cap", "sector": "Healthcare"}, {"symbol": "ULTRACEMCO-EQ", "token": "11532", "name": "ULTRACEMCO", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "GRASIM-EQ", "token": "1232", "name": "GRASIM", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "SHREECEM-EQ", "token": "3103", "name": "SHREECEM", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "AMBUJACEM-EQ", "token": "1270", "name": "AMBUJACEM", "segment": "Large Cap", "sector": "Infrastructure"}, {"symbol": "DLF-EQ", "token": "14732", "name": "DLF", "segment": "Large Cap", "sector": "Real Estate"}, {"symbol": "GODREJPROP-EQ", "token": "17875", "name": "GODREJPROP", "segment": "Large Cap", "sector": "Real Estate"}, {"symbol": "TRENT-EQ", "token": "1964", "name": "Trent", "segment": "Mid Cap", "sector": "Retail"}, {"symbol": "IRFC-EQ", "token": "2029", "name": "IRFC", "segment": "Mid Cap", "sector": "Finance"}, {"symbol": "POLYCAB-EQ", "token": "9590", "name": "Polycab India", "segment": "Mid Cap", "sector": "Electrical"}, {"symbol": "PERSISTENT-EQ", "token": "18365", "name": "Persistent Systems", "segment": "Mid Cap", "sector": "IT"}, {"symbol": "PIIND-EQ", "token": "24647", "name": "PI Industries", "segment": "Mid Cap", "sector": "Chemicals"}, {"symbol": "CANBK-EQ", "token": "10794", "name": "Canara Bank", "segment": "Mid Cap", "sector": "Banking"}, {"symbol": "FEDERALBNK-EQ", "token": "1023", "name": "Federal Bank", "segment": "Mid Cap", "sector": "Banking"}, {"symbol": "IDFCFIRSTB-EQ", "token": "11184", "name": "IDFC First Bank", "segment": "Mid Cap", "sector": "Banking"}, {"symbol": "LUPIN-EQ", "token": "10440", "name": "Lupin", "segment": "Mid Cap", "sector": "Pharma"}, {"symbol": "MUTHOOTFIN-EQ", "token": "23650", "name": "Muthoot Finance", "segment": "Mid Cap", "sector": "NBFC"}, {"symbol": "PAGEIND-EQ", "token": "14413", "name": "Page Industries", "segment": "Mid Cap", "sector": "Textile"}, {"symbol": "INDHOTEL-EQ", "token": "1512", "name": "Indian Hotels", "segment": "Mid Cap", "sector": "Hotels"}, {"symbol": "TATAPOWER-EQ", "token": "3426", "name": "Tata Power", "segment": "Mid Cap", "sector": "Power"}, {"symbol": "PEL-EQ", "token": "2689", "name": "Piramal Enterprises", "segment": "Mid Cap", "sector": "Diversified"}, {"symbol": "VOLTAS-EQ", "token": "3718", "name": "Voltas", "segment": "Mid Cap", "sector": "Consumer Durables"}, {"symbol": "MFSL-EQ", "token": "27706", "name": "Max Financial Services", "segment": "Mid Cap", "sector": "Insurance"}, {"symbol": "COFORGE-EQ", "token": "11543", "name": "Coforge", "segment": "Mid Cap", "sector": "IT"}, {"symbol": "ASHOKLEY-EQ", "token": "212", "name": "Ashok Leyland", "segment": "Mid Cap", "sector": "Auto"}, {"symbol": "RECLTD-EQ", "token": "15355", "name": "REC Limited", "segment": "Mid Cap", "sector": "Finance"}, {"symbol": "PFC-EQ", "token": "14299", "name": "Power Finance Corp", "segment": "Mid Cap", "sector": "Finance"}, {"symbol": "MPHASIS-EQ", "token": "4503", "name": "MPHASIS", "segment": "Mid Cap", "sector": "IT"}, {"symbol": "OFSS-EQ", "token": "10738", "name": "OFSS", "segment": "Mid Cap", "sector": "IT"}, {"symbol": "MAXHEALTH-EQ", "token": "22377", "name": "MAXHEALTH", "segment": "Mid Cap", "sector": "Healthcare"}, {"symbol": "AUROPHARMA-EQ", "token": "275", "name": "AUROPHARMA", "segment": "Mid Cap", "sector": "Pharma"}, {"symbol": "DIXON-EQ", "token": "21690", "name": "DIXON", "segment": "Mid Cap", "sector": "Consumer Durables"}, {"symbol": "HAVELLS-EQ", "token": "9819", "name": "HAVELLS", "segment": "Mid Cap", "sector": "Consumer Durables"}, {"symbol": "CROMPTON-EQ", "token": "17094", "name": "CROMPTON", "segment": "Mid Cap", "sector": "Consumer Durables"}, {"symbol": "INDUSTOWER-EQ", "token": "29135", "name": "INDUSTOWER", "segment": "Mid Cap", "sector": "Telecom"}, {"symbol": "PRESTIGE-EQ", "token": "20302", "name": "PRESTIGE", "segment": "Mid Cap", "sector": "Real Estate"}, {"symbol": "IGL-EQ", "token": "11262", "name": "IGL", "segment": "Mid Cap", "sector": "Energy"}, {"symbol": "MGL-EQ", "token": "17534", "name": "MGL", "segment": "Mid Cap", "sector": "Energy"}, {"symbol": "PNB-EQ", "token": "10666", "name": "PNB", "segment": "Mid Cap", "sector": "Banking"}, {"symbol": "MOTHERSON-EQ", "token": "4204", "name": "MOTHERSON", "segment": "Mid Cap", "sector": "Auto"}, {"symbol": "IRCTC-EQ", "token": "13611", "name": "IRCTC", "segment": "Small Cap", "sector": "Travel"}, {"symbol": "IEX-EQ", "token": "2400", "name": "Indian Energy Exchange", "segment": "Small Cap", "sector": "Exchange"}, {"symbol": "DEEPAKNTR-EQ", "token": "17388", "name": "Deepak Nitrite", "segment": "Small Cap", "sector": "Chemicals"}, {"symbol": "NATIONALUM-EQ", "token": "6364", "name": "National Aluminium", "segment": "Small Cap", "sector": "Metals"}, {"symbol": "SAIL-EQ", "token": "2963", "name": "SAIL", "segment": "Small Cap", "sector": "Metals"}, {"symbol": "BEL-EQ", "token": "383", "name": "Bharat Electronics", "segment": "Small Cap", "sector": "Defence"}, {"symbol": "HAL-EQ", "token": "2303", "name": "Hindustan Aeronautics", "segment": "Small Cap", "sector": "Defence"}, {"symbol": "NHPC-EQ", "token": "2455", "name": "NHPC", "segment": "Small Cap", "sector": "Power"}, {"symbol": "BANKBARODA-EQ", "token": "4668", "name": "Bank of Baroda", "segment": "Small Cap", "sector": "Banking"}, {"symbol": "GAIL-EQ", "token": "4717", "name": "GAIL", "segment": "Small Cap", "sector": "Energy"}, {"symbol": "IOC-EQ", "token": "1624", "name": "Indian Oil Corp", "segment": "Small Cap", "sector": "Energy"}, {"symbol": "BHEL-EQ", "token": "438", "name": "BHEL", "segment": "Small Cap", "sector": "Capital Goods"}, {"symbol": "SUZLON-EQ", "token": "13987", "name": "Suzlon Energy", "segment": "Small Cap", "sector": "Renewable"}, {"symbol": "TATAELXSI-EQ", "token": "3407", "name": "Tata Elxsi", "segment": "Small Cap", "sector": "IT"}, {"symbol": "LTTS-EQ", "token": "18564", "name": "L&T Technology Services", "segment": "Small Cap", "sector": "IT"}, {"symbol": "LICHSGFIN-EQ", "token": "1997", "name": "LIC Housing Finance", "segment": "Small Cap", "sector": "NBFC"}, {"symbol": "MANAPPURAM-EQ", "token": "2142", "name": "Manappuram Finance", "segment": "Small Cap", "sector": "NBFC"}, {"symbol": "PETRONET-EQ", "token": "11351", "name": "Petronet LNG", "segment": "Small Cap", "sector": "Energy"}, {"symbol": "NMDC-EQ", "token": "15332", "name": "NMDC", "segment": "Small Cap", "sector": "Mining"}, {"symbol": "HINDPETRO-EQ", "token": "1406", "name": "Hindustan Petroleum", "segment": "Small Cap", "sector": "Energy"}];
        
        const startBtn = document.getElementById('startBtn');
        const progressBar = document.getElementById('progressBar');
        const statusText = document.getElementById('statusText');
        const resultsBody = document.getElementById('resultsBody');
        
        let isScanning = false;
        
        function getBadgeClass(status) {
            if (status === 'Actionable') return 'badge actionable';
            if (status === 'Inside Zone') return 'badge inside';
            if (status === 'Approaching') return 'badge approaching';
            return 'badge';
        }
        
        function appendResultRow(data) {
            // Remove empty state if present
            if (resultsBody.querySelector('.empty-state')) {
                resultsBody.innerHTML = '';
            }
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${data.symbol}</strong> <span style="color:#94a3b8; font-size:0.8rem; margin-left:8px;">${data.segment}</span></td>
                <td><span class="${getBadgeClass(data.status)}">${data.status}</span></td>
                <td>₹${data.cmp.toFixed(2)}</td>
                <td><strong>₹${data.entry.toFixed(2)}</strong></td>
                <td style="color:#ef4444;">₹${data.sl.toFixed(2)}</td>
                <td style="color:#10b981;">₹${data.target.toFixed(2)}</td>
                <td>${data.age} Days</td>
            `;
            resultsBody.appendChild(tr);
        }

        async function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        async function runScan() {
            if (isScanning) return;
            
            isScanning = true;
            startBtn.disabled = true;
            startBtn.innerText = "Scanning...";
            resultsBody.innerHTML = '';
            
            let completed = 0;
            const total = STOCKS.length;
            
            for (let i = 0; i < total; i++) {
                const stock = STOCKS[i];
                statusText.innerText = `Fetching ${stock.symbol}... (${i+1}/${total})`;
                
                try {
                    const res = await fetch(`screener.php?action=scan&symbol=${stock.symbol}&token=${stock.token}&segment=${stock.segment}`);
                    const data = await res.json();
                    
                    if (data && data.status !== 'None' && data.status !== 'No Data') {
                        appendResultRow(data);
                    }
                } catch (e) {
                    console.error("Error fetching", stock.symbol, e);
                }
                
                completed++;
                progressBar.style.width = `${(completed / total) * 100}%`;
                
                // Wait 0.5s between AJAX calls to avoid 429 Too Many Requests
                await sleep(500); 
            }
            
            statusText.innerText = `Scan Complete! ${completed} stocks processed.`;
            startBtn.innerText = "Scan Complete";
            isScanning = false;
            
            if (resultsBody.children.length === 0) {
                resultsBody.innerHTML = '<tr><td colspan="7" class="empty-state">No actionable zones found today.</td></tr>';
            }
        }

        startBtn.addEventListener('click', runScan);
    </script>
</body>
</html>
