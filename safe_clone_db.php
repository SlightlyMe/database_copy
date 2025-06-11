<?php
/**
 * All-in-One Database Cloner with Dummy Data Generator
 * 
 * Maps database structure, analyzes content patterns, and creates a complete 
 * SQL script to recreate the database with realistic dummy data
 * 
 * Usage: 
 *   Command line: php clone_database.php [records_per_table]
 *   Browser: clone_database.php?records=50
 * 
 * Output: database_clone.sql - Complete recreation script with dummy data
 */

require_once 'my_db.php';

class DatabaseCloner {
    private $pdo;
    private $database_name;
    private $records_per_table;
    private $tables_info = [];
    private $foreign_keys = [];
    private $generated_ids = [];
    
    // Dummy data pools
    private $lorem_words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
        'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint'
    ];
    
    private $first_names = [
        'James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda',
        'William', 'Elizabeth', 'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
        'Thomas', 'Sarah', 'Christopher', 'Karen', 'Charles', 'Nancy', 'Daniel', 'Lisa',
        'Matthew', 'Betty', 'Anthony', 'Helen', 'Mark', 'Sandra', 'Donald', 'Donna'
    ];
    
    private $last_names = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
        'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
        'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker'
    ];
    
    private $companies = [
        'TechCorp', 'DataSys', 'InfoTech', 'SoftWare Inc', 'Digital Solutions',
        'CloudTech', 'WebSystems', 'AppDev Ltd', 'CodeCraft', 'ByteWorks',
        'NetLogic', 'DevForce', 'TechFlow', 'DataStream', 'CyberTech'
    ];
    
    private $email_domains = [
        'example.com', 'test.org', 'sample.net', 'demo.com', 'dev.local',
        'staging.org', 'dummy.net', 'fake.com', 'mock.org'
    ];
    
    public function __construct($pdo, $database_name, $records_per_table = 25) {
        $this->pdo = $pdo;
        $this->database_name = $database_name;
        $this->records_per_table = $records_per_table;
    }
    
    public function generateCloneScript($output_file = 'database_clone.sql') {
        echo "Starting database analysis and clone generation...\n\n";
        
        // Step 1: Analyze database structure
        echo "Step 1: Analyzing database structure...\n";
        $this->analyzeDatabase();
        
        // Step 2: Map relationships
        echo "Step 2: Mapping table relationships...\n";
        $this->mapRelationships();
        
        // Step 3: Determine creation order
        echo "Step 3: Determining table creation order...\n";
        $creation_order = $this->getTableCreationOrder();
        
        // Step 4: Generate SQL script
        echo "Step 4: Generating SQL script with dummy data...\n";
        $sql_content = $this->generateSQL($creation_order);
        
        // Step 5: Write to file
        echo "Step 5: Writing SQL script to file...\n";
        file_put_contents($output_file, $sql_content);
        
        return [
            'output_file' => $output_file,
            'tables_count' => count($this->tables_info),
            'total_records' => count($this->tables_info) * $this->records_per_table,
            'file_size' => strlen($sql_content),
            'creation_order' => $creation_order
        ];
    }
    
    private function analyzeDatabase() {
        $stmt = $this->pdo->query("
            SELECT table_name
            FROM information_schema.tables 
            WHERE table_schema = '{$this->database_name}'
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "  Analyzing table: $table\n";
            $this->tables_info[$table] = [
                'create_statement' => $this->getCreateStatement($table),
                'columns' => $this->analyzeColumns($table),
                'primary_key' => $this->getPrimaryKey($table),
                'foreign_keys' => $this->getTableForeignKeys($table),
                'indexes' => $this->getTableIndexes($table)
            ];
        }
    }
    
    private function analyzeColumns($table) {
        $stmt = $this->pdo->prepare("
            SELECT 
                column_name,
                data_type,
                column_type,
                is_nullable,
                column_default,
                character_maximum_length,
                numeric_precision,
                numeric_scale,
                column_key,
                extra
            FROM information_schema.columns 
            WHERE table_schema = ? AND table_name = ?
            ORDER BY ordinal_position
        ");
        $stmt->execute([$this->database_name, $table]);
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['column_name']] = [
                'data_type' => $row['data_type'],
                'column_type' => $row['column_type'],
                'is_nullable' => $row['is_nullable'] === 'YES',
                'default' => $row['column_default'],
                'max_length' => $row['character_maximum_length'],
                'precision' => $row['numeric_precision'],
                'scale' => $row['numeric_scale'],
                'key' => $row['column_key'],
                'extra' => $row['extra'],
                'dummy_type' => $this->inferDummyDataType($row['column_name'], $row['data_type'], $row['column_type'])
            ];
        }
        
        return $columns;
    }
    
    private function inferDummyDataType($column_name, $data_type, $column_type) {
        $column_lower = strtolower($column_name);
        
        // Email patterns
        if (strpos($column_lower, 'email') !== false) {
            return 'email';
        }
        
        // Name patterns
        if (strpos($column_lower, 'first_name') !== false || strpos($column_lower, 'firstname') !== false) {
            return 'first_name';
        }
        if (strpos($column_lower, 'last_name') !== false || strpos($column_lower, 'lastname') !== false) {
            return 'last_name';
        }
        if (strpos($column_lower, 'name') !== false && strpos($column_lower, 'user') !== false) {
            return 'full_name';
        }
        if (strpos($column_lower, 'name') !== false) {
            return 'name';
        }
        
        // Phone patterns
        if (strpos($column_lower, 'phone') !== false || strpos($column_lower, 'tel') !== false) {
            return 'phone';
        }
        
        // Address patterns
        if (strpos($column_lower, 'address') !== false) {
            return 'address';
        }
        if (strpos($column_lower, 'city') !== false) {
            return 'city';
        }
        if (strpos($column_lower, 'state') !== false) {
            return 'state';
        }
        if (strpos($column_lower, 'zip') !== false || strpos($column_lower, 'postal') !== false) {
            return 'zipcode';
        }
        
        // Company patterns
        if (strpos($column_lower, 'company') !== false || strpos($column_lower, 'organization') !== false) {
            return 'company';
        }
        
        // URL patterns
        if (strpos($column_lower, 'url') !== false || strpos($column_lower, 'website') !== false) {
            return 'url';
        }
        
        // Date patterns
        if (strpos($column_lower, 'birth') !== false) {
            return 'birthdate';
        }
        
        // Description/text patterns
        if (strpos($column_lower, 'description') !== false || strpos($column_lower, 'content') !== false) {
            return 'lorem_paragraph';
        }
        if (strpos($column_lower, 'title') !== false) {
            return 'lorem_title';
        }
        
        // Password patterns
        if (strpos($column_lower, 'password') !== false || strpos($column_lower, 'hash') !== false) {
            return 'password_hash';
        }
        
        // Status/enum patterns
        if (strpos($column_type, 'enum') !== false) {
            return 'enum';
        }
        
        // Default based on data type
        switch ($data_type) {
            case 'varchar':
            case 'char':
            case 'text':
                return 'lorem_text';
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
                return 'integer';
            case 'decimal':
            case 'float':
            case 'double':
                return 'decimal';
            case 'date':
                return 'date';
            case 'datetime':
            case 'timestamp':
                return 'datetime';
            case 'time':
                return 'time';
            default:
                return 'lorem_text';
        }
    }
    
    private function generateDummyValue($column_name, $column_info, $table_name, $record_index) {
        $dummy_type = $column_info['dummy_type'];
        $max_length = $column_info['max_length'];
        
        switch ($dummy_type) {
            case 'email':
                $username = strtolower($this->first_names[array_rand($this->first_names)]) . 
                           strtolower($this->last_names[array_rand($this->last_names)]) . 
                           rand(1, 999);
                $domain = $this->email_domains[array_rand($this->email_domains)];
                return "'{$username}@{$domain}'";
                
            case 'first_name':
                return "'" . $this->first_names[array_rand($this->first_names)] . "'";
                
            case 'last_name':
                return "'" . $this->last_names[array_rand($this->last_names)] . "'";
                
            case 'full_name':
            case 'name':
                $first = $this->first_names[array_rand($this->first_names)];
                $last = $this->last_names[array_rand($this->last_names)];
                return "'{$first} {$last}'";
                
            case 'phone':
                return "'" . sprintf('555-%03d-%04d', rand(100, 999), rand(1000, 9999)) . "'";
                
            case 'address':
                $number = rand(100, 9999);
                $streets = ['Main St', 'Oak Ave', 'Pine Rd', 'First St', 'Second Ave', 'Park Blvd', 'Elm St'];
                return "'{$number} " . $streets[array_rand($streets)] . "'";
                
            case 'city':
                $cities = ['Springfield', 'Madison', 'Franklin', 'Georgetown', 'Clinton', 'Riverside', 'Fairview'];
                return "'" . $cities[array_rand($cities)] . "'";
                
            case 'state':
                $states = ['CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI'];
                return "'" . $states[array_rand($states)] . "'";
                
            case 'zipcode':
                return "'" . sprintf('%05d', rand(10000, 99999)) . "'";
                
            case 'company':
                return "'" . $this->companies[array_rand($this->companies)] . "'";
                
            case 'url':
                $domains = ['example.com', 'test.org', 'demo.net'];
                return "'https://www." . $domains[array_rand($domains)] . "'";
                
            case 'lorem_title':
                $words = array_rand(array_flip($this->lorem_words), rand(2, 4));
                $title = implode(' ', $words);
                return "'" . ucwords($title) . "'";
                
            case 'lorem_paragraph':
                $word_count = min(rand(20, 50), $max_length ? floor($max_length / 6) : 50);
                $words = array_rand(array_flip($this->lorem_words), $word_count);
                $text = implode(' ', $words);
                if ($max_length && strlen($text) > $max_length) {
                    $text = substr($text, 0, $max_length - 3) . '...';
                }
                return "'" . ucfirst($text) . "'";
                
            case 'lorem_text':
                $word_count = $max_length ? min(rand(1, 5), floor($max_length / 6)) : rand(1, 5);
                $words = array_rand(array_flip($this->lorem_words), $word_count);
                $text = implode(' ', $words);
                if ($max_length && strlen($text) > $max_length) {
                    $text = substr($text, 0, $max_length);
                }
                return "'" . ucfirst($text) . "'";
                
            case 'password_hash':
                // Generate a fake bcrypt hash
                return "'$2y$10$" . str_repeat('a', 53) . "'";
                
            case 'enum':
                // Extract enum values from column_type
                preg_match_all("/'([^']*)'/", $column_info['column_type'], $matches);
                if (!empty($matches[1])) {
                    return "'" . $matches[1][array_rand($matches[1])] . "'";
                }
                return "'active'";
                
            case 'integer':
                if (strpos($column_info['column_type'], 'unsigned') !== false) {
                    return rand(1, 1000000);
                }
                return rand(-1000000, 1000000);
                
            case 'decimal':
                $precision = $column_info['precision'] ?: 10;
                $scale = $column_info['scale'] ?: 2;
                $max_val = pow(10, $precision - $scale) - 1;
                return number_format(rand(1, $max_val) + (rand(0, 99) / 100), $scale, '.', '');
                
            case 'date':
                $start = strtotime('-5 years');
                $end = time();
                $random_time = rand($start, $end);
                return "'" . date('Y-m-d', $random_time) . "'";
                
            case 'datetime':
                $start = strtotime('-2 years');
                $end = time();
                $random_time = rand($start, $end);
                return "'" . date('Y-m-d H:i:s', $random_time) . "'";
                
            case 'time':
                return "'" . sprintf('%02d:%02d:%02d', rand(0, 23), rand(0, 59), rand(0, 59)) . "'";
                
            case 'birthdate':
                $start = strtotime('-80 years');
                $end = strtotime('-18 years');
                $random_time = rand($start, $end);
                return "'" . date('Y-m-d', $random_time) . "'";
                
            default:
                return "'Lorem ipsum'";
        }
    }
    
    private function mapRelationships() {
        foreach ($this->tables_info as $table => $info) {
            foreach ($info['foreign_keys'] as $fk) {
                $this->foreign_keys[] = [
                    'table' => $table,
                    'column' => $fk['column_name'],
                    'ref_table' => $fk['referenced_table_name'],
                    'ref_column' => $fk['referenced_column_name']
                ];
            }
        }
    }
    
    private function getTableCreationOrder() {
        $tables = array_keys($this->tables_info);
        $ordered = [];
        $remaining = $tables;
        
        // Simple dependency resolution
        while (!empty($remaining)) {
            $added_this_round = [];
            
            foreach ($remaining as $table) {
                $dependencies = [];
                foreach ($this->foreign_keys as $fk) {
                    if ($fk['table'] === $table && $fk['ref_table'] !== $table) {
                        $dependencies[] = $fk['ref_table'];
                    }
                }
                
                // Check if all dependencies are already ordered
                $can_add = true;
                foreach ($dependencies as $dep) {
                    if (!in_array($dep, $ordered)) {
                        $can_add = false;
                        break;
                    }
                }
                
                if ($can_add) {
                    $ordered[] = $table;
                    $added_this_round[] = $table;
                }
            }
            
            // Remove added tables from remaining
            $remaining = array_diff($remaining, $added_this_round);
            
            // Break if no progress (circular dependencies)
            if (empty($added_this_round) && !empty($remaining)) {
                $ordered = array_merge($ordered, $remaining);
                break;
            }
        }
        
        return $ordered;
    }
    
    private function generateSQL($creation_order) {
        $sql = [];
        
        // Header
        $sql[] = "-- Database Clone Script with Dummy Data";
        $sql[] = "-- Generated: " . date('Y-m-d H:i:s');
        $sql[] = "-- Original Database: {$this->database_name}";
        $sql[] = "-- Records per table: {$this->records_per_table}";
        $sql[] = "";
        $sql[] = "SET FOREIGN_KEY_CHECKS = 0;";
        $sql[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
        $sql[] = "SET time_zone = '+00:00';";
        $sql[] = "";
        
        // Create database
        $sql[] = "-- Create database";
        $sql[] = "CREATE DATABASE IF NOT EXISTS `{$this->database_name}_clone` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        $sql[] = "USE `{$this->database_name}_clone`;";
        $sql[] = "";
        
        // Create tables
        foreach ($creation_order as $table) {
            $sql[] = "-- Table structure for table `{$table}`";
            $sql[] = "DROP TABLE IF EXISTS `{$table}`;";
            $sql[] = $this->tables_info[$table]['create_statement'] . ";";
            $sql[] = "";
        }
        
        // Insert dummy data
        foreach ($creation_order as $table) {
            $sql[] = "-- Dummy data for table `{$table}`";
            $insert_statements = $this->generateInsertStatements($table);
            $sql = array_merge($sql, $insert_statements);
            $sql[] = "";
        }
        
        // Footer
        $sql[] = "SET FOREIGN_KEY_CHECKS = 1;";
        $sql[] = "";
        $sql[] = "-- Clone complete!";
        $sql[] = "-- Database: {$this->database_name}_clone";
        $sql[] = "-- Total tables: " . count($creation_order);
        $sql[] = "-- Total records: " . (count($creation_order) * $this->records_per_table);
        
        return implode("\n", $sql);
    }
    
    private function generateInsertStatements($table) {
        $statements = [];
        $columns = $this->tables_info[$table]['columns'];
        $primary_key = $this->tables_info[$table]['primary_key'];
        
        // Get column names (excluding auto-increment)
        $insert_columns = [];
        foreach ($columns as $col_name => $col_info) {
            if ($col_info['extra'] !== 'auto_increment') {
                $insert_columns[] = "`{$col_name}`";
            }
        }
        
        if (empty($insert_columns)) {
            return $statements;
        }
        
        // Generate records
        for ($i = 1; $i <= $this->records_per_table; $i++) {
            $values = [];
            
            foreach ($columns as $col_name => $col_info) {
                if ($col_info['extra'] === 'auto_increment') {
                    continue;
                }
                
                // Handle foreign keys
                $is_foreign_key = false;
                foreach ($this->foreign_keys as $fk) {
                    if ($fk['table'] === $table && $fk['column'] === $col_name) {
                        // Get a random ID from the referenced table
                        $ref_table = $fk['ref_table'];
                        if (!isset($this->generated_ids[$ref_table])) {
                            $this->generated_ids[$ref_table] = range(1, $this->records_per_table);
                        }
                        $values[] = $this->generated_ids[$ref_table][array_rand($this->generated_ids[$ref_table])];
                        $is_foreign_key = true;
                        break;
                    }
                }
                
                if (!$is_foreign_key) {
                    if ($col_info['default'] !== null && rand(0, 10) < 3) {
                        // Sometimes use default value
                        $values[] = $col_info['default'] === 'CURRENT_TIMESTAMP' ? 'NOW()' : "'{$col_info['default']}'";
                    } else {
                        $values[] = $this->generateDummyValue($col_name, $col_info, $table, $i);
                    }
                }
            }
            
            $statements[] = "INSERT INTO `{$table}` (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $values) . ");";
            
            // Store generated ID for foreign key references
            if (!empty($primary_key) && count($primary_key) === 1) {
                $pk_col = $primary_key[0];
                if ($columns[$pk_col]['extra'] === 'auto_increment') {
                    if (!isset($this->generated_ids[$table])) {
                        $this->generated_ids[$table] = [];
                    }
                    $this->generated_ids[$table][] = $i;
                }
            }
        }
        
        return $statements;
    }
    
    private function getCreateStatement($table) {
        $stmt = $this->pdo->prepare("SHOW CREATE TABLE `{$table}`");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Create Table'];
    }
    
    private function getPrimaryKey($table) {
        $stmt = $this->pdo->prepare("
            SELECT column_name
            FROM information_schema.key_column_usage
            WHERE table_schema = ? AND table_name = ? AND constraint_name = 'PRIMARY'
            ORDER BY ordinal_position
        ");
        $stmt->execute([$this->database_name, $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getTableForeignKeys($table) {
        $stmt = $this->pdo->prepare("
            SELECT 
                column_name,
                referenced_table_name,
                referenced_column_name,
                constraint_name
            FROM information_schema.key_column_usage
            WHERE table_schema = ? 
            AND table_name = ? 
            AND referenced_table_name IS NOT NULL
        ");
        $stmt->execute([$this->database_name, $table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTableIndexes($table) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT index_name, non_unique
            FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ?
            AND index_name != 'PRIMARY'
        ");
        $stmt->execute([$this->database_name, $table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Main execution
try {
    // Get records per table parameter
    $records_per_table = 25; // Default
    
    if (isset($argv[1]) && is_numeric($argv[1])) {
        $records_per_table = (int)$argv[1];
    }
    
    if (isset($_GET['records']) && is_numeric($_GET['records'])) {
        $records_per_table = (int)$_GET['records'];
    }
    
    $isBrowser = !empty($_SERVER['HTTP_HOST']);
    $nl = $isBrowser ? "<br>\n" : "\n";
    $hr = $isBrowser ? "<hr>" : str_repeat("=", 60);
    
    echo "Database Cloner with Dummy Data Generator" . $nl;
    echo $hr . $nl;
    echo $nl;
    
    echo "Source Database: " . $my_db . $nl;
    echo "Records per table: " . $records_per_table . $nl;
    echo "Output: database_clone.sql" . $nl;
    echo $nl;
    
    $cloner = new DatabaseCloner($pdo, $my_db, $records_per_table);
    $result = $cloner->generateCloneScript();
    
    echo "SUCCESS!" . $nl;
    echo $hr . $nl;
    echo "Output file: " . $result['output_file'] . $nl;
    echo "Tables cloned: " . $result['tables_count'] . $nl;
    echo "Total dummy records: " . number_format($result['total_records']) . $nl;
    echo "File size: " . number_format($result['file_size']) . " bytes" . $nl;
    echo $nl;
    
    echo "Next steps:" . $nl;
    echo "1. Review the generated SQL file" . $nl;
    echo "2. Import: mysql -u root -p < database_clone.sql" . $nl;
    echo "3. Your cloned database will be: {$my_db}_clone" . $nl;
    echo $nl;
    
    echo "Table creation order: " . implode(', ', $result['creation_order']) . $nl;
    
} catch (Exception $e) {
    $nl = !empty($_SERVER['HTTP_HOST']) ? "<br>\n" : "\n";
    echo "ERROR: " . $e->getMessage() . $nl;
}
?>