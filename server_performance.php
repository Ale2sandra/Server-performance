<?php
// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Your session variables
$userIdDb = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
$fullname = isset($_SESSION['login_session']) ? $_SESSION['login_session'] : '';
$username = isset($_SESSION['useremail']) ? $_SESSION['useremail'] : '';

// Check if session variables are empty
if (empty($userIdDb)) {
    echo "User ID is missing from the session.<br>";
}
if (empty($fullname)) {
    echo "Full Name is missing from the session.<br>";
}
// Initialize variables
$idleFree = 0;
$cpuLoad = 0;

// Get the number of available CPU cores dynamically
$numCores = shell_exec("nproc"); // This will return the number of processing units available
$numCores = intval(trim($numCores)); // Convert to integer

$coreUsage = array_fill(0, $numCores, 0);  // Initialize core usage array with the number of cores

$mpstatRaw = shell_exec("mpstat -P ALL 1 1");
if ($mpstatRaw) {
    $lines = explode("\n", $mpstatRaw);
    $idlePercentages = []; // Array to hold idle percentages for each core
    foreach ($lines as $line) {
        // Adjust regex to capture the correct core usage
        if (preg_match("/(\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)/", $line, $matches)) {
            $coreId = intval($matches[1]);
            $idle = floatval($matches[7]);
            if ($coreId < $numCores) { // Ensure coreId is within bounds
                $coreUsage[$coreId] = round(100 - $idle, 2); // This should reflect the actual usage 
                $idlePercentages[] = $idle; // Store idle percentage for average calculation
            }
        }
    }

    // Calculate the average idle percentage
    if (!empty($idlePercentages)) {
        $cpuIdleAverage = array_sum($idlePercentages) / count($idlePercentages);
        $idleFree = round($cpuIdleAverage, 2); // Average idle
        $cpuLoad = round(100 - $cpuIdleAverage, 2); // Total CPU load should be 100 - average idle
    } else {
        // Fallback if we have no idle data
        $cpuLoad = 0;
        $idleFree = 100;
    }
}


// Memory Usage
$totalMemory = (float) shell_exec("grep MemTotal /proc/meminfo | awk '{print $2}'") / 1024;
$freeMemory = (float) shell_exec("grep MemAvailable /proc/meminfo | awk '{print $2}'") / 1024;
$usedMemory = $totalMemory - $freeMemory;

// Swap Memory Usage
$totalSwap = (float) shell_exec("grep SwapTotal /proc/meminfo | awk '{print $2}'") / 1024;
$freeSwap = (float) shell_exec("grep SwapFree /proc/meminfo | awk '{print $2}'") / 1024;
$usedSwap = $totalSwap - $freeSwap;

// Disk Usage
$diskUsageRaw = shell_exec("df -BG / | tail -1");
$diskParts = preg_split('/\s+/', trim($diskUsageRaw));

// Replace undefined with 'N/A' (or any other placeholder)
$diskTotal = isset($diskParts[1]) ? intval($diskParts[1]) : 'N/A';
$diskUsed = isset($diskParts[2]) ? intval($diskParts[2]) : 'N/A';
$diskFree = isset($diskParts[3]) ? intval($diskParts[3]) : 'N/A';

// Uptime
$uptime = htmlspecialchars(shell_exec("uptime -p"));

// Top Processes
$topProcesses = htmlspecialchars(shell_exec("ps aux --sort=-%cpu | head -n 5"));
// Prepare Data
$data = [
    "cpuLoad" => $cpuLoad, // This should be consistent with the pie chart
    "idleFree" => $idleFree, // This should match the pie chart
    "coreUsage" => $coreUsage,
    "ram" => [
        "used" => round($usedMemory, 2),
        "free" => round($freeMemory, 2),
        "total" => round($totalMemory, 2)
    ],
    "swap" => [
        "used" => round($usedSwap, 2),
        "free" => round($freeSwap, 2),
        "total" => round($totalSwap, 2)
    ],
    "disk" => [
        "used" => $diskUsed,
        "free" => $diskFree,
        "total" => $diskTotal
    ],
    "uptime" => trim($uptime),
    "processes" => $topProcesses
 

];


// Fetch details of Server 5
$fileUrl = 'http://';
$fileContents = file_get_contents($fileUrl); // Here we should get data from Server 5

if ($fileContents === false) {
    echo "Error: Unable to read the file from Server 5.";
} else {
    // Decode the JSON data from the fetched file
    $serverData = json_decode($fileContents, true);
    
    if ($serverData === null) {
        echo "Error: Unable to decode JSON data from Server 5.";
    } else {
        // Use server data safely
        $cpuLoad = isset($serverData['cpu']['load']) ? $serverData['cpu']['load'] : 'N/A';
        $idleFree = isset($serverData['cpu']['idleFree']) ? $serverData['cpu']['idleFree'] : 'N/A';
        $server5CoreUsage = isset($serverData['cpu']['coreUsage']) ? $serverData['cpu']['coreUsage'] : [];

        // Display Server 5 details
        // echo "<h2 style='color: blue;'>Server 5 Performance Overview</h2>";
        // echo "<p><strong>Current CPU Load:</strong> <span style='color: red;'>$cpuLoad</span></p>";
        // echo "<p><strong>Available Idle CPU:</strong> <span style='color: green;'>$idleFree</span></p>";
        
        // Display core usage
        // echo "<h3>Core Usage Statistics:</h3>";
        // echo "<ul>";
        // foreach ($server5CoreUsage as $i => $core) {
        //     $status = $core < 100 ? 'In Use' : 'Available';
        //     echo "<li>Core {$i}: " . number_format($core, 2) . "% - <strong>{$status}</strong></li>";
        // }
        // echo "</ul>";
    }
}
// Fetch details of Server 16
$fileUrl16 = '';
$fileContents16 = file_get_contents($fileUrl16); // Here we should get data from Server 16

