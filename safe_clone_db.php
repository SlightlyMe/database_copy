<?php
/**
 * Database Tools - Browser-Friendly Single File Solution
 * 
 * Professional database documentation and cloning tools for PHP developers
 * Generate AI-friendly database documentation and create realistic dummy data
 * 
 * Browser Usage:
 *   http://yoursite.com/database_tools.php                           (Main interface)
 *   http://yoursite.com/database_tools.php?action=structure          (Generate docs)
 *   http://yoursite.com/database_tools.php?action=structure&show=1   (With sensitive data)
 *   http://yoursite.com/database_tools.php?action=clone&records=100  (Clone with dummy data)
 * 
 * Command Line Usage (optional):
 *   php database_tools.php structure [show]
 *   php database_tools.php clone [records]
 * 
 * Requires: .env file with DB_HOST, DB_NAME, DB_USER, DB_PASS
 * 
 * @version 1.0.0
 * @author Database Tools Team
 * @license MIT
 */

// ============================================================================
// FEATURE TOGGLES - Configure what you want enabled
// ============================================================================

const FEATURE_TOGGLES = [
    // Core Features
    'STRUCTURE_GENERATOR'       => true,   // Generate .db_structure files for AI
    'DATABASE_CLONER'           => true,   // Generate clone scripts with dummy data
    'BROWSER_INTERFACE'         => true,   // Show HTML interface in browser
    
    // Security Features  
    'REQUIRE_AUTH'              => false,  // Require authentication (set password below)
    'AUTH_PASSWORD'             => 'admin123', // Change this password!
    'PERMISSION_VALIDATION'     => true,   // Check database permissions
    'FILE_OVERWRITE_PROTECTION' => true,   // Prevent overwriting system files
    'PRODUCTION_WARNINGS'       => true,   // Warn about production usage
    
    // Output Features
    'PROGRESS_INDICATORS'       => true,   // Show progress during operations
    'COLORED_OUTPUT'            => false,  // Use colors (browser doesn't need this)
    'VERBOSE_LOGGING'           => false,  // Detailed operation logging
    'DOWNLOAD_FILES'            => true,   // Offer file downloads in browser
    
    // Data Generation Features
    'SMART_COLUMN_DETECTION'    => true,   // Intelligent dummy data based on column names
    'FOREIGN_KEY_HANDLING'      => true,   // Maintain referential integrity
    'REALISTIC_DATES'           => true,   // Generate dates within realistic ranges
    'LOREM_IPSUM_TEXT'          => true,   // Use Lorem Ipsum for text fields
    
    // Documentation Features
    'SAMPLE_DATA_PREVIEW'       => true,   // Show sample data in structure docs
    'TABLE_RELATIONSHIPS'       => true,   // Document foreign key relationships
    'INDEX_DOCUMENTATION'       => true,   // Include index information
    'ENGINE_DETAILS'            => true,   // Show storage engine info
    
    // Safety Features
    'BACKUP_RECOMMENDATIONS'    => true,   // Suggest database backups
    'MEMORY_LIMIT_CHECKS'       => true,   // Monitor memory usage for large operations
    'MAX_RECORDS_LIMIT'         => 5000,   // Maximum records per table (0 = unlimited)
    
    // Environment Features
    'MULTI_ENV_SUPPORT'         => true,   // Support .env.development, .env.staging, etc.
    'AUTO_ENV_DETECTION'        => true,   // Auto-detect environment from hostname
    'CONFIG_VALIDATION'         => true,   // Validate .env file contents
];

// ============================================================================
// CONFIGURATION & CONSTANTS
// ============================================================================

define('VERSION', '1.0.0');
define('MAX_MEMORY', '512M');

// Determine if running in browser or command line
$is_browser = !empty($_SERVER['HTTP_HOST']);
$is_cli = php_sapi_name() === 'cli';

// Get action from URL parameters or command line
if ($is_browser) {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'interface';
    $option = $_GET['show'] ?? $_POST['show'] ?? $_GET['records'] ?? $_POST['records'] ?? null;
} else {
    $action = $argv[1] ?? 'help';
    $option = $argv[2] ?? null;
}

// ============================================================================
// AUTHENTICATION CHECK
// ============================================================================

