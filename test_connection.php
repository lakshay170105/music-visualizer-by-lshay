<?php
/**
 * L-SHAY Music Visualizer - Connection Test
 * Test database connection and API functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Database Configuration (same as api.php)
define('DB_HOST', 'sql210.infinityfree.com');
define('DB_USER', 'if0_40599502');
define('DB_PASS', 'x21Y4NuBfQgum6');
define('DB_NAME', 'if0_40599502_lshay_visualizer');

$test_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ],
    'tests' => []
];

// Test 1: Basic PHP functionality
$test_results['tests']['php_basic'] = [
    'status' => 'success',
    'message' => 'PHP is working correctly'
];

// Test 2: Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        $test_results['tests']['database_connection'] = [
            'status' => 'error',
            'message' => 'Connection failed: ' . $conn->connect_error,
            'host' => DB_HOST,
            'user' => DB_USER,
            'database' => DB_NAME
        ];
    } else {
        $test_results['tests']['database_connection'] = [
            'status' => 'success',
            'message' => 'Database connected successfully',
            'host' => DB_HOST,
            'user' => DB_USER,
            'database' => DB_NAME,
            'server_info' => $conn->server_info
        ];
        
        // Test 3: Check if tables exist
        $tables = ['users', 'user_sessions', 'user_stats', 'visualizers', 'templates'];
        $table_status = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
                $table_status[$table] = [
                    'exists' => true,
                    'row_count' => $count
                ];
            } else {
                $table_status[$table] = [
                    'exists' => false,
                    'row_count' => 0
                ];
            }
        }
        
        $test_results['tests']['database_tables'] = [
            'status' => 'success',
            'tables' => $table_status
        ];
        
        // Test 4: Try a simple query
        $result = $conn->query("SELECT COUNT(*) as total_users FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            $test_results['tests']['database_query'] = [
                'status' => 'success',
                'message' => 'Database queries working',
                'total_users' => $row['total_users']
            ];
        } else {
            $test_results['tests']['database_query'] = [
                'status' => 'error',
                'message' => 'Query failed: ' . $conn->error
            ];
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    $test_results['tests']['database_connection'] = [
        'status' => 'error',
        'message' => 'Database exception: ' . $e->getMessage()
    ];
}

// Test 5: Check if api.php exists and is accessible
$api_file = 'api.php';
if (file_exists($api_file)) {
    $test_results['tests']['api_file'] = [
        'status' => 'success',
        'message' => 'api.php file exists',
        'size' => filesize($api_file) . ' bytes'
    ];
} else {
    $test_results['tests']['api_file'] = [
        'status' => 'error',
        'message' => 'api.php file not found'
    ];
}

// Test 6: Check POST data handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_results['tests']['post_data'] = [
        'status' => 'success',
        'message' => 'POST request received',
        'post_data' => $_POST,
        'raw_input' => file_get_contents('php://input')
    ];
} else {
    $test_results['tests']['post_data'] = [
        'status' => 'info',
        'message' => 'This is a GET request. Send POST to test POST handling.'
    ];
}

// Output results
echo json_encode($test_results, JSON_PRETTY_PRINT);
?>