if ($fileContents16 === false) {
    echo "Error: Unable to read the file from Server 16.";
} else {
    // Decode the JSON data from the fetched file
    $serverData16 = json_decode($fileContents16, true);
    
    if ($serverData16 === null) {
        echo "Error: Unable to decode JSON data from Server 16.";
    } else {
        // Use server data safely
        $cpuLoad16 = isset($serverData16['cpu']['load']) ? $serverData16['cpu']['load'] : 'N/A';
        $idleFree16 = isset($serverData16['cpu']['idleFree']) ? $serverData16['cpu']['idleFree'] : 'N/A';
        $server16CoreUsage = isset($serverData16['cpu']['coreUsage']) ? $serverData16['cpu']['coreUsage'] : [];

        // Display Server 16 details with a different format
        // echo "<h2 style='color: green;'>Server 16 Performance Overview</h2>"; // Different color for distinction
        // echo "<p><strong>Current CPU Load:</strong> <span style='color: orange;'>$cpuLoad16</span></p>"; // Different color for CPU load
        // echo "<p><strong>Available Idle CPU:</strong> <span style='color: blue;'>$idleFree16</span></p>"; // Different color for idle CPU
        
        // Display core usage with a different presentation
        // echo "<h3>Core Usage Statistics:</h3>";
        // echo "<ul style='list-style-type: square;'>"; // Different list style
        // foreach ($server16CoreUsage as $i => $core) {
        //     $status = $core < 100 ? 'In Use' : 'Available';
        //     echo "<li>Core {$i}: " . number_format($core, 2) . "% - <strong>{$status}</strong></li>";
        // }
    //     echo "</ul>";
    }
}
// Fetch details of Server 15
$fileUrl15 = 'https://';
$fileContents15 = file_get_contents($fileUrl15); // Here we should get data from Server 15

if ($fileContents15 === false) {
    echo "Error: Unable to read the file from Server 15.";
} else {
    // Decode the JSON data from the fetched file
    $serverData15 = json_decode($fileContents15, true);
    
    if ($serverData15 === null) {
        echo "Error: Unable to decode JSON data from Server 15.";
    } else {
        // Use server data safely
        $cpuLoad15 = isset($serverData15['cpu']['load']) ? $serverData15['cpu']['load'] : 'N/A';
        $idleFree15 = isset($serverData15['cpu']['idleFree']) ? $serverData15['cpu']['idleFree'] : 'N/A';
        $server15CoreUsage = isset($serverData15['cpu']['coreUsage']) ? $serverData15['cpu']['coreUsage'] : [];

        // Display the server details
        // echo "<h2 style='color: purple;'>Server 15 Performance Overview</h2>";
        // echo "<p><strong>Current CPU Load:</strong> <span style='color: red;'>$cpuLoad</span></p>";
        // echo "<p><strong>Available Idle CPU:</strong> <span style='color: green;'>$idleFree</span></p>";
        
        // Display core usage if available
        // if (!empty($server15CoreUsage)) {
        //     echo "<h3>Core Usage Statistics:</h3><ul>";
        //     foreach ($server15CoreUsage as $i => $core) {
        //         $status = $core < 100 ? 'In Use' : 'Available'; 
        //         echo "<li>Core {$i}: " . number_format($core, 2) . "% - <strong>{$status}</strong></li>";
        //     }
        //     echo "</ul>";
        // } else {
        //     echo "<p>No core usage data available.</p>";
        }
    }
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Performance Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">


    <?php include("head.php"); ?>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.5);
           
        }
        .chart-container {
    flex: 1 1 45%; /* Allow two charts per row */
    min-width: 300px; /* Minimum width for responsiveness */
    height: 400px; /* Fixed height for other charts */
    padding-left: 20px; /* Add padding to the left */
}
.chart-container#coreChartContainer {
    height: 600px; /* Increased height for Core Usage chart */
    padding-left: 20px; /* Increased height for Core Usage chart */
}
.charts-wrapper {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping if necessary */
    gap: 20px; /* Space between charts */
}
.text-highlight {
            color: #007bff;
            font-weight: bold;
        }
        .right-align {
    margin-left: auto; /* Pushes the card to the right */
    margin-right: auto; /* Centers the card */
    width: 90%; /* Adjust width as needed */
}
#server5PerformanceSections {
            display: none; /* Hide performance section initially */
        }
        #LocalServerPerformanceSections{
            display :none;
        }
        #server16PerformanceSections{
            display :none;
        }
        #server15PerformanceSections{
            display :none;
        }
        .btn-teal {
        background-color: teal;
        color: white;
    }
        </style>
</head>
<body>
<?php include("navigation.php"); ?>
    <div class="container mt-5">
        <h1 class="text-center text-primary">📊 Server Performance Dashboard</h1>
     
        <!-- Button to Open the Modal -->
<br><h1><button id="openModalBtn" class="btn btn-primary"title='Click on Show Instructions button'>Show Instructions</button></h1></br>