if (FEATURE_TOGGLES['REQUIRE_AUTH'] && $is_browser) {
    session_start();
    
    if ($_POST['password'] ?? false) {
        if ($_POST['password'] === FEATURE_TOGGLES['AUTH_PASSWORD']) {
            $_SESSION['authenticated'] = true;
        } else {
            $auth_error = "Invalid password";
        }
    }
    
    if (!($_SESSION['authenticated'] ?? false)) {
        showLoginForm($auth_error ?? null);
        exit;
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Output message (browser or CLI appropriate)
 */
function output($message, $type = 'info') {
    global $is_browser;
    
    if ($is_browser) {
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : 'info');
        echo "<div class='message $class'>$message</div>";
    } else {
        echo $message;
    }
}

/**
 * Output line (browser or CLI appropriate)
 */
function outputLine($message, $type = 'info') {
    global $is_browser;
    output($message . ($is_browser ? '' : "\n"), $type);
}

/**
 * Show progress indicator
 */
function showProgress($message) {
    if (FEATURE_TOGGLES['PROGRESS_INDICATORS']) {
        output($message . "... ");
        if (!empty($_SERVER['HTTP_HOST'])) {
            echo "<script>document.body.scrollTop = document.body.scrollHeight;</script>";
            flush();
        }
    }
}

/**
 * Mark progress as complete
 */
function progressDone() {
    if (FEATURE_TOGGLES['PROGRESS_INDICATORS']) {
        outputLine("✓", 'success');
    }
}

/**
 * Show warning message
 */
function showWarning($message) {
    outputLine("⚠️ WARNING: " . $message, 'warning');
}

/**
 * Show error and exit
 */
function showError($message) {
    outputLine("❌ ERROR: " . $message, 'error');
    if (!empty($_SERVER['HTTP_HOST'])) {
        echo "</div></body></html>";
    }
    exit(1);
}

/**
 * Format bytes to human readable
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// ============================================================================
// BROWSER INTERFACE
// ============================================================================

/**
 * Show login form
 */
function showLoginForm($error = null) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Tools - Login</title>
        <style><?php echo getCSS(); ?></style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Database Tools - Authentication Required</h1>
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Show main browser interface
 */
function showBrowserInterface() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Tools v<?php echo VERSION; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style><?php echo getCSS(); ?></style>
    </head>
    <body>
        <div class="container">
            <header>
                <h1>🗄️ Database Tools v<?php echo VERSION; ?></h1>
                <p>Professional database documentation and cloning tools for PHP developers</p>
            </header>

            <div class="grid">
                <?php if (FEATURE_TOGGLES['STRUCTURE_GENERATOR']): ?>
                <div class="card">
                    <h2>📋 Generate Documentation</h2>
                    <p>Create AI-friendly database structure documentation perfect for ChatGPT, Claude, and other AI assistants.</p>
                    
                    <form method="get" style="margin-bottom: 1rem;">
                        <input type="hidden" name="action" value="structure">
                        <label>
                            <input type="checkbox" name="show" value="1"> 
                            Include sensitive data (row counts, sample data)
                        </label>
                        <button type="submit" class="btn btn-primary">Generate .db_structure</button>
                    </form>
                    
                    <div class="example">
                        <strong>Example URLs:</strong><br>
                        <code>?action=structure</code> - Safe mode (no sensitive data)<br>
                        <code>?action=structure&show=1</code> - Full details (development only)
                    </div>
                </div>
                <?php endif; ?>

                <?php if (FEATURE_TOGGLES['DATABASE_CLONER']): ?>
                <div class="card">
                    <h2>🎭 Clone Database</h2>
                    <p>Generate a complete database clone script with realistic dummy data for development and testing.</p>
                    
                    <form method="get" style="margin-bottom: 1rem;">
                        <input type="hidden" name="action" value="clone">
                        <label>
                            Records per table: 
                            <input type="number" name="records" value="25" min="1" max="<?php echo FEATURE_TOGGLES['MAX_RECORDS_LIMIT']; ?>" style="width: 100px;">
                        </label>
                        <button type="submit" class="btn btn-success">Generate Clone Script</button>
                    </form>
                    
                    <div class="example">
                        <strong>Example URLs:</strong><br>
                        <code>?action=clone</code> - 25 records per table<br>
                        <code>?action=clone&records=100</code> - 100 records per table
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="features">
                <h2>✨ Features</h2>
                <div class="feature-grid">
                    <div>🤖 AI-Optimized Documentation</div>
                    <div>🎯 Smart Column Detection</div>
                    <div>🔗 Foreign Key Handling</div>
                    <div>🔒 Secure by Design</div>
                    <div>📊 Relationship Mapping</div>
                    <div>⚡ Zero Dependencies</div>
                </div>
            </div>

            <div class="requirements">
                <h2>⚙️ Requirements</h2>
                <ul>
                    <li><strong>.env file</strong> with database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)</li>
                    <li><strong>PHP 7.4+</strong> with PDO MySQL extension</li>
                    <li><strong>Database user</strong> with SELECT privileges on information_schema</li>
                </ul>
            </div>

            <div class="security">
                <h2>🔒 Security Notes</h2>
                <ul>
                    <li>Never run the cloner on production databases</li>
                    <li>Use "show sensitive data" only in development environments</li>
                    <li>Protect your .env files from web access</li>
                    <li>Review generated SQL before importing to databases</li>
                </ul>
            </div>

            <footer>
                <p>Database Tools v<?php echo VERSION; ?> | Built for the AI-assisted development era</p>
            </footer>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Get CSS styles for browser interface
 */
