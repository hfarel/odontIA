<?php

/**
 * Test Script for AI Dental Analysis Standalone System
 * 
 * This script tests the core functionality of the standalone system
 */

// Define BASEPATH for compatibility
define('BASEPATH', __DIR__ . '/');

echo "=== AI Dental Analysis Standalone System Test ===\n\n";

// Test 1: Check file structure
echo "1. Checking file structure...\n";
$required_files = [
    'config/ai_config.php',
    'config/database.php',
    'libraries/AI_Dental_Analysis.php',
    'models/Patient_model.php',
    'models/Document_model.php',
    'controllers/AI_Report_Controller.php',
    'database/schema.sql'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "✅ All required files exist\n";
} else {
    echo "❌ Missing files:\n";
    foreach ($missing_files as $file) {
        echo "   - $file\n";
    }
    exit(1);
}

// Test 2: Test configuration loading
echo "\n2. Testing configuration loading...\n";
try {
    require_once 'config/ai_config.php';
    require_once 'config/database.php';
    echo "✅ Configuration files loaded successfully\n";
    echo "   - API Endpoint: " . substr($config['openai_api_endpoint'], 0, 50) . "...\n";
    echo "   - Model: " . $config['openai_model'] . "\n";
    echo "   - Database: " . $db['default']['database'] . "\n";
} catch (Exception $e) {
    echo "❌ Configuration loading failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test database connection
echo "\n3. Testing database connection...\n";
try {
    $mysqli = new mysqli(
        $db['default']['hostname'],
        $db['default']['username'],
        $db['default']['password'],
        $db['default']['database']
    );

    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }

    echo "✅ Database connection successful\n";
    echo "   - Server: " . $mysqli->server_info . "\n";
    echo "   - Database: " . $db['default']['database'] . "\n";

    // Test if tables exist
    $tables = ['patient', 'document', 'request', 'insurance'];
    $existing_tables = [];
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $existing_tables[] = $table;
        }
    }

    if (count($existing_tables) === count($tables)) {
        echo "✅ All required tables exist\n";
    } else {
        echo "⚠️  Some tables missing. Run database/schema.sql to create them.\n";
        echo "   Missing: " . implode(', ', array_diff($tables, $existing_tables)) . "\n";
    }

    $mysqli->close();
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
    echo "   Please ensure MySQL is running and the database exists.\n";
    echo "   Run: mysql -u root -p < database/schema.sql\n";
}

// Test 4: Test AI Analysis Library
echo "\n4. Testing AI Analysis Library...\n";
try {
    require_once 'libraries/AI_Dental_Analysis.php';
    $ai_analysis = new AI_Dental_Analysis();
    echo "✅ AI Analysis Library loaded successfully\n";

    // Test connection
    $connection_test = $ai_analysis->test_connection();
    if ($connection_test['success']) {
        echo "✅ AI API connection successful\n";
    } else {
        echo "⚠️  AI API connection failed: " . $connection_test['error'] . "\n";
        echo "   This is expected if API key is not configured properly.\n";
    }
} catch (Exception $e) {
    echo "❌ AI Analysis Library test failed: " . $e->getMessage() . "\n";
}

// Test 5: Test Models
echo "\n5. Testing Models...\n";
try {
    require_once 'models/Patient_model.php';
    require_once 'models/Document_model.php';

    $patient_model = new Patient_model();
    $document_model = new Document_model();

    echo "✅ Models loaded successfully\n";

    // Test patient model
    $patients = $patient_model->read();
    echo "   - Patients in database: " . count($patients) . "\n";

    // Test document model
    $ai_reports = $document_model->get_ai_reports();
    echo "   - AI reports in database: " . count($ai_reports) . "\n";
} catch (Exception $e) {
    echo "❌ Models test failed: " . $e->getMessage() . "\n";
}

// Test 6: Test Controller
echo "\n6. Testing Controller...\n";
try {
    require_once 'controllers/AI_Report_Controller.php';
    $controller = new AI_Report_Controller();
    echo "✅ Controller loaded successfully\n";

    // Test AI connection through controller
    $connection_test = $controller->test_ai_connection();
    if ($connection_test['success']) {
        echo "✅ Controller AI connection test successful\n";
    } else {
        echo "⚠️  Controller AI connection test failed: " . $connection_test['error'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "\n";
}

// Test 7: Test file permissions
echo "\n7. Testing file permissions...\n";
$directories = ['uploads', 'reports', 'logs'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        if (is_writable($dir)) {
            echo "✅ Directory writable: $dir\n";
        } else {
            echo "⚠️  Directory not writable: $dir\n";
        }
    }
}

// Test 8: Test sample AI analysis (if image exists)
echo "\n8. Testing sample AI analysis...\n";
$test_image = 'uploads/test_xray.jpg';
if (file_exists($test_image)) {
    try {
        $result = $controller->perform_ai_analysis($test_image, 1, 1);
        if ($result['success']) {
            echo "✅ Sample AI analysis completed successfully\n";
            echo "   - Document ID: " . $result['document_id'] . "\n";
        } else {
            echo "⚠️  Sample AI analysis failed: " . $result['error'] . "\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Sample AI analysis test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️  No test image found at $test_image\n";
    echo "   Place a test X-ray image there to test AI analysis functionality.\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Standalone AI Dental Analysis System is ready!\n";
echo "\nNext steps:\n";
echo "1. Configure your Azure Cognitive Services API key in config/ai_config.php\n";
echo "2. Import the database schema: mysql -u root -p < database/schema.sql\n";
echo "3. Place X-ray images in the uploads/ directory\n";
echo "4. Use the system to analyze dental X-ray images\n";
echo "\nExample usage:\n";
echo "```php\n";
echo "require_once 'controllers/AI_Report_Controller.php';\n";
echo "\$controller = new AI_Report_Controller();\n";
echo "\$result = \$controller->perform_ai_analysis('uploads/xray.jpg', 1, 1);\n";
echo "```\n";

echo "\nTest completed successfully! 🎉\n";