<!-- Instructions Modal -->
<br><div id="instructionsModal" class="modal"></br>
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>
        <h1>Instructions</h1>
        <h3><p>Welcome to the Server Performance Dashboard! Here are some instructions to help you navigate and utilize the features:</p></h3>
        
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 48%;">
                <h2>1. Overview</h2>
                <h4><p>The bottom section of the dashboard lists the top five processes consuming CPU resources. This helps you identify any resource-heavy applications running on your server.</p></h4>
                <h4><p>This dashboard provides real-time insights into your server's performance metrics, including CPU load, memory usage, disk space, and more. <strong>You can click the show button for each server to open the details and graphics of the server, and every time you open it and see all the details, you have to click hide button and so the graphics do not open directly when you want to open it in another browser.</strong></p></h4>

                <h2>2. Key Metrics</h2>
                <ul>
                    <h4><li><strong>CPU Load:</strong> Displays the current CPU load percentage, indicating how much of the CPU's capacity is being utilized. A consistently high CPU load (over 80%) may indicate that your server is overburdened or under-resourced.</li></h4>
                    <h4><li><strong>Memory Usage:</strong> Shows the total, used, and free memory in megabytes (MB). High memory usage (over 75%) can lead to performance degradation, so monitor this closely.</li></h4>
                    <h4><li><strong>Disk Usage:</strong> Provides information on total, used, and free disk space in gigabytes (GB). Ensure that disk usage remains below 90% to avoid performance issues.</li></h4>
                    <h4><li><strong>Core Usage:</strong> Displays the usage percentage for each CPU core, giving you a detailed view of CPU performance. Uneven load across cores can indicate issues with process management.</li></h4>
                </ul>

                <h2>3. Charts</h2>
                <h4><p>The dashboard includes visual representations of the metrics through charts. You can view:</p></h4>
                <ul>
                    <h4><li>A doughnut chart for CPU load and idle percentages.</li></h3>
                    <h4><li>A pie chart for RAM usage (used vs. free), which helps visualize how much memory is available for applications.</li></h4>
                    <h4><li>A pie chart for disk usage (used vs. free) to quickly assess storage availability.</li></h4>
                    <h4><li>A bar chart showing the usage of each CPU core, allowing you to identify any cores that may be over or under-utilized.</li></h4>
                </ul>

                <h2>4. Top Processes</h2>
                <h4><p>The bottom section of the dashboard lists the top five processes consuming CPU resources. You can click on each process to get more details, such as memory usage and execution time. This helps you identify any resource-heavy applications running on your server.</p></h4>
            </div>

            <div style="width: 48%;">
                <h2>5. Best Practices</h2>
                <h4><p>To maintain optimal server performance, consider the following best practices:</p></h4>
                <ul>
                    <h4><li>Regularly monitor CPU and memory usage to prevent bottlenecks.</li></h4>
                    <h4><li>Keep your server software and applications updated to improve performance and security.</li></h4>
                    <h4><li>Optimize and manage processes to distribute workload evenly across CPU cores.</li></h4>
                    <h4><li>Implement alerting mechanisms for when metrics exceed predefined thresholds.</li></h4>
                </ul>

                <h2>6. Troubleshooting Tips</h2>
                <h4><p>If you encounter performance issues, consider these troubleshooting steps:</p></h4>
                <ul>
                    <h4><li>Check for processes that may be consuming excessive resources and consider terminating or optimizing them.</li></h4>
                    <h4><li>Review system logs for any errors or warnings that could indicate underlying issues.</li></h4>
                    <h4><li>Ensure that there is sufficient disk space available; low disk space can severely impact performance.</li></h4>
                    <h4><li>Restarting the server can help clear temporary issues affecting performance.</li></h4>
                </ul>

                <h2>7. Closing the Modal</h2>
                <h4><p>You can close this instructions modal by clicking the "×" button in the top right corner.</p></h4>
                
                <h2>8. Additional Help</h2>
                <h4><p>If you have any questions or need further assistance, please refer to the documentation or contact your system administrator.</p></h4>
            </div>
        </div>
    </div>
</div>
<button id="showLocalServerBtn" title='Click on show Local server performance button'
        class="btn btn-success btn-lg mx-2" 
        onclick="toggleLocalServerPerformance(true)">
    <i class="bi bi-server"></i> Show Local Server Performance
</button>

<button id="hideLocalServerBtn" title='Click on Hide Local server performance button'
        class="btn btn-warning btn-lg mx-2" 
        onclick="toggleLocalServerPerformance(false)" 
        style="display:none">
    <i class="bi bi-eye-slash"></i> Hide Local Server Performance
</button>





<!-- Server 5 Buttons -->
<button id="showServerPerformanceBtn" title="Click on Show server 5 performance button"
        class="btn btn-info btn-lg mx-2" 
        onclick="toggleServer5Performance(true)">
    <i class="fas fa-chart-line"></i> Show Server 5 Performance
</button>

<button id="hideServerPerformanceBtn" title="Click on Hide server 5 performance button"
        class="btn btn-secondary btn-lg mx-2" 
        onclick="toggleServer5Performance(false)" 
        style="display:none">
    <i class="fas fa-eye-slash"></i> Hide Server 5 Performance
</button>

<!-- Server 15 Buttons -->
<button id="showServerPerformanceBtn15" title="Click on Show server 15 performance button"
        class="btn btn-warning btn-lg mx-2" 
        onclick="toggleServer15Performance(true)">
    <i class="fas fa-chart-line"></i> Show Server 15 Performance
</button>

<button id="hideServerPerformanceBtn15" title="Click on Hide server 15 performance button"
        class="btn btn-dark btn-lg mx-2" 
        onclick="toggleServer15Performance(false)" 
        style="display:none">
    <i class="fas fa-eye-slash"></i> Hide Server 15 Performance
</button>

<!-- Server 16 Buttons -->
<button id="showServerPerformanceBtn16" title="Click on Show server 16 performance button"
        class="btn btn-teal btn-lg mx-2" 
        onclick="toggleServer16Performance(true)">
    <i class="fas fa-chart-line"></i> Show Server 16 Performance
</button>

<button id="hideServerPerformanceBtn16" title="Click on Hide server 16 performance button"
        class="btn btn-info btn-lg mx-2" 
        onclick="toggleServer16Performance(false)" 
        style="display:none">
    <i class="fas fa-eye-slash"></i> Hide Server 16 Performance
</button>



    
<div id="server5PerformanceSections">
    
<div class="row">
    <!-- Server 5 Details -->
  
    <div class="col-md-4 col-12 offset-md-8"> <!-- Added offset-md-2 to push right -->
        <div class="card mb-4" style="width: 100%;">
        <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; display: flex; align-items: center;">
                🔍 Server 5 Details
            </div>
            
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr><td>CPU Load (Used)</td><td class="text-highlight"><?= number_format($serverData["cpu"]["load"], 2); ?>%</td></tr>
                        <tr><td>Idle Free</td><td class="text-highlight"><?= number_format($serverData["cpu"]["idleFree"], 2); ?>%</td></tr>
                        <tr><td>Total RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["total"]); ?> MB</td></tr>
                        <tr><td>Used RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["used"]); ?> MB</td></tr>
                        <tr><td>Free RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["free"]); ?> MB</td></tr>
                        <tr><td>Total Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["total"]); ?> MB</td></tr>
                        <tr><td>Used Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["used"]); ?> MB</td></tr>
                        <tr><td>Free Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["free"]); ?> MB</td></tr>
                        <tr><td>Total Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["total"], 2); ?> GB</td></tr>
                        <tr><td>Used Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["used"], 2); ?> GB</td></tr>
                        <tr><td>Free Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["free"], 2); ?> GB</td></tr>
                        <tr><td>Uptime</td><td class="text-highlight"><?= htmlspecialchars($serverData["uptime"]); ?></td></tr>
                        
                        <tr><td>Core Usage</td>
                            <td class="text-highlight">
                            <?php 
    $coreUsageText = ''; 
    // Assuming $serverData["cpu"]["coreUsage"] contains the core usage data
    foreach ($serverData["cpu"]["coreUsage"] as $i => $core) {
        // Determine the status based on the core usage percentage
        $status = $core < 100 ? 'Used' : 'Free'; 
        // Append the formatted string to the core usage text
        $coreUsageText .= "Core {$i}: " . number_format($core, 2) . "% - {$status}<br>";
    }

    echo $coreUsageText;
    ?>>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Charts Section -->
    <div class="col-md-4 col-12 offset-md-2">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">📊 Performance Charts</div>
                    <div class="charts-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                            <canvas id="server5CpuLoadChart"></canvas> <!-- Server 5 CPU Load chart -->
                        </div>
                        <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                            <canvas id="server5MemoryUsageChart"></canvas> <!-- Server 5 Memory Usage chart -->
                        </div>
                        <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                            <canvas id="diskChart5"></canvas> <!-- Disk chart -->
                        </div>
                    </div>
                </div>
            </div>

     <!-- Core Usage Chart -->
     <div class="col-md-4 col-24 "> <!-- Added offset-md-2 -->
        <div class="card mb-24">
        <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; width:700px; display: flex; align-items: center;">
                📊 Server 5 Core Usage Chart
            </div>
            <div class="chart-container" style="height: 600px; width: 700px">
                <canvas id="server5CoreChart"></canvas>
            </div>
        </div>
    </div>
</div>
        <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem;padding: 12px 20px;">⚙️ Top Processes for server 5</div>
                <td class="text-highlight">
                            <pre><?= htmlspecialchars($serverData["processes"]); ?></pre>
                        </td>
            </div>
        </div>
    </div>
</div>

        <div id="LocalServerPerformanceSections">
<div class="row">
    <!-- Server Details -->
    <div class="col-md-4 col-12 offset-md-8"> 
        <div class="card mb-4" style="width: 100%;">
        <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; display: flex; align-items: center;">
    🔍 Server Details
</div>

            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                    <tr><td>CPU Load (Used)</td><td class="text-highlight"><?= $data["cpuLoad"]; ?>%</td></tr>
                        <!-- <tr><td>Idle Used</td><td class="text-highlight"><?= $data["idleUsed"]; ?>%</td></tr> -->
                        <tr><td>Idle Free</td><td class="text-highlight"><?= $data["idleFree"]; ?>%</td></tr>
                        <tr><td>Total RAM</td><td class="text-highlight"><?= $data["ram"]["total"]; ?> MB</td></tr>
                        <tr><td>Used RAM</td><td class="text-highlight"><?= $data["ram"]["used"]; ?> MB</td></tr>
                        <tr><td>Free RAM</td><td class="text-highlight"><?= $data["ram"]["free"]; ?> MB</td></tr>
                        <tr><td>Total Swap</td><td class="text-highlight"><?= $data["swap"]["total"]; ?> MB</td></tr>
                        <tr><td>Used Swap</td><td class="text-highlight"><?= $data["swap"]["used"]; ?> MB</td></tr>
                        <tr><td>Free Swap</td><td class="text-highlight"><?= $data["swap"]["free"]; ?> MB</td></tr>
                        <tr><td>Total Disk</td><td class="text-highlight"><?= $data["disk"]["total"]; ?> GB</td></tr>
                        <tr><td>Used Disk</td><td class="text-highlight"><?= $data["disk"]["used"]; ?> GB</td></tr>
                        <tr><td>Free Disk</td><td class="text-highlight"><?= $data["disk"]["free"]; ?> GB</td></tr>
                        <tr><td>Uptime</td><td class="text-highlight"><?= $data["uptime"]; ?></td></tr>
                        <tr><td>Core Usage</td>
                            <td class="text-highlight">
                            <?php 
$coreUsageText = ''; 
for ($i = 0; $i < $numCores; $i++) {
    // Get the core usage, defaulting to 0 if not set
    $core = isset($data["coreUsage"][$i]) ? $data["coreUsage"][$i] : 0;

    // Update the status logic to reflect 0% as Free
    $status = $core < 100 ? 'Used' : 'Free'; 

    // Ensure that if the core usage is 0, it shows as 0%
    $coreUsageText .= "Core {$i}: " . number_format($core, 2) . "% - {$status}<br>";
}