function getCSS() {
    return '
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6; margin: 0; padding: 20px; background: #f5f7fa; color: #333;
        }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        header { text-align: center; margin-bottom: 2rem; }
        h1 { color: #2c3e50; margin-bottom: 0.5rem; }
        h2 { color: #34495e; margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .card { 
            background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #e9ecef;
            border-left: 4px solid #007bff;
        }
        .btn { 
            background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; 
            border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;
            margin-top: 1rem; font-size: 14px; font-weight: 500;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="number"], input[type="password"] { 
            width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; 
        }
        .example { 
            background: #f1f3f4; padding: 1rem; border-radius: 4px; margin-top: 1rem;
            font-size: 0.9em; border-left: 3px solid #6c757d;
        }
        code { 
            background: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 3px; 
            font-family: "SFMono-Regular", Consolas, monospace; font-size: 0.9em;
        }
        .features { margin: 2rem 0; }
        .feature-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1rem; margin-top: 1rem; 
        }
        .feature-grid > div { 
            background: #e3f2fd; padding: 0.75rem; border-radius: 4px; text-align: center; 
            font-weight: 500; color: #1565c0;
        }
        .requirements, .security { 
            background: #fff3cd; padding: 1.5rem; border-radius: 8px; 
            border-left: 4px solid #ffc107; margin: 1rem 0;
        }
        .security { background: #f8d7da; border-color: #dc3545; }
        footer { text-align: center; margin-top: 2rem; color: #6c757d; font-size: 0.9em; }
        .message { 
            padding: 1rem; margin: 1rem 0; border-radius: 4px; font-weight: 500;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .message.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .output { 
            background: #f8f9fa; border: 1px solid #e9ecef; padding: 1rem; 
            border-radius: 4px; font-family: monospace; white-space: pre-wrap; 
            max-height: 400px; overflow-y: auto; margin: 1rem 0;
        }
        .download { 
            background: #28a745; color: white; padding: 0.5rem 1rem; 
            text-decoration: none; border-radius: 4px; display: inline-block; margin: 0.5rem;
        }
        .download:hover { background: #1e7e34; }
    ';
}

// ============================================================================
// ENVIRONMENT LOADER & DATABASE CONNECTION
// ============================================================================

/**
 * Load environment variables from .env file
 */
function loadEnv($file = '.env') {
    // Auto-detect environment file if enabled
    if (FEATURE_TOGGLES['AUTO_ENV_DETECTION'] && FEATURE_TOGGLES['MULTI_ENV_SUPPORT']) {
        $hostname = gethostname();
        if (strpos($hostname, 'staging') !== false && file_exists('.env.staging')) {
            $file = '.env.staging';
        } elseif (strpos($hostname, 'prod') !== false && file_exists('.env.production')) {
            $file = '.env.production';
        } elseif (file_exists('.env.development')) {
            $file = '.env.development';
        }
    }
    
    if (!file_exists($file)) {
        showError("Environment file '$file' not found. Create a .env file with: DB_HOST, DB_NAME, DB_USER, DB_PASS");
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (empty(trim($line)) || strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    
    // Validate configuration
    if (FEATURE_TOGGLES['CONFIG_VALIDATION']) {
        $required = ['DB_HOST', 'DB_NAME', 'DB_USER'];
        $missing = array_filter($required, function($var) { return empty($_ENV[$var]); });
        if (!empty($missing)) {
            showError("Missing required environment variables: " . implode(', ', $missing));
        }
    }
}

/**
 * Get database connection
 */
function getDatabase() {
    loadEnv();
    
    try {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'] ?? '', $options);
        
        // Validate permissions
        if (FEATURE_TOGGLES['PERMISSION_VALIDATION']) {
            $pdo->query("SELECT 1 FROM information_schema.tables LIMIT 1");
        }
        
        return [$pdo, $_ENV['DB_NAME']];
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            showError("Database credentials invalid. Check your .env file.");
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            showError("Database '{$_ENV['DB_NAME']}' does not exist.");
        } else {
            showError("Database connection failed: " . $e->getMessage());
        }
    }
}

/**
 * Safe file write with protection
 */
function safeFileWrite($filename, $content) {
    if (FEATURE_TOGGLES['FILE_OVERWRITE_PROTECTION']) {
        $dangerous_files = ['.htaccess', 'index.php', 'config.php', 'wp-config.php'];
        if (in_array(basename($filename), $dangerous_files)) {
            showError("Cannot overwrite system file '$filename' for security reasons.");
        }
    }
    
    $temp = $filename . '.tmp';
    if (file_put_contents($temp, $content, LOCK_EX) === false) {
        showError("Cannot write to temporary file. Check directory permissions.");
    }
    
    if (!rename($temp, $filename)) {
        unlink($temp);
        showError("Cannot finalize file '$filename'. Check directory permissions.");
    }
}

// ============================================================================
// DATABASE STRUCTURE GENERATOR
// ============================================================================

if (FEATURE_TOGGLES['STRUCTURE_GENERATOR']) {

class DatabaseStructureGenerator {
    private $pdo;
    private $database_name;
    private $show_sensitive;
    
    public function __construct($pdo, $database_name, $show_sensitive = false) {
        $this->pdo = $pdo;
        $this->database_name = $database_name;
        $this->show_sensitive = $show_sensitive;
    }
    
    public function generateStructureFile($filename = '.db_structure') {
        if (FEATURE_TOGGLES['MEMORY_LIMIT_CHECKS']) {
            ini_set('memory_limit', MAX_MEMORY);
        }
        
        showProgress("Analyzing database structure");
        
        $output = [];
        $output[] = "DATABASE STRUCTURE REFERENCE";
        $output[] = "Generated: " . date('Y-m-d H:i:s');
        $output[] = "Database: " . $this->database_name;
        $output[] = "Generator: Database Tools v" . VERSION;
        $output[] = "Mode: " . ($this->show_sensitive ? "DEVELOPMENT (sensitive data visible)" : "SECURE (sensitive data hidden)");
        $output[] = str_repeat("=", 80);
        $output[] = "";
        
        progressDone();
        
        // Get tables
        showProgress("Scanning tables");
        $tables = $this->getAllTables();
        progressDone();
        
        // Table of Contents
        $output[] = "TABLE OF CONTENTS:";
        foreach ($tables as $table) {
            if ($this->show_sensitive && FEATURE_TOGGLES['SAMPLE_DATA_PREVIEW']) {
                $output[] = "  - {$table['table_name']} ({$table['row_count']} rows)";
            } else {
                $output[] = "  - {$table['table_name']}";
            }
        }
        $output[] = "";
        $output[] = str_repeat("=", 80);
        $output[] = "";
        
        // Database Summary
        $output[] = "DATABASE SUMMARY:";
        $output[] = "Total Tables: " . count($tables);
        if ($this->show_sensitive) {
            $output[] = "Total Rows: " . number_format(array_sum(array_column($tables, 'row_count')));
        } else {
            $output[] = "Total Rows: [Hidden - use show=1 parameter to display]";
        }
        
        if (FEATURE_TOGGLES['ENGINE_DETAILS']) {
            $output[] = "Primary Engine: " . $this->getMostCommonEngine();
            $output[] = "Character Set: " . $this->getDatabaseCharset();
        }
        $output[] = "";
        
        // Relationships
        if (FEATURE_TOGGLES['TABLE_RELATIONSHIPS']) {
            showProgress("Mapping relationships");
            $relationships = $this->getForeignKeyRelationships();
            if (!empty($relationships)) {
                $output[] = "FOREIGN KEY RELATIONSHIPS:";
                foreach ($relationships as $rel) {
                    $output[] = "  {$rel['table']}.{$rel['column']} -> {$rel['referenced_table']}.{$rel['referenced_column']}";
                }
                $output[] = "";
            }
            progressDone();
        }
        
        $output[] = str_repeat("=", 80);
        $output[] = "";
        
        // Detailed table structures
        showProgress("Generating detailed documentation");
        foreach ($tables as $table) {
            $output = array_merge($output, $this->generateTableStructure($table['table_name']));
            $output[] = "";
        }
        progressDone();
        
        // Write file
        showProgress("Writing documentation file");
        $content = implode("\n", $output);
        safeFileWrite($filename, $content);
        progressDone();
        
        return [
            'filename' => $filename,
            'tables_count' => count($tables),
            'total_rows' => $this->show_sensitive ? array_sum(array_column($tables, 'row_count')) : 0,
            'file_size' => strlen($content),
            'content' => $content
        ];
    }
    
    private function getAllTables() {
        $stmt = $this->pdo->query("
            SELECT table_name, table_comment, table_rows, engine, table_collation
            FROM information_schema.tables 
            WHERE table_schema = '{$this->database_name}' AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->show_sensitive && FEATURE_TOGGLES['SAMPLE_DATA_PREVIEW']) {
                try {
                    $countStmt = $this->pdo->query("SELECT COUNT(*) as actual_count FROM `{$row['table_name']}`");
                    $actualCount = $countStmt->fetch(PDO::FETCH_ASSOC)['actual_count'];
                } catch (Exception $e) {
                    $actualCount = $row['table_rows'] ?? 0;
                }
            } else {
                $actualCount = 0;
            }
            
            $tables[] = [
                'table_name' => $row['table_name'],
                'table_comment' => $row['table_comment'],
                'row_count' => $actualCount,
                'engine' => $row['engine'],
                'collation' => $row['table_collation']
            ];
        }
        return $tables;
    }
    
    private function generateTableStructure($tableName) {
        $output = [];
        $output[] = "TABLE: {$tableName}";
        $output[] = str_repeat("-", 40);
        
        if (FEATURE_TOGGLES['ENGINE_DETAILS']) {
            $tableInfo = $this->getTableInfo($tableName);
            if ($tableInfo['table_comment']) {
                $output[] = "Description: {$tableInfo['table_comment']}";
            }
            $output[] = "Engine: {$tableInfo['engine']}";
            $output[] = "Collation: {$tableInfo['table_collation']}";
            $output[] = "";
        }
        
        // Columns
        $columns = $this->getTableColumns($tableName);
        $output[] = "COLUMNS:";
        $output[] = sprintf("%-20s %-25s %-8s %-8s %-15s %s", "Name", "Type", "Null", "Key", "Default", "Comment");
        $output[] = str_repeat("-", 100);
        
        foreach ($columns as $col) {
            $default = $col['column_default'] ?? 'NULL';
            if ($default === null) $default = 'NULL';
            if (strlen($default) > 14) $default = substr($default, 0, 11) . '...';
            
            $output[] = sprintf("%-20s %-25s %-8s %-8s %-15s %s",
                $col['column_name'], $col['column_type'],
                $col['is_nullable'] === 'YES' ? 'YES' : 'NO',
                $col['column_key'], $default, $col['column_comment']
            );
        }
        $output[] = "";
        
        // Primary Key
        $primaryKey = $this->getPrimaryKey($tableName);
        if ($primaryKey) {
            $output[] = "PRIMARY KEY: " . implode(', ', $primaryKey);
            $output[] = "";
        }
        
        // Foreign Keys
        if (FEATURE_TOGGLES['TABLE_RELATIONSHIPS']) {
            $foreignKeys = $this->getTableForeignKeys($tableName);
            if (!empty($foreignKeys)) {
                $output[] = "FOREIGN KEYS:";
                foreach ($foreignKeys as $fk) {
                    $output[] = "  {$fk['column_name']} -> {$fk['referenced_table_name']}.{$fk['referenced_column_name']}";
                }
                $output[] = "";
            }
        }
        
        // Sample data
        if ($this->show_sensitive && FEATURE_TOGGLES['SAMPLE_DATA_PREVIEW']) {
            $sampleData = $this->getSampleData($tableName, 3);
            if (!empty($sampleData)) {
                $output[] = "SAMPLE DATA (first 3 rows):";
                $columnNames = array_keys($sampleData[0]);
                $output[] = implode(' | ', $columnNames);
                $output[] = str_repeat("-", min(80, strlen(implode(' | ', $columnNames))));
                
                foreach ($sampleData as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) $values[] = 'NULL';
                        elseif (strlen($value) > 15) $values[] = substr($value, 0, 12) . '...';
                        else $values[] = $value;
                    }
                    $output[] = implode(' | ', $values);
                }
                $output[] = "";
            }
        } else {
            $output[] = "SAMPLE DATA: [Hidden - use show=1 parameter to display actual data]";
            $output[] = "";
        }
        
        $output[] = str_repeat("=", 80);
        return $output;
    }
    
    // Helper methods (abbreviated for space)
    private function getTableInfo($tableName) {
        $stmt = $this->pdo->prepare("SELECT engine, table_comment, table_collation FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
        $stmt->execute([$this->database_name, $tableName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTableColumns($tableName) {
        $stmt = $this->pdo->prepare("SELECT column_name, column_type, is_nullable, column_key, column_default, column_comment FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position");
        $stmt->execute([$this->database_name, $tableName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPrimaryKey($tableName) {
        $stmt = $this->pdo->prepare("SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = ? AND table_name = ? AND constraint_name = 'PRIMARY' ORDER BY ordinal_position");
        $stmt->execute([$this->database_name, $tableName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getTableForeignKeys($tableName) {
        $stmt = $this->pdo->prepare("SELECT column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE table_schema = ? AND table_name = ? AND referenced_table_name IS NOT NULL");
        $stmt->execute([$this->database_name, $tableName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getForeignKeyRelationships() {
        $stmt = $this->pdo->prepare("SELECT table_name as 'table', column_name as 'column', referenced_table_name as referenced_table, referenced_column_name as referenced_column FROM information_schema.key_column_usage WHERE table_schema = ? AND referenced_table_name IS NOT NULL ORDER BY table_name, column_name");
        $stmt->execute([$this->database_name]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getSampleData($tableName, $limit = 3) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `{$tableName}` LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getMostCommonEngine() {
        $stmt = $this->pdo->prepare("SELECT engine FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE' GROUP BY engine ORDER BY COUNT(*) DESC LIMIT 1");
        $stmt->execute([$this->database_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['engine'] : 'Unknown';
    }
    
    private function getDatabaseCharset() {
        $stmt = $this->pdo->prepare("SELECT default_character_set_name FROM information_schema.schemata WHERE schema_name = ?");
        $stmt->execute([$this->database_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['default_character_set_name'] : 'Unknown';
    }
}

} // End STRUCTURE_GENERATOR

// ============================================================================
// DATABASE CLONER (Simplified version for space)
// ============================================================================

if (FEATURE_TOGGLES['DATABASE_CLONER']) {

class DatabaseCloner {
    private $pdo;
    private $database_name;
    private $records_per_table;
    
    public function __construct($pdo, $database_name, $records_per_table = 25) {
        $this->pdo = $pdo;
        $this->database_name = $database_name;
        $this->records_per_table = min($records_per_table, FEATURE_TOGGLES['MAX_RECORDS_LIMIT'] ?: PHP_INT_MAX);
    }
    
    public function generateCloneScript($output_file = 'database_clone.sql') {
        // Production warning
        if (FEATURE_TOGGLES['PRODUCTION_WARNINGS']) {
            $hostname = gethostname();
            if (strpos(strtolower($hostname), 'prod') !== false) {
                showWarning("Production environment detected! This tool should only be used for development.");
            }
        }
        
        showProgress("Analyzing database structure");
        $tables = $this->getTables();
        progressDone();
        
        showProgress("Generating SQL script with dummy data");
        
        $sql = [];
        $sql[] = "-- Database Clone Script with Dummy Data";
        $sql[] = "-- Generated: " . date('Y-m-d H:i:s');
        $sql[] = "-- Original Database: {$this->database_name}";
        $sql[] = "-- Records per table: {$this->records_per_table}";
        $sql[] = "";
        $sql[] = "SET FOREIGN_KEY_CHECKS = 0;";
        $sql[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
        $sql[] = "";
        $sql[] = "CREATE DATABASE IF NOT EXISTS `{$this->database_name}_clone` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        $sql[] = "USE `{$this->database_name}_clone`;";
        $sql[] = "";
        
        // Create tables and insert dummy data
        foreach ($tables as $table) {
            $sql[] = "-- Table: {$table}";
            $sql[] = $this->getCreateStatement($table) . ";";
            $sql[] = "";
            
            // Generate dummy data
            $columns = $this->getTableColumns($table);
            $insertColumns = array_filter($columns, function($col) {
                return $col['extra'] !== 'auto_increment';
            });
            
            if (!empty($insertColumns)) {
                for ($i = 1; $i <= $this->records_per_table; $i++) {
                    $values = [];
                    foreach ($insertColumns as $col) {
                        $values[] = $this->generateDummyValue($col);
                    }
                    $columnNames = array_map(function($col) { return "`{$col['column_name']}`"; }, $insertColumns);
                    $sql[] = "INSERT INTO `{$table}` (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $values) . ");";
                }
            }
            $sql[] = "";
        }
        
        $sql[] = "SET FOREIGN_KEY_CHECKS = 1;";
        $content = implode("\n", $sql);
        
        progressDone();
        
        showProgress("Writing clone script");
        safeFileWrite($output_file, $content);
        progressDone();
        
        return [
            'output_file' => $output_file,
            'tables_count' => count($tables),
            'total_records' => count($tables) * $this->records_per_table,
            'file_size' => strlen($content),
            'content' => $content
        ];
    }
    
    private function getTables() {
        $stmt = $this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$this->database_name}' AND table_type = 'BASE TABLE' ORDER BY table_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getCreateStatement($table) {
        $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Create Table'];
    }
    
    private function getTableColumns($table) {
        $stmt = $this->pdo->prepare("SELECT column_name, data_type, column_type, is_nullable, column_default, extra FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position");
        $stmt->execute([$this->database_name, $table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateDummyValue($column) {
        $name = strtolower($column['column_name']);
        $type = $column['data_type'];
        
        // Smart column detection
        if (strpos($name, 'email') !== false) {
            return "'user" . rand(1, 999) . "@example.com'";
        } elseif (strpos($name, 'name') !== false) {
            $names = ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Brown'];
            return "'" . $names[array_rand($names)] . "'";
        } elseif (strpos($name, 'phone') !== false) {
            return "'555-" . sprintf('%03d', rand(100, 999)) . "-" . sprintf('%04d', rand(1000, 9999)) . "'";
        } elseif ($type === 'int' || $type === 'bigint') {
            return rand(1, 1000);
        } elseif ($type === 'decimal' || $type === 'float') {
            return number_format(rand(1, 10000) / 100, 2, '.', '');
        } elseif ($type === 'date') {
            return "'" . date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')) . "'";
        } elseif ($type === 'datetime' || $type === 'timestamp') {
            return "'" . date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days')) . "'";
        } elseif (strpos($column['column_type'], 'enum') !== false) {
            preg_match_all("/'([^']*)'/", $column['column_type'], $matches);
            return !empty($matches[1]) ? "'" . $matches[1][array_rand($matches[1])] . "'" : "'active'";
        } else {
            // Default text
            $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet'];
            return "'" . implode(' ', array_slice($words, 0, rand(1, 3))) . "'";
        }
    }
}

} // End DATABASE_CLONER

// ============================================================================
// MAIN EXECUTION
// ============================================================================

try {
    if ($is_browser && $action === 'interface') {
        showBrowserInterface();
        exit;
    }
    
    // Start output for browser operations
    if ($is_browser && $action !== 'interface') {
        echo '<!DOCTYPE html><html><head><title>Database Tools - Processing</title><style>' . getCSS() . '</style></head><body><div class="container">';
        echo '<h1>🗄️ Database Tools v' . VERSION . '</h1>';
        echo '<div class="output">';
    }
    
    switch ($action) {
        case 'structure':
            if (!FEATURE_TOGGLES['STRUCTURE_GENERATOR']) {
                showError("Structure generator is disabled.");
            }
            
            outputLine("📋 Generating Database Structure Documentation", 'info');
            
            list($pdo, $db_name) = getDatabase();
            $show_sensitive = !empty($option) || !empty($_GET['show']) || !empty($_POST['show']);
            
            if ($show_sensitive && FEATURE_TOGGLES['PRODUCTION_WARNINGS']) {
                showWarning("Showing sensitive data. Use only in development!");
            }
            
            $generator = new DatabaseStructureGenerator($pdo, $db_name, $show_sensitive);
            $result = $generator->generateStructureFile();
            
            outputLine("");
            outputLine("✅ SUCCESS!", 'success');
            outputLine("📄 File: " . $result['filename']);
            outputLine("📊 Tables: " . $result['tables_count']);
            if ($show_sensitive) {
                outputLine("📈 Total Rows: " . number_format($result['total_rows']));
            }
            outputLine("💾 File Size: " . formatBytes($result['file_size']));
            
            if ($is_browser && FEATURE_TOGGLES['DOWNLOAD_FILES']) {
                echo '</div>';
                echo '<a href="data:text/plain;charset=utf-8,' . rawurlencode($result['content']) . '" download="' . $result['filename'] . '" class="download">📥 Download ' . $result['filename'] . '</a>';
                echo '<p>The file has also been saved to your server directory.</p>';
            }
            break;
            
        case 'clone':
            if (!FEATURE_TOGGLES['DATABASE_CLONER']) {
                showError("Database cloner is disabled.");
            }
            
            outputLine("🎭 Generating Database Clone with Dummy Data", 'info');
            
            list($pdo, $db_name) = getDatabase();
            $records = intval($option ?: $_GET['records'] ?: $_POST['records'] ?: 25);
            
            if (FEATURE_TOGGLES['BACKUP_RECOMMENDATIONS']) {
                showWarning("Always backup your database before importing clone scripts!");
            }
            
            $cloner = new DatabaseCloner($pdo, $db_name, $records);
            $result = $cloner->generateCloneScript();
            
            outputLine("");
            outputLine("✅ SUCCESS!", 'success');
            outputLine("📄 File: " . $result['output_file']);
            outputLine("📊 Tables: " . $result['tables_count']);
            outputLine("📈 Total Records: " . number_format($result['total_records']));
            outputLine("💾 File Size: " . formatBytes($result['file_size']));
            
            if ($is_browser && FEATURE_TOGGLES['DOWNLOAD_FILES']) {
                echo '</div>';
                echo '<a href="data:application/sql;charset=utf-8,' . rawurlencode($result['content']) . '" download="' . $result['output_file'] . '" class="download">📥 Download ' . $result['output_file'] . '</a>';
                echo '<p>Import with: <code>mysql -u root -p &lt; ' . $result['output_file'] . '</code></p>';
            }
            break;
            
        case 'help':
        default:
            if ($is_browser) {
                showBrowserInterface();
            } else {
                outputLine("Database Tools v" . VERSION . " - Browser-Friendly Single File Solution");
                outputLine("");
                outputLine("Browser Usage:");
                outputLine("  http://yoursite.com/database_tools.php                           (Main interface)");
                outputLine("  http://yoursite.com/database_tools.php?action=structure          (Generate docs)");
                outputLine("  http://yoursite.com/database_tools.php?action=structure&show=1   (With sensitive data)");
                outputLine("  http://yoursite.com/database_tools.php?action=clone&records=100  (Clone with dummy data)");
                outputLine("");
                outputLine("Command Line Usage:");
                outputLine("  php database_tools.php structure [show]");
                outputLine("  php database_tools.php clone [records]");
                outputLine("");
                outputLine("Requires .env file with: DB_HOST, DB_NAME, DB_USER, DB_PASS");
            }
            break;
    }
    
    // Close browser output
    if ($is_browser && $action !== 'interface' && $action !== 'help') {
        echo '</div></body></html>';
    }
    
} catch (Exception $e) {
    showError($e->getMessage());
}
?>