<?php
/**
 * L-SHAY Music Visualizer - Complete Backend System
 * Single PHP file with all functionality
 * User Authentication, Data Storage, Visualizer Management
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Session Configuration (for multi-device support)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Headers (CORS for cross-origin requests)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Configuration - Updated for InfinityFree
define('DB_HOST', 'sql210.infinityfree.com');
define('DB_USER', 'if0_40599502');
define('DB_PASS', 'x21Y4NuBfQgum6');
define('DB_NAME', 'if0_40599502_lshay_visualizer');

// Debug mode for troubleshooting
define('DEBUG_MODE', true);

// Database Connection
class Database {
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            // Direct connection with error output
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                // Output error directly for debugging
                die(json_encode([
                    'success' => false,
                    'error' => 'Database Connection Failed',
                    'message' => $this->conn->connect_error,
                    'host' => DB_HOST,
                    'user' => DB_USER,
                    'database' => DB_NAME
                ]));
            }
            
            $this->conn->set_charset("utf8mb4");
            $this->createTables();
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'error' => 'Database Exception',
                'message' => $e->getMessage()
            ]));
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function createTables() {
        // Users Table
        $usersTable = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            session_token VARCHAR(255) NULL,
            token_expires INT NULL,
            profile_data JSON NULL,
            is_active BOOLEAN DEFAULT TRUE,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_session_token (session_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // Visualizers Table - NO FOREIGN KEYS for InfinityFree
        $visualizersTable = "CREATE TABLE IF NOT EXISTS visualizers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            settings JSON NOT NULL,
            thumbnail TEXT NULL,
            is_public BOOLEAN DEFAULT FALSE,
            views INT DEFAULT 0,
            likes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_name (name),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // User Stats Table - NO FOREIGN KEYS for InfinityFree
        $statsTable = "CREATE TABLE IF NOT EXISTS user_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            visualizers_created INT DEFAULT 0,
            videos_exported INT DEFAULT 0,
            total_time_minutes INT DEFAULT 0,
            last_export TIMESTAMP NULL,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // Templates Table
        $templatesTable = "CREATE TABLE IF NOT EXISTS templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            genre VARCHAR(50) NOT NULL,
            style VARCHAR(50) NOT NULL,
            visualizer VARCHAR(50) NOT NULL,
            color VARCHAR(7) NOT NULL,
            secondary_color VARCHAR(7) NOT NULL,
            bg_color VARCHAR(7) NOT NULL,
            text_animation VARCHAR(50) NULL,
            particles BOOLEAN DEFAULT FALSE,
            overlay BOOLEAN DEFAULT FALSE,
            preview_data TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // User Activity Log - NO FOREIGN KEYS for InfinityFree
        $activityTable = "CREATE TABLE IF NOT EXISTS user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            activity_data JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // User Sessions Table (Multi-Device Support) - NO FOREIGN KEYS for InfinityFree
        $sessionsTable = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_token (session_token),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // Feedback Table - NO FOREIGN KEYS for InfinityFree
        $feedbackTable = "CREATE TABLE IF NOT EXISTS feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            type ENUM('suggestion', 'bug', 'feature', 'praise', 'other') NOT NULL,
            name VARCHAR(100) NULL,
            email VARCHAR(100) NULL,
            message TEXT NOT NULL,
            status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
            admin_reply TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($usersTable);
        $this->conn->query($visualizersTable);
        $this->conn->query($statsTable);
        $this->conn->query($templatesTable);
        $this->conn->query($activityTable);
        $this->conn->query($sessionsTable);
        $this->conn->query($feedbackTable);
        
        $this->initializeTemplates();
    }

    
    private function initializeTemplates() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM templates");
        $count = $result->fetch_assoc()['count'];
        
        if ($count == 0) {
            $genres = ['hiphop', 'lofi', 'edm', 'trap', 'pop', 'rock', 'indie', 'rnb', 'jazz', 'classical'];
            $styles = ['dark', 'neon', 'minimal', 'vintage', 'cyberpunk', 'vaporwave', 'grunge', 'luxury', 'retro', 'aesthetic'];
            $visualizers = ['linearWaveform', 'curvedWaveform', 'zigzagWaveform', 'verticalBars', 'circularBars', 
                           'solidCirclePulse', 'audioReactiveParticles', '3dBars', 'liquidEqualizer', 'kaleidoscope'];
            $colors = ['#1db954', '#00d9ff', '#ff006e', '#ff0000', '#00ff00', '#0000ff', '#ff00ff', '#ffff00', '#ff6b9d', '#01cdfe'];
            
            for ($i = 0; $i < 50; $i++) {
                $genre = $genres[array_rand($genres)];
                $style = $styles[array_rand($styles)];
                $visualizer = $visualizers[array_rand($visualizers)];
                $color = $colors[array_rand($colors)];
                $secondaryColor = $colors[array_rand($colors)];
                $name = strtoupper($genre) . ' ' . ucfirst($style) . ' #' . ($i + 1);
                $textAnimation = ['typewriter', 'fadeLetter', 'glitchText', 'neonGlowText'][array_rand(['typewriter', 'fadeLetter', 'glitchText', 'neonGlowText'])];
                $bgColor = $style === 'dark' ? '#000000' : ($style === 'neon' ? '#0a0e27' : '#1a1a1a');
                $particles = (bool)rand(0, 1);
                $overlay = (bool)rand(0, 1);
                
                $stmt = $this->conn->prepare("INSERT INTO templates (name, genre, style, visualizer, color, secondary_color, bg_color, text_animation, particles, overlay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssii", $name, $genre, $style, $visualizer, $color, $secondaryColor, $bgColor, $textAnimation, $particles, $overlay);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    public function error($message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    
    public function success($data = null, $message = "Success") {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// Helper Functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateSessionToken($userId, $db) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (86400 * 30); // 30 days
    $expires_str = date('Y-m-d H:i:s', $expires); // String format for TIMESTAMP
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Clean expired sessions first
    $cleanStmt = $db->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
    if ($cleanStmt) {
        $cleanStmt->execute();
        $cleanStmt->close();
    }
    
    // Insert new session (multi-device support - allows multiple active sessions)
    $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Session prepare failed: " . $db->error);
        die(json_encode([
            'success' => false,
            'error' => 'SESSION_PREPARE_FAILED',
            'message' => 'Failed to prepare session insert: ' . $db->error
        ]));
    }
    
    $stmt->bind_param("issss", $userId, $token, $ipAddress, $userAgent, $expires_str);
    
    if (!$stmt->execute()) {
        error_log("Session creation failed: " . $stmt->error . " -- DB error: " . $db->error);
        die(json_encode([
            'success' => false,
            'error' => 'SESSION_INSERT_FAILED',
            'message' => 'Failed to create session: ' . $stmt->error,
            'db_error' => $db->error,
            'user_id' => $userId
        ]));
    }
    
    // Check if insert actually worked
    if ($db->affected_rows === 0) {
        error_log("Session insert did not affect rows. Last error: " . $db->error);
    }
    
    $stmt->close();
    
    // Update users table for backward compatibility
    $stmt = $db->prepare("UPDATE users SET session_token = ?, token_expires = ?, last_login = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $token, $expires, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("User update prepare failed: " . $db->error);
    }
    
    // Log session creation
    error_log("New session created for user $userId from IP: $ipAddress -- token: " . substr($token, 0, 12) . "...");
    
    return $token;
}

function validateSessionToken($token, $db) {
    // Check in user_sessions table for multi-device support
    $stmt = $db->prepare("SELECT us.user_id as id, u.username FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.session_token = ? AND us.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update last activity
        $updateStmt = $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
        $updateStmt->bind_param("s", $token);
        $updateStmt->execute();
        $updateStmt->close();
        
        $stmt->close();
        return $user;
    }
    
    $stmt->close();
    return false;
}

function logActivity($userId, $activityType, $activityData, $db) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO user_activity (user_id, activity_type, activity_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $jsonActivity = json_encode($activityData);
    $stmt->bind_param("issss", $userId, $activityType, $jsonActivity, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
}


// Initialize Database
$database = new Database();
$conn = $database->getConnection();

// Get Request Data
$requestMethod = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get Authorization Token (InfinityFree Safe - GET/POST Priority)
$token = $_GET['token'] ?? $_POST['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

// Validate Token for Protected Routes
$currentUser = null;
if (!empty($token) && $action !== 'register' && $action !== 'login' && $action !== 'init') {
    $currentUser = validateSessionToken($token, $conn);
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
}

// API Routes

// Simple Status Check (No authentication required)
if ($action === 'status' || $action === '' || !$action) {
    echo json_encode([
        'success' => true,
        'message' => 'L-SHAY API is running',
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_host' => DB_HOST,
        'database_name' => DB_NAME,
        'available_actions' => [
            'status', 'test', 'debug', 'register', 'login', 'profile',
            'save_visualizer', 'get_visualizers', 'get_templates'
        ]
    ]);
    exit;
}

// Credentials Check Endpoint
if ($action === 'check_credentials') {
    echo json_encode([
        'current_credentials' => [
            'DB_HOST' => DB_HOST,
            'DB_USER' => DB_USER,
            'DB_NAME' => DB_NAME,
            'DB_PASS' => substr(DB_PASS, 0, 3) . '***' // Partial for security
        ],
        'connection_status' => $conn ? 'Connected' : 'Failed',
        'mysql_error' => $conn ? null : mysqli_connect_error(),
        'instructions' => [
            '1. Go to InfinityFree Control Panel',
            '2. Click MySQL Databases',
            '3. Copy EXACT values:',
            '   - MySQL Hostname',
            '   - MySQL Username',
            '   - MySQL Database Name',
            '   - MySQL Password',
            '4. Update api.php with exact values'
        ]
    ]);
    exit;
}

// Quick Test Endpoint
if ($action === 'test') {
    // Test database connection
    $dbTest = 'Unknown';
    $selectTest = 'Not tested';
    $insertTest = 'Not tested';
    $userCount = 0;
    
    try {
        // Test SELECT
        $result = $conn->query("SELECT 1");
        if ($result) {
            $dbTest = 'Connected ✅';
            $selectTest = 'Working ✅';
        }
        
        // Test user count
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            $userCount = $row['count'];
        }
        
        // Test INSERT (with unique username)
        $testUser = 'test_' . time();
        $testEmail = 'developbylshay@gmail.com';
        $testPass = password_hash('test123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        if ($stmt) {
            $stmt->bind_param("sss", $testUser, $testEmail, $testPass);
            if ($stmt->execute()) {
                $insertTest = 'Working ✅ (User ID: ' . $conn->insert_id . ')';
                $stmt->close();
                
                // Delete test user
                $conn->query("DELETE FROM users WHERE username = '$testUser'");
            } else {
                $insertTest = 'FAILED: ' . $stmt->error;
            }
        } else {
            $insertTest = 'PREPARE FAILED: ' . $conn->error;
        }
        
    } catch (Exception $e) {
        $dbTest = 'Failed: ' . $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'tests' => [
            'connection' => $dbTest,
            'select_query' => $selectTest,
            'insert_query' => $insertTest,
            'current_users' => $userCount
        ],
        'credentials' => [
            'host' => DB_HOST,
            'user' => DB_USER,
            'database' => DB_NAME
        ]
    ]);
    exit;
}

// Test Registration Endpoint (Direct Insert)
if ($action === 'test_register') {
    $testUsername = 'testuser_' . time();
    $testEmail = 'developbylshay@gmail.com';
    $testPassword = password_hash('test123', PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("sss", $testUsername, $testEmail, $testPassword);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // Create user stats
            $stmt2 = $conn->prepare("INSERT INTO user_stats (user_id, visualizers_created, videos_exported, total_time_minutes) VALUES (?, 0, 0, 0)");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Test registration successful!',
                'user_id' => $userId,
                'username' => $testUsername,
                'email' => $testEmail
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Insert failed: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Debug Endpoint
if ($action === 'debug' || isset($_GET['debug'])) {
    $debug = [
        'status' => 'API is running',
        'php_version' => phpversion(),
        'server_time' => date('Y-m-d H:i:s'),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'action' => $action,
        'post_data' => $_POST,
        'get_data' => $_GET,
        'database_connected' => true,
        'tables_exist' => []
    ];
    
    // Check tables
    $tables = ['users', 'user_sessions', 'user_stats', 'visualizers', 'templates', 'user_activity', 'feedback'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $debug['tables_exist'][$table] = $result->num_rows > 0;
    }
    
    // Count users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $debug['total_users'] = $result->fetch_assoc()['count'];
    
    $database->success($debug, 'Debug info');
}

// 1. Initialize Database
if ($action === 'init') {
    $database->success(null, 'Database initialized successfully');
}

// Test Connection & Debug Info
if ($action === 'test_connection') {
    $debug_info = [
        'database_connected' => true,
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'post_data' => $_POST,
        'get_data' => $_GET
    ];
    
    // Count users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $debug_info['total_users'] = $result->fetch_assoc()['count'];
    
    // Count active sessions
    $result = $conn->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()");
    $debug_info['active_sessions'] = $result->fetch_assoc()['count'];
    
    // Recent users
    $result = $conn->query("SELECT id, username, email, created_at, last_login FROM users ORDER BY id DESC LIMIT 5");
    $recent_users = [];
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
    $debug_info['recent_users'] = $recent_users;
    
    $database->success($debug_info, 'Connection test successful');
}

// 2. User Registration
if ($action === 'register') {
    // Debug: Log raw POST data
    error_log("=== REGISTRATION START ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("Request method: " . $requestMethod);
    error_log("GET data: " . json_encode($_GET));
    
    // Check if POST data exists
    if (empty($_POST)) {
        echo json_encode([
            'success' => false,
            'error' => 'NO POST DATA',
            'message' => 'POST data is empty. Check form submission.',
            'get_data' => $_GET,
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]);
        exit;
    }
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Normalize username to lowercase for consistency
    $username = strtolower($username);
    
    // Debug logging
    error_log("Registration attempt: username=$username, email=$email");
    
    // Output received data for debugging
    if (empty($username) || empty($email) || empty($password)) {
        error_log("Registration failed: Empty fields");
        echo json_encode([
            'success' => false,
            'error' => 'EMPTY FIELDS',
            'message' => 'All fields are required',
            'received' => [
                'username' => $username,
                'email' => $email,
                'password_length' => strlen($password)
            ],
            'post_keys' => array_keys($_POST)
        ]);
        exit;
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $database->error("Username must be between 3 and 50 characters");
    }
    
    if (!validateEmail($email)) {
        $database->error("Invalid email format");
    }
    
    if (strlen($password) < 6) {
        $database->error("Password must be at least 6 characters");
    }
    
    // Check if username or email already exists - provide specific feedback
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        // Determine which field conflicts
        if (strtolower($existing['username']) === strtolower($username)) {
            error_log("Registration failed: Username '$username' already exists");
            $database->error("Username '$username' is already taken. Please choose a different username or login if this is your account.");
        } else if (strtolower($existing['email']) === strtolower($email)) {
            error_log("Registration failed: Email '$email' already exists");
            $database->error("Email '$email' is already registered. Please use a different email or login if this is your account.");
        } else {
            error_log("Registration failed: Username or email already exists");
            $database->error("Username or email already exists. Please try different credentials or login if you have an account.");
        }
    }
    $stmt->close();
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'PREPARE FAILED',
            'message' => 'SQL prepare failed: ' . $conn->error,
            'query' => 'INSERT INTO users...'
        ]);
        exit;
    }
    
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        $stmt->close();
        
        error_log("User created successfully: ID=$userId, username=$username");
        
        // Create user stats entry
        $stmt = $conn->prepare("INSERT INTO user_stats (user_id, visualizers_created, videos_exported, total_time_minutes) VALUES (?, 0, 0, 0)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Generate session token
        $token = generateSessionToken($userId, $conn);
        
        // Log registration activity
        logActivity($userId, 'registration', [
            'username' => $username,
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $conn);
        
        error_log("Registration completed for user: $username (ID: $userId)");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'token' => $token
            ]
        ]);
        exit;
    } else {
        error_log("Registration failed: Database error - " . $stmt->error);
        echo json_encode([
            'success' => false,
            'error' => 'INSERT FAILED',
            'message' => 'Failed to insert user: ' . $stmt->error,
            'mysql_error' => $conn->error,
            'username' => $username,
            'email' => $email
        ]);
        $stmt->close();
        exit;
    }
}

// 3. User Login
if ($action === 'login') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug logging
    error_log("Login attempt for username: $username");
    
    if (empty($username) || empty($password)) {
        $database->error("Username and password are required");
    }
    
    // Case-insensitive username search
    $stmt = $conn->prepare("SELECT id, username, email, password, is_active FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Login failed: User not found - $username");
        $stmt->close();
        $database->error("Invalid username or password");
    }
    
    $user = $result->fetch_assoc();
    
    // Check if account is active
    if (!$user['is_active']) {
        error_log("Login failed: Account inactive - $username");
        $stmt->close();
        $database->error("Account is inactive");
    }
    
    if (!verifyPassword($password, $user['password'])) {
        error_log("Login failed: Wrong password - $username");
        $stmt->close();
        $database->error("Invalid username or password");
    }
    
    // Generate new session token (multi-device support)
    $token = generateSessionToken($user['id'], $conn);
    
    // Log activity
    logActivity($user['id'], 'login', [
        'username' => $username,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ], $conn);
    
    error_log("Login successful for user: $username (ID: {$user['id']})");
    
    unset($user['password']);
    
    $database->success([
        'user' => $user,
        'token' => $token
    ], "Login successful");
}


// 4. User Logout
if ($action === 'logout') {
    $stmt = $conn->prepare("UPDATE users SET session_token = NULL, token_expires = NULL WHERE session_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    logActivity($currentUser['id'], 'logout', [], $conn);
    $database->success(null, "Logout successful");
}

// 5. Get User Profile
if ($action === 'profile') {
    $stmt = $conn->prepare("SELECT id, username, email, created_at, last_login, profile_data FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT visualizers_created, videos_exported, total_time_minutes, last_export FROM user_stats WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM visualizers WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $visualizerCount = $result->fetch_assoc();
    
    $database->success([
        'user' => $userData,
        'stats' => $stats,
        'saved_visualizers' => $visualizerCount['count']
    ]);
}

// 6. Save Visualizer
if ($action === 'save_visualizer') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $settings = $_POST['settings'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    
    if (empty($name) || empty($settings)) {
        $database->error("Name and settings are required");
    }
    
    $settingsData = json_decode($settings);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $database->error("Invalid settings format");
    }
    
    $stmt = $conn->prepare("INSERT INTO visualizers (user_id, name, settings, thumbnail) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $currentUser['id'], $name, $settings, $thumbnail);
    
    if ($stmt->execute()) {
        $visualizerId = $conn->insert_id;
        
        $stmt = $conn->prepare("UPDATE user_stats SET visualizers_created = visualizers_created + 1 WHERE user_id = ?");
        $stmt->bind_param("i", $currentUser['id']);
        $stmt->execute();
        
        logActivity($currentUser['id'], 'save_visualizer', ['name' => $name, 'id' => $visualizerId], $conn);
        
        $database->success([
            'id' => $visualizerId,
            'name' => $name,
            'message' => "Visualizer saved successfully"
        ]);
    } else {
        $database->error("Failed to save visualizer");
    }
}

// 7. Get User's Visualizers
if ($action === 'get_visualizers') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT id, name, settings, thumbnail, created_at, updated_at FROM visualizers WHERE user_id = ?";
    $params = [$currentUser['id']];
    $types = "i";
    
    if (!empty($search)) {
        $query .= " AND name LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $types .= "s";
    }
    
    $query .= " ORDER BY updated_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $visualizers = [];
    while ($row = $result->fetch_assoc()) {
        $visualizers[] = $row;
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM visualizers WHERE user_id = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("i", $currentUser['id']);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    $database->success([
        'visualizers' => $visualizers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

// 8. Get Templates
if ($action === 'get_templates') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $genre = isset($_GET['genre']) ? sanitizeInput($_GET['genre']) : '';
    $style = isset($_GET['style']) ? sanitizeInput($_GET['style']) : '';
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT * FROM templates WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($genre) && $genre !== 'all') {
        $query .= " AND genre = ?";
        $params[] = $genre;
        $types .= 's';
    }
    
    if (!empty($style) && $style !== 'all') {
        $query .= " AND style = ?";
        $params[] = $style;
        $types .= 's';
    }
    
    $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    $database->success(['templates' => $templates]);
}

// 9. Get User Sessions (Multi-device)
if ($action === 'get_sessions') {
    $stmt = $conn->prepare("SELECT id, ip_address, user_agent, created_at, last_activity, expires_at FROM user_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY last_activity DESC");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    $database->success(['sessions' => $sessions]);
}

// 10. Revoke Session (Logout from specific device)
if ($action === 'revoke_session') {
    $sessionId = $_POST['session_id'] ?? 0;
    
    if (empty($sessionId)) {
        $database->error("Session ID is required");
    }
    
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $sessionId, $currentUser['id']);
    
    if ($stmt->execute()) {
        logActivity($currentUser['id'], 'revoke_session', ['session_id' => $sessionId], $conn);
        $database->success(null, "Session revoked successfully");
    } else {
        $database->error("Failed to revoke session");
    }
}

// 11. Sync State (Get latest user state)
if ($action === 'sync_state') {
    // Get user profile
    $stmt = $conn->prepare("SELECT id, username, email, created_at, last_login, profile_data FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    // Get stats
    $stmt = $conn->prepare("SELECT visualizers_created, videos_exported, total_time_minutes, last_export FROM user_stats WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Get recent visualizers
    $stmt = $conn->prepare("SELECT id, name, settings, thumbnail, created_at, updated_at FROM visualizers WHERE user_id = ? ORDER BY updated_at DESC LIMIT 10");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $visualizers = [];
    while ($row = $result->fetch_assoc()) {
        $visualizers[] = $row;
    }
    
    $database->success([
        'user' => $userData,
        'stats' => $stats,
        'recent_visualizers' => $visualizers,
        'sync_time' => time()
    ]);
}

// Submit Feedback
if ($action === 'submit_feedback') {
    $type = sanitizeInput($_POST['type'] ?? 'other');
    $name = sanitizeInput($_POST['name'] ?? 'Anonymous');
    $email = sanitizeInput($_POST['email'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $token = $_POST['token'] ?? '';
    
    if (empty($message)) {
        $database->error("Message is required");
    }
    
    // Get user_id if logged in
    $userId = null;
    if (!empty($token)) {
        $user = validateSessionToken($token, $conn);
        if ($user) {
            $userId = $user['id'];
        }
    }
    
    // Insert feedback
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, type, name, email, message, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt->bind_param("issssss", $userId, $type, $name, $email, $message, $ipAddress, $userAgent);
    
    if ($stmt->execute()) {
        $feedbackId = $conn->insert_id;
        $stmt->close();
        
        error_log("Feedback submitted: ID=$feedbackId, type=$type, name=$name");
        
        $database->success([
            'feedback_id' => $feedbackId,
            'type' => $type,
            'name' => $name
        ], "Feedback submitted successfully");
    } else {
        error_log("Feedback submission failed: " . $stmt->error);
        $stmt->close();
        $database->error("Failed to submit feedback");
    }
}

// =====================================================
// ADMIN ENDPOINTS
// =====================================================

// Get Admin Stats
if ($action === 'get_admin_stats') {
    $stats = [];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // New users today
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $result->fetch_assoc()['count'];
    
    // Total visualizers
    $result = $conn->query("SELECT COUNT(*) as count FROM visualizers");
    $stats['total_visualizers'] = $result->fetch_assoc()['count'];
    
    // New visualizers today
    $result = $conn->query("SELECT COUNT(*) as count FROM visualizers WHERE DATE(created_at) = CURDATE()");
    $stats['new_visualizers_today'] = $result->fetch_assoc()['count'];
    
    // Total exports
    $result = $conn->query("SELECT SUM(videos_exported) as total FROM user_stats");
    $stats['total_exports'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Exports today (from activity log)
    $result = $conn->query("SELECT COUNT(*) as count FROM user_activity WHERE activity_type = 'video_export' AND DATE(created_at) = CURDATE()");
    $stats['exports_today'] = $result->fetch_assoc()['count'];
    
    // Active users (last 7 days)
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['active_users_7days'] = $result->fetch_assoc()['count'];
    
    $database->success($stats);
}

// Get Recent Activities
if ($action === 'get_recent_activities') {
    $limit = $_GET['limit'] ?? 20;
    
    $query = "SELECT ua.*, u.username, u.email 
              FROM user_activity ua 
              JOIN users u ON ua.user_id = u.id 
              ORDER BY ua.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    $database->success($activities);
}

// Get All Users (Admin)
if ($action === 'get_all_users') {
    $query = "SELECT u.*, us.visualizers_created, us.videos_exported, us.last_activity 
              FROM users u 
              LEFT JOIN user_stats us ON u.id = us.user_id 
              ORDER BY u.created_at DESC";
    
    $result = $conn->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Remove sensitive data
        unset($row['password']);
        unset($row['session_token']);
        $users[] = $row;
    }
    
    $database->success($users);
}

// Get All Visualizers (Admin)
if ($action === 'get_all_visualizers') {
    $query = "SELECT v.*, u.username 
              FROM visualizers v 
              LEFT JOIN users u ON v.user_id = u.id 
              ORDER BY v.created_at DESC";
    
    $result = $conn->query($query);
    
    $visualizers = [];
    while ($row = $result->fetch_assoc()) {
        $visualizers[] = $row;
    }
    
    $database->success($visualizers);
}

// Get User Details (Admin)
if ($action === 'get_user_details') {
    $userId = $_GET['id'] ?? 0;
    
    if (empty($userId)) {
        $database->error("User ID is required");
    }
    
    $stmt = $conn->prepare("SELECT u.*, us.* 
                            FROM users u 
                            LEFT JOIN user_stats us ON u.id = us.user_id 
                            WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $database->error("User not found");
    }
    
    $user = $result->fetch_assoc();
    unset($user['password']);
    unset($user['session_token']);
    
    $database->success($user);
}

// Get Visualizer Details (Admin)
if ($action === 'get_visualizer_details') {
    $vizId = $_GET['id'] ?? 0;
    
    if (empty($vizId)) {
        $database->error("Visualizer ID is required");
    }
    
    $stmt = $conn->prepare("SELECT v.*, u.username 
                            FROM visualizers v 
                            LEFT JOIN users u ON v.user_id = u.id 
                            WHERE v.id = ?");
    $stmt->bind_param("i", $vizId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $database->error("Visualizer not found");
    }
    
    $database->success($result->fetch_assoc());
}

// Delete User (Admin)
if ($action === 'delete_user') {
    $userId = $_GET['id'] ?? 0;
    
    if (empty($userId)) {
        $database->error("User ID is required");
    }
    
    // Delete user (cascade will delete related data)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        $database->success(null, "User deleted successfully");
    } else {
        $database->error("Failed to delete user");
    }
}

// Delete Visualizer (Admin)
if ($action === 'delete_visualizer_admin') {
    $vizId = $_GET['id'] ?? 0;
    
    if (empty($vizId)) {
        $database->error("Visualizer ID is required");
    }
    
    $stmt = $conn->prepare("DELETE FROM visualizers WHERE id = ?");
    $stmt->bind_param("i", $vizId);
    
    if ($stmt->execute()) {
        $database->success(null, "Visualizer deleted successfully");
    } else {
        $database->error("Failed to delete visualizer");
    }
}

// Get Feedback (Admin)
if ($action === 'get_feedback') {
    $query = "SELECT f.*, u.username 
              FROM feedback f 
              LEFT JOIN users u ON f.user_id = u.id 
              ORDER BY f.created_at DESC";
    
    $result = $conn->query($query);
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    $database->success($feedback);
}

// Get Feedback Details (Admin)
if ($action === 'get_feedback_details') {
    $feedbackId = $_GET['id'] ?? 0;
    
    if (empty($feedbackId)) {
        $database->error("Feedback ID is required");
    }
    
    $stmt = $conn->prepare("SELECT f.*, u.username 
                            FROM feedback f 
                            LEFT JOIN users u ON f.user_id = u.id 
                            WHERE f.id = ?");
    $stmt->bind_param("i", $feedbackId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $database->error("Feedback not found");
    }
    
    $database->success($result->fetch_assoc());
}

// =====================================================
// ADMIN DASHBOARD & DATABASE TEST
// =====================================================

// Admin Dashboard - Real-time monitoring
if ($action === 'admin_dashboard') {
    // Get Statistics
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()");
    $stats['active_sessions'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM visualizers");
    $stats['total_visualizers'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT SUM(videos_exported) as total FROM user_stats");
    $stats['total_exports'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['active_users_24h'] = $result->fetch_assoc()['count'];
    
    // Get Recent Users
    $recent_users = [];
    $result = $conn->query("SELECT id, username, email, created_at, last_login FROM users ORDER BY created_at DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
    
    // Get Active Sessions
    $active_sessions = [];
    $result = $conn->query("SELECT us.*, u.username FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.expires_at > NOW() ORDER BY us.last_activity DESC LIMIT 20");
    while ($row = $result->fetch_assoc()) {
        $active_sessions[] = $row;
    }
    
    // Get Recent Visualizers
    $recent_visualizers = [];
    $result = $conn->query("SELECT v.id, v.name, v.created_at, u.username FROM visualizers v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $recent_visualizers[] = $row;
    }
    
    // Get Recent Activity
    $recent_activity = [];
    $result = $conn->query("SELECT ua.*, u.username FROM user_activity ua JOIN users u ON ua.user_id = u.id ORDER BY ua.created_at DESC LIMIT 20");
    while ($row = $result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
    
    $database->success([
        'stats' => $stats,
        'recent_users' => $recent_users,
        'active_sessions' => $active_sessions,
        'recent_visualizers' => $recent_visualizers,
        'recent_activity' => $recent_activity
    ]);
}

// Database Test - Check connection and tables
if ($action === 'test_database') {
    $test_results = [];
    
    // Test 1: Connection
    $test_results['connection'] = [
        'status' => 'success',
        'message' => 'Database connected successfully',
        'host' => DB_HOST,
        'database' => DB_NAME
    ];
    
    // Test 2: Tables
    $required_tables = ['users', 'user_sessions', 'user_stats', 'visualizers', 'user_activity', 'templates', 'feedback'];
    $tables_status = [];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $result->num_rows > 0;
        
        if ($exists) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            $tables_status[$table] = [
                'exists' => true,
                'row_count' => $count
            ];
        } else {
            $tables_status[$table] = [
                'exists' => false,
                'row_count' => 0
            ];
        }
    }
    
    $test_results['tables'] = $tables_status;
    
    // Test 3: Sample Data
    $sample_data = [];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $sample_data['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()");
    $sample_data['active_sessions'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM visualizers");
    $sample_data['total_visualizers'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM templates");
    $sample_data['total_templates'] = $result->fetch_assoc()['count'];
    
    $test_results['sample_data'] = $sample_data;
    
    // Test 4: Recent Users
    $recent_users = [];
    $result = $conn->query("SELECT id, username, email, created_at, last_login FROM users ORDER BY created_at DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
    $test_results['recent_users'] = $recent_users;
    
    // Test 5: Active Sessions
    $active_sessions = [];
    $result = $conn->query("SELECT us.id, us.user_id, u.username, us.ip_address, us.created_at, us.last_activity FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.expires_at > NOW() ORDER BY us.last_activity DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $active_sessions[] = $row;
    }
    $test_results['active_sessions'] = $active_sessions;
    
    $database->success($test_results);
}

// Increment Export Count
if ($action === 'increment_export') {
    $stmt = $conn->prepare("UPDATE user_stats SET videos_exported = videos_exported + 1, last_export = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    
    if ($stmt->execute()) {
        logActivity($currentUser['id'], 'video_export', ['timestamp' => time()], $conn);
        $database->success(null, "Export count updated");
    } else {
        $database->error("Failed to update export count");
    }
}

// =====================================================
// ENHANCED ADMIN ENDPOINTS
// =====================================================

// Delete User (Admin)
if ($action === 'admin_delete_user') {
    $userId = $_POST['user_id'] ?? 0;
    
    if (!$userId) {
        $database->error("User ID required");
    }
    
    // Delete user and all related data
    $conn->begin_transaction();
    
    try {
        $conn->query("DELETE FROM user_sessions WHERE user_id = $userId");
        $conn->query("DELETE FROM user_stats WHERE user_id = $userId");
        $conn->query("DELETE FROM user_activity WHERE user_id = $userId");
        $conn->query("DELETE FROM visualizers WHERE user_id = $userId");
        $conn->query("DELETE FROM users WHERE id = $userId");
        
        $conn->commit();
        $database->success(null, "User deleted successfully");
    } catch (Exception $e) {
        $conn->rollback();
        $database->error("Failed to delete user: " . $e->getMessage());
    }
}

// Ban/Unban User (Admin)
if ($action === 'admin_ban_user') {
    $userId = $_POST['user_id'] ?? 0;
    $banned = $_POST['banned'] ?? 0;
    
    if (!$userId) {
        $database->error("User ID required");
    }
    
    $stmt = $conn->prepare("UPDATE users SET banned = ? WHERE id = ?");
    $stmt->bind_param("ii", $banned, $userId);
    
    if ($stmt->execute()) {
        $action_text = $banned ? "banned" : "unbanned";
        $database->success(null, "User $action_text successfully");
    } else {
        $database->error("Failed to update user status");
    }
}

// Get System Info (Admin)
if ($action === 'admin_system_info') {
    $info = [
        'php_version' => phpversion(),
        'mysql_version' => $conn->server_info,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'disk_free_space' => disk_free_space('.'),
        'disk_total_space' => disk_total_space('.'),
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
    
    $database->success($info);
}

// Clear Cache/Sessions (Admin)
if ($action === 'admin_clear_cache') {
    $type = $_POST['type'] ?? 'sessions';
    
    switch ($type) {
        case 'sessions':
            $conn->query("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $database->success(null, "Expired sessions cleared");
            break;
        case 'activity':
            $conn->query("DELETE FROM user_activity WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $database->success(null, "Old activity logs cleared");
            break;
        case 'all':
            $conn->query("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $conn->query("DELETE FROM user_activity WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $database->success(null, "All cache cleared");
            break;
        default:
            $database->error("Invalid cache type");
    }
}

// Export Data (Admin)
if ($action === 'admin_export_data') {
    $type = $_GET['type'] ?? 'users';
    
    switch ($type) {
        case 'users':
            $result = $conn->query("SELECT id, username, email, created_at, last_login FROM users ORDER BY created_at DESC");
            break;
        case 'visualizers':
            $result = $conn->query("SELECT v.*, u.username FROM visualizers v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC");
            break;
        case 'feedback':
            $result = $conn->query("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
            break;
        default:
            $database->error("Invalid export type");
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Y-m-d') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Update Feedback Status (Admin)
if ($action === 'admin_update_feedback') {
    $feedbackId = $_POST['feedback_id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    
    if (!$feedbackId) {
        $database->error("Feedback ID required");
    }
    
    $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $feedbackId);
    
    if ($stmt->execute()) {
        $database->success(null, "Feedback status updated");
    } else {
        $database->error("Failed to update feedback status");
    }
}

// Get Analytics (Admin)
if ($action === 'admin_analytics') {
    $analytics = [];
    
    // User registration trends (last 30 days)
    $result = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date DESC
    ");
    $analytics['user_registrations'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['user_registrations'][] = $row;
    }
    
    // Visualizer creation trends
    $result = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM visualizers 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date DESC
    ");
    $analytics['visualizer_creations'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['visualizer_creations'][] = $row;
    }
    
    // Most active users
    $result = $conn->query("
        SELECT u.username, COUNT(ua.id) as activity_count 
        FROM users u 
        LEFT JOIN user_activity ua ON u.id = ua.user_id 
        WHERE ua.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY u.id 
        ORDER BY activity_count DESC 
        LIMIT 10
    ");
    $analytics['most_active_users'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['most_active_users'][] = $row;
    }
    
    $database->success($analytics);
}

// Default response
$database->error("Invalid API endpoint");