// Output the core usage text
echo $coreUsageText;
?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pie Charts -->
    <div class="col-md-4 col-12 offset-md-2"> <!-- Added offset-md-2 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">📊 Pie Charts</div>
            <div class="charts-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="chart-container" style="flex: 1 1 100%; height: 300px;">
                    <canvas id="cpuChart"></canvas> <!-- CPU chart -->
                </div>
                <div class="chart-container" style="flex: 1 1 100%; height: 300px;">
                    <canvas id="ramChart"></canvas> <!-- RAM chart -->
                </div>
                <div class="chart-container" style="flex: 1 1 100%; height: 300px;">
                    <canvas id="diskChart"></canvas> <!-- Disk chart -->
                </div>
            </div>
        </div>
    </div>

    <!-- Core Usage Chart -->
    <div class="col-md-4 col-24 "> <!-- Added offset-md-2 -->
        <div class="card mb-24">
        <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; width:700px; display: flex; align-items: center;">
    📊 Core Usage Chart
</div>

            <div class="chart-container" style="height: 600px; width: 700px"> <!-- Increased height for Core Usage chart -->
                <canvas id="coreChart"></canvas>
            </div>
        </div>
    </div>
</div>





    <!-- Top Processes Section -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem;padding: 12px 20px;">⚙️ Top Processes</div>
                <div class="card-body">
                    <pre><?= $data["processes"]; ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="server16PerformanceSections">
    <div class="row">
        <!-- Server 16 Details -->
        <div class="col-md-4 col-12 offset-md-8">
            <div class="card mb-4" style="width: 100%;">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; display: flex; align-items: center;">
                    🔍 Server 16 Details
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr><td>CPU Load (Used)</td><td class="text-highlight"><?= number_format($serverData["cpu"]["load"], 2); ?>%</td></tr>
                            <tr><td>Idle Free</td><td class="text-highlight"><?= number_format($serverData["cpu"]["idleFree"], 2); ?>%</td></tr>
                            <tr><td>Total RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["total"]); ?> MB</td></tr>
                            <tr><td>Used RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["used"]); ?> MB</td></tr>
                            <tr><td>Free RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["free"]); ?> MB</td></tr>
                            <tr><td>Total Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["total"]); ?> MB</td></tr>
                            <tr><td>Used Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["used"]); ?> MB</td></tr>
                            <tr><td>Free Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["free"]); ?> MB</td></tr>
                            <tr><td>Total Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["total"], 2); ?> GB</td></tr>
                            <tr><td>Used Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["used"], 2); ?> GB</td></tr>
                            <tr><td>Free Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["free"], 2); ?> GB</td></tr>
                            <tr><td>Uptime</td><td class="text-highlight"><?= htmlspecialchars($serverData["uptime"]); ?></td></tr>
                            
                            <tr>
    <td>Core Usage</td>
    <td class="text-highlight">
        <ul>
        <?php 
        // Assuming $serverData16["cpu"]["coreUsage"] contains the core usage data for Server 16
        $coreUsageData = $serverData16["cpu"]["coreUsage"]; // Use the correct variable for Server 16
        $minUsedValue = 1.00; // Minimum value for used cores

        // Limit the loop to 24 cores for Server 16
        for ($i = 0; $i < 24; $i++) {
            if (isset($coreUsageData[$i])) { // Check if core data exists
                $core = $coreUsageData[$i];
                
                // Format the usage and status according to the rules
                if ($core === 100) {
                    $formattedUsage = "0.00%"; // 100% usage shown as 0%
                    $status = "Free"; // Label as Free
                } else {
                    // Set minimum for used cores
                    $coreDisplay = $core < $minUsedValue ? $minUsedValue : $core; 
                    $formattedUsage = number_format($coreDisplay, 2) . "%"; // Normal display
                    $status = "Used"; // Label as Used
                }
                echo "<li>Core {$i}: {$formattedUsage} - <strong>{$status}</strong></li>";
            } else {
                echo "<li>Core {$i}: N/A</li>"; // Handle cases where core data is not available
            }
        }
        ?>
        </ul>
    </td>
</tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Charts Section -->
        <div class="col-md-4 col-12 offset-md-2">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">📊 Performance Charts</div>
                <div class="charts-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="server16CpuLoadChart"></canvas> <!-- Server 16 CPU Load chart -->
                    </div>
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="server16MemoryUsageChart"></canvas> <!-- Server 16 Memory Usage chart -->
                    </div>
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="diskChart16"></canvas> <!-- Disk chart -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Core Usage Chart -->
        <div class="col-md-4 col-12">
            <div class="card mb-24">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; width:700px; display: flex; align-items: center;">
                    📊 Server 16 Core Usage Chart
                </div>
                <div class="chart-container" style="height: 600px; width: 700px">
                    <canvas id="server16CoreChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">⚙️ Top Processes for Server 16</div>
                <td class="text-highlight">
                    <pre><?= htmlspecialchars($serverData16["processes"]); ?></pre>
                </td>
            </div>
        </div>
    </div>
</div>


<div id="server15PerformanceSections">
    <div class="row">
        <!-- Server 15 Details -->
        <div class="col-md-4 col-12 offset-md-8">
            <div class="card mb-4" style="width: 100%;">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; display: flex; align-items: center;">
                    🔍 Server 15 Details
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr><td>CPU Load (Used)</td><td class="text-highlight"><?= number_format($serverData["cpu"]["load"], 2); ?>%</td></tr>
                            <tr><td>Idle Free</td><td class="text-highlight"><?= number_format($serverData["cpu"]["idleFree"], 2); ?>%</td></tr>
                            <tr><td>Total RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["total"]); ?> MB</td></tr>
                            <tr><td>Used RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["used"]); ?> MB</td></tr>
                            <tr><td>Free RAM</td><td class="text-highlight"><?= number_format($serverData["memory"]["free"]); ?> MB</td></tr>
                            <tr><td>Total Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["total"]); ?> MB</td></tr>
                            <tr><td>Used Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["used"]); ?> MB</td></tr>
                            <tr><td>Free Swap</td><td class="text-highlight"><?= number_format($serverData["swap"]["free"]); ?> MB</td></tr>
                            <tr><td>Total Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["total"], 2); ?> GB</td></tr>
                            <tr><td>Used Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["used"], 2); ?> GB</td></tr>
                            <tr><td>Free Disk</td><td class="text-highlight"><?= number_format($serverData["disk"]["free"], 2); ?> GB</td></tr>
                            <tr><td>Uptime</td><td class="text-highlight"><?= htmlspecialchars($serverData["uptime"]); ?></td></tr>
                            <tr><td>Core Usage</td>
    <td class="text-highlight">
        <ul>
        <?php 
        // Assuming $serverData["cpu"]["coreUsage"] contains the core usage data
        foreach ($server15CoreUsage as $i => $core) {
            // Determine formatted core usage output
            if ($core === 100) {
                $formattedUsage = "0.00%"; // 100% usage shown as 0%
                $status = "Free"; // Label as Free
            } else {
                $formattedUsage = number_format($core, 2) . "%"; // Normal display
                $status = "Used"; // Label as Used
            }
            echo "<li>Core {$i}: {$formattedUsage} - <strong>{$status}</strong></li>";
        }
        ?>
        </ul>
    </td>
</tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="col-md-4 col-12 offset-md-2">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">📊 Performance Charts</div>
                <div class="charts-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="server15CpuLoadChart"></canvas> <!-- Server 15 CPU Load chart -->
                    </div>
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="server15MemoryUsageChart"></canvas> <!-- Server 15 Memory Usage chart -->
                    </div>
                    <div class="chart-container" style="flex: 1 1 45%; min-width: 300px; height: 400px;">
                        <canvas id="diskChart15"></canvas> <!-- Disk chart -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Core Usage Chart for server 15-->
        <div class="col-md-4 col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px; display: flex; align-items: center;">
                    📊 Server 15 Core Usage Chart
                </div>
                <div class="chart-container" style="height: 600px; width: 700px">
                    <canvas id="server15CoreChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white" style="font-size: 2rem; padding: 12px 20px;">⚙️ Top Processes for Server 15</div>
                <td class="text-highlight">
                    <pre><?= htmlspecialchars($serverData15["processes"]); ?></pre>
                </td>
            </div>
        </div>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
// Function to toggle visibility for Server 5
function toggleServer5Performance(show) {
  sessionStorage.setItem('server5Visible', show ? 'true' : 'false');
  sessionStorage.setItem('LocalServerVisible', 'false');
  sessionStorage.setItem('server15Visible', 'false'); // Ensure Server 15 is hidden
  sessionStorage.setItem('server16Visible', 'false'); // Ensure Server 16 is hidden

  if (show) { // Reload only when showing the section
    location.reload();
  } else {
    updatePerformanceSectionVisibility();
  }
}

// Function to toggle visibility for Local Server
function toggleLocalServerPerformance(show) {
  sessionStorage.setItem('LocalServerVisible', show ? 'true' : 'false');
  sessionStorage.setItem('server5Visible', 'false');
  sessionStorage.setItem('server15Visible', 'false'); // Ensure Server 15 is hidden
  sessionStorage.setItem('server16Visible', 'false'); // Ensure Server 16 is hidden

  if (show) { // Reload only when showing the section
    location.reload();
  } else {
    updatePerformanceSectionVisibility();
  }
}

// Function to toggle visibility for Server 16
function toggleServer16Performance(show) {
  sessionStorage.setItem('server16Visible', show ? 'true' : 'false');
  sessionStorage.setItem('server5Visible', 'false');
  sessionStorage.setItem('LocalServerVisible', 'false'); // Ensure Local Server is hidden
  sessionStorage.setItem('server15Visible', 'false'); // Ensure Server 15 is hidden

  if (show) { // Reload only when showing the section
    location.reload();
  } else {
    updatePerformanceSectionVisibility();
  }
}

// Function to toggle visibility for Server 15
function toggleServer15Performance(show) {
  sessionStorage.setItem('server15Visible', show ? 'true' : 'false');
  sessionStorage.setItem('server5Visible', 'false');
  sessionStorage.setItem('LocalServerVisible', 'false'); // Ensure Local Server is hidden
  sessionStorage.setItem('server16Visible', 'false'); // Ensure Server 16 is hidden

  if (show) { // Reload only when showing the section
    location.reload();
  } else {
    updatePerformanceSectionVisibility();
  }
}

// Function to update visibility of performance sections based on session storage
function updatePerformanceSectionVisibility() {
  const server5PerformanceSections = document.getElementById('server5PerformanceSections');
  const LocalServerPerformanceSections = document.getElementById('LocalServerPerformanceSections');
  const server15PerformanceSections = document.getElementById('server15PerformanceSections');
  const server16PerformanceSections = document.getElementById('server16PerformanceSections');

  const showServer5Btn = document.getElementById('showServerPerformanceBtn');
  const hideServer5Btn = document.getElementById('hideServerPerformanceBtn');

  const showLocalServerBtn = document.getElementById('showLocalServerBtn');
  const hideLocalServerBtn = document.getElementById('hideLocalServerBtn');

  const showServer15Btn = document.getElementById('showServerPerformanceBtn15');
  const hideServer15Btn = document.getElementById('hideServerPerformanceBtn15');

  const showServer16Btn = document.getElementById('showServerPerformanceBtn16');
  const hideServer16Btn = document.getElementById('hideServerPerformanceBtn16');

  // Get the visibility state from session storage
  const server5Visible = sessionStorage.getItem('server5Visible') === 'true';
  const LocalServerVisible = sessionStorage.getItem('LocalServerVisible') === 'true';
  const server15Visible = sessionStorage.getItem('server15Visible') === 'true';
  const server16Visible = sessionStorage.getItem('server16Visible') === 'true';

  // Set up UI based on visibility states
  server5PerformanceSections.style.display = server5Visible ? "block" : "none";
  showServer5Btn.style.display = server5Visible ? "none" : "inline";
  hideServer5Btn.style.display = server5Visible ? "inline" : "none";

  LocalServerPerformanceSections.style.display = LocalServerVisible ? "block" : "none";
  showLocalServerBtn.style.display = LocalServerVisible ? "none" : "inline";
  hideLocalServerBtn.style.display = LocalServerVisible ? "inline" : "none";

  server15PerformanceSections.style.display = server15Visible ? "block" : "none";
  showServer15Btn.style.display = server15Visible ? "none" : "inline";
  hideServer15Btn.style.display = server15Visible ? "inline" : "none";

  server16PerformanceSections.style.display = server16Visible ? "block" : "none";
  showServer16Btn.style.display = server16Visible ? "none" : "inline";
  hideServer16Btn.style.display = server16Visible ? "inline" : "none";
}

// Initial setup to manage server performance sections visibility on page load
window.onload = function() {
  updatePerformanceSectionVisibility(); // Call the function to initialize visibility
};
// JavaScript to handle modal opening and closing
 document.getElementById('openModalBtn').onclick = function() {
        document.getElementById('instructionsModal').style.display = "block";
    }

    document.getElementById('closeModal').onclick = function() {
        document.getElementById('instructionsModal').style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == document.getElementById('instructionsModal')) {
            document.getElementById('instructionsModal').style.display = "none";
        }
    }




/// CPU Chart
new Chart(document.getElementById('cpuChart'), {
    type: 'doughnut',
    data: {
        labels: ['CPU Load', 'Idle Free'],
        datasets: [{
            data: [<?= $data["cpuLoad"]; ?>,  <?= $data["idleFree"]; ?>],
            backgroundColor: ['#7286D3', '#F39C12', '#909337']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${percentage}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                },
                anchor: 'start',
                align: 'start'
            }
        }
    },
    plugins: [ChartDataLabels]
});

// RAM Chart
new Chart(document.getElementById('ramChart'), {
    type: 'pie',
    data: {
        labels: ['Used RAM', 'Free RAM'],
        datasets: [{
            data: [<?= $data["ram"]["used"]; ?>, <?= $data["ram"]["free"]; ?>],
            backgroundColor: ['#3498DB', '#2ECC71']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    const percentage = ((value / total) * 100).toFixed(2);
                    return `${percentage}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Disk Chart
new Chart(document.getElementById('diskChart'), {
    type: 'pie',
    data: {
        labels: ['Used Disk', 'Free Disk'],
        datasets: [{
            data: [<?= $data["disk"]["used"]; ?>, <?= $data["disk"]["free"]; ?>],
            backgroundColor: ['#9B59B6', '#34495E']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${percentage}%`;
                },
                color: '#fff',
                font: {
                    size: 14,
                    weight: 'bold'
                },
                anchor: 'center',
                align: 'center'
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Core Usage Chart
new Chart(document.getElementById('coreChart'), {
    type: 'bar',
    data: {
        labels: Array.from({ length: <?= $numCores; ?> }, (_, i) => `Core ${i}`),
        datasets: [{
            label: 'Core Usage (%)',
            data: <?= json_encode(array_values($data["coreUsage"])); ?>,
            backgroundColor: '#FFCC33',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Core Usage (%)',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    stepSize: 10,
                    font: {
                        size: 20
                    }
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Cores',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    font: {
                        size: 20
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true
            },
            datalabels: {
                anchor: 'end',
                align: 'end',
                formatter: (value) => `${value}%`,
                font: {
                    size: 20
                }
            }
        },
        layout: {
            padding: {
                top: 20,
                left: 50, // Increased left padding to move the chart to the left
                right: 80,
                bottom: 20
            }
        },
        title: {
            display: true,
            text: 'Core Usage by Core Number',
            font: {
                size: 24
            },
            padding: {
                top: 10,
                bottom: 10
            }
        }
    },
    plugins: [ChartDataLabels]
});
// New Server 5 CPU Load Chart
new Chart(document.getElementById('server5CpuLoadChart'), {
    type: 'doughnut',
    data: {
        labels: ['CPU Load', 'Idle Free'],
        datasets: [{
            data: [<?= $serverData['cpu']['load']; ?>, <?= $serverData['cpu']['idleFree']; ?>],
            backgroundColor: ['#FF5733', '#81c0ff']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// New Server 5 Memory Usage Chart
new Chart(document.getElementById('server5MemoryUsageChart'), {
    type: 'pie',
    data: {
        labels: ['Used Ram', 'Free Ram'],
        datasets: [{
            data: [<?= $serverData['memory']['used']; ?>, <?= $serverData['memory']['free']; ?>],
            backgroundColor: ['#3498DB', '#2ECC71']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(2)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});
// Disk Chart
new Chart(document.getElementById('diskChart5'), {
    type: 'pie',
    data: {
        labels: ['Used Disk', 'Free Disk'],
        datasets: [{
            data: [<?= $data["disk"]["used"]; ?>, <?= $data["disk"]["free"]; ?>],
            backgroundColor: ['#9B59B6', '#34495E']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#fff',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});
// Server 5 Core Usage Chart
new Chart(document.getElementById('server5CoreChart'), {
    type: 'bar',
    data: {
        labels: Array.from({ length: 32 }, (_, i) => `Core ${i+1}`), // 32 cores for Server 5
        datasets: [{
            label: 'Server 5 Core Usage (%)',
            data: <?= json_encode(array_values($server5CoreUsage)); ?>, // Replace with actual core usage data for Server 5
            backgroundColor: '#FFCC33',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Core Usage (%)',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    stepSize: 10,
                    font: {
                        size: 20
                    }
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Cores',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    font: {
                        size: 20
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true
            },
            datalabels: {
                anchor: 'end',
                align: 'end',
                formatter: (value) => `${value}%`,
                font: {
                    size: 20
                }
            }
        },
        layout: {
            padding: {
                top: 20,
                left: 50, // Increased left padding to move the chart to the left
                right: 80,
                bottom: 20
            }
        },
        title: {
            display: true,
            text: 'Server 5 Core Usage by Core Number',
            font: {
                size: 24
            },
            padding: {
                top: 10,
                bottom: 10
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 16 CPU Load Chart
new Chart(document.getElementById('server16CpuLoadChart'), {
    type: 'doughnut',
    data: {
        labels: ['CPU Load', 'Idle Free'],
        datasets: [{
            data: [<?= $serverData['cpu']['load']; ?>, <?= $serverData['cpu']['idleFree']; ?>],
            backgroundColor: ['#FF5733', '#81c0ff']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 16 Memory Usage Chart
new Chart(document.getElementById('server16MemoryUsageChart'), {
    type: 'pie',
    data: {
        labels: ['Used Ram', 'Free Ram'],
        datasets: [{
            data: [<?= $serverData['memory']['used']; ?>, <?= $serverData['memory']['free']; ?>],
            backgroundColor: ['#3498DB', '#2ECC71']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(2)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 16 Disk Chart
new Chart(document.getElementById('diskChart16'), {
    type: 'pie',
    data: {
        labels: ['Used Disk', 'Free Disk'],
        datasets: [{
            data: [<?= $serverData["disk"]["used"]; ?>, <?= $serverData["disk"]["free"]; ?>],
            backgroundColor: ['#9B59B6', '#34495E']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#fff',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 16 Core Usage Chart
new Chart(document.getElementById('server16CoreChart'), {
    type: 'bar',
    data: {
        labels: Array.from({ length: 24 }, (_, i) => `Core ${i}`), // 24 cores for Server 16, starting from Core 0
        datasets: [{
            label: 'Server 16 Core Usage (%)',
            data: <?= json_encode(array_map(function($core) {
                // Apply minimum used value logic
                $minUsedValue = 1; // Minimum value for used cores
                return $core === 100 ? 0 : ($core < $minUsedValue ? $minUsedValue : $core);
            }, array_values($serverData16['cpu']['coreUsage']))); ?>,
            backgroundColor: '#FFCC33',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Core Usage (%)',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    stepSize: 10,
                    font: {
                        size: 20
                    }
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Cores',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    font: {
                        size: 20
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true
            },
            datalabels: {
                anchor: 'end',
                align: 'end',
                formatter: (value) => `${value}%`,
                font: {
                    size: 20
                }
            }
        },
        layout: {
            padding: {
                top: 20,
                left: 50, // Increased left padding to move the chart to the left
                right: 80,
                bottom: 20
            }
        },
        title: {
            display: true,
            text: 'Server 16 Core Usage by Core Number',
            font: {
                size: 24
            },
            padding: {
                top: 10,
                bottom: 10
            }
        }
    },
    plugins: [ChartDataLabels]
});
// Server 15 CPU Load Chart
new Chart(document.getElementById('server15CpuLoadChart'), {
    type: 'doughnut',
    data: {
        labels: ['CPU Load', 'Idle Free'],
        datasets: [{
            data: [<?= $serverData['cpu']['load']; ?>, <?= $serverData['cpu']['idleFree']; ?>],
            backgroundColor: ['#FF5733', '#81c0ff']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 15 Memory Usage Chart
new Chart(document.getElementById('server15MemoryUsageChart'), {
    type: 'pie',
    data: {
        labels: ['Used Ram', 'Free Ram'],
        datasets: [{
            data: [<?= $serverData['memory']['used']; ?>, <?= $serverData['memory']['free']; ?>],
            backgroundColor: ['#3498DB', '#2ECC71']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(2)}%`;
                },
                color: '#000000',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 15 Disk Chart
new Chart(document.getElementById('diskChart15'), {
    type: 'pie',
    data: {
        labels: ['Used Disk', 'Free Disk'],
        datasets: [{
            data: [<?= $serverData["disk"]["used"]; ?>, <?= $serverData["disk"]["free"]; ?>],
            backgroundColor: ['#9B59B6', '#34495E']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                    return `${((value / total) * 100).toFixed(1)}%`;
                },
                color: '#fff',
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Server 15 Core Usage Chart
new Chart(document.getElementById('server15CoreChart'), {
    type: 'bar',
    data: {
        labels: Array.from({ length: 12 }, (_, i) => `Core ${i}`), // Change here to start from Core 0
        datasets: [{
            label: 'Server 15 Core Usage (%)',
            data: <?= json_encode(array_map(function($core) {// Apply minimum used value logic
                $minUsedValue = 1; // Minimum value for used cores
                return $core === 100 ? 0 : ($core < $minUsedValue ? $minUsedValue : $core);
            }, array_values($serverData15['cpu']['coreUsage']))); ?>,
            backgroundColor: '#FFCC33',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Core Usage (%)',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    stepSize: 10,
                    font: {
                        size: 20
                    }
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Cores',
                    font: {
                        size: 20
                    }
                },
                ticks: {
                    font: {
                        size: 20
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true
            },
            datalabels: {
                anchor: 'end',
                align: 'end',
                formatter: (value) => `${value}%`,
                font: {
                    size: 20
                }
            }
        },
        layout: {
            padding: {
                top: 20,
                left: 50, // Increased left padding to move the chart to the left
                right: 80,
                bottom: 20
            }
        },
        title: {
            display: true,
            text: 'Server 15 Core Usage by Core Number',
            font: {
                size: 24
            },
            padding: {
                top: 10,
                bottom: 10
            }
        }
    },
    plugins: [ChartDataLabels]
});
    </script>
    <footer>
        <?php include("footer.php"); ?>
    </footer>
</body>
</html>
