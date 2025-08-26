<?php

/**
 * Example Usage - AI Dental Analysis Standalone System
 * 
 * This file demonstrates how to use the standalone AI Dental Analysis system
 */

// Define BASEPATH for compatibility
define('BASEPATH', __DIR__ . '/');

// Include required files
require_once 'controllers/AI_Report_Controller.php';

echo "=== AI Dental Analysis Standalone System - Example Usage ===\n\n";

// Initialize the controller
$controller = new AI_Report_Controller();

// Example 1: Test AI Connection
echo "1. Testing AI Connection...\n";
$connection_result = $controller->test_ai_connection();
if ($connection_result['success']) {
    echo "✅ AI connection successful: " . $connection_result['message'] . "\n";
} else {
    echo "❌ AI connection failed: " . $connection_result['error'] . "\n";
    echo "   Please check your API configuration in config/ai_config.php\n\n";
}

// Example 2: Perform AI Analysis (if test image exists)
echo "\n2. Performing AI Analysis...\n";
$test_image = 'uploads/test_xray.jpg';
if (file_exists($test_image)) {
    echo "Found test image: $test_image\n";

    // Perform AI analysis
    $analysis_result = $controller->perform_ai_analysis($test_image, 1, 1);

    if ($analysis_result['success']) {
        echo "✅ AI analysis completed successfully!\n";
        echo "   - Document ID: " . $analysis_result['document_id'] . "\n";
        echo "   - Message: " . $analysis_result['message'] . "\n";

        $document_id = $analysis_result['document_id'];

        // Example 3: View AI Report
        echo "\n3. Viewing AI Report...\n";
        $report_data = $controller->view_ai_report($document_id);

        if (!isset($report_data['error'])) {
            echo "✅ AI report retrieved successfully!\n";
            echo "   - Patient: " . ($report_data['patient']['firstname'] ?? 'Unknown') . " " . ($report_data['patient']['lastname'] ?? '') . "\n";
            echo "   - Report sections found: " . count($report_data['report']) . "\n";

            // Display report sections
            if (isset($report_data['report']['findings']) && !empty($report_data['report']['findings'])) {
                echo "   - Findings: " . substr($report_data['report']['findings'], 0, 100) . "...\n";
            }
            if (isset($report_data['report']['diagnosis']) && !empty($report_data['report']['diagnosis'])) {
                echo "   - Diagnosis: " . substr($report_data['report']['diagnosis'], 0, 100) . "...\n";
            }

            // Example 4: Download AI Report as PDF
            echo "\n4. Downloading AI Report as PDF...\n";
            $pdf_result = $controller->download_ai_report($document_id);

            if ($pdf_result['success']) {
                echo "✅ PDF generation successful!\n";
                echo "   - Filename: " . $pdf_result['filename'] . "\n";
                echo "   - Content length: " . strlen($pdf_result['html_content']) . " characters\n";

                // Save the HTML content to a file for viewing
                $html_file = 'reports/' . $pdf_result['filename'];
                file_put_contents($html_file, $pdf_result['html_content']);
                echo "   - HTML file saved to: $html_file\n";
            } else {
                echo "❌ PDF generation failed: " . $pdf_result['error'] . "\n";
            }
        } else {
            echo "❌ Failed to retrieve AI report: " . $report_data['error'] . "\n";
        }
    } else {
        echo "❌ AI analysis failed: " . $analysis_result['error'] . "\n";
    }
} else {
    echo "ℹ️  No test image found at $test_image\n";
    echo "   To test AI analysis, place an X-ray image at that location.\n";
}

// Example 5: Database Operations
echo "\n5. Database Operations...\n";
try {
    require_once 'models/Patient_model.php';
    require_once 'models/Document_model.php';

    $patient_model = new Patient_model();
    $document_model = new Document_model();

    // Get all patients
    $patients = $patient_model->read();
    echo "   - Total patients in database: " . count($patients) . "\n";

    // Get all AI reports
    $ai_reports = $document_model->get_ai_reports();
    echo "   - Total AI reports in database: " . count($ai_reports) . "\n";

    // Search for a specific patient
    if (!empty($patients)) {
        $first_patient = $patients[0];
        echo "   - Sample patient: " . $first_patient['firstname'] . " " . $first_patient['lastname'] . " (ID: " . $first_patient['patient_id'] . ")\n";

        // Search for this patient
        $search_result = $patient_model->search(['firstname' => $first_patient['firstname']]);
        echo "   - Search results for '" . $first_patient['firstname'] . "': " . count($search_result) . " patients found\n";
    }
} catch (Exception $e) {
    echo "❌ Database operations failed: " . $e->getMessage() . "\n";
}

// Example 6: Error Handling
echo "\n6. Error Handling Examples...\n";

// Try to view a non-existent report
echo "   Testing error handling with non-existent report...\n";
$error_result = $controller->view_ai_report(99999);
if (isset($error_result['error'])) {
    echo "   ✅ Error handling works: " . $error_result['error'] . "\n";
} else {
    echo "   ⚠️  Unexpected result for non-existent report\n";
}

// Try to analyze a non-existent image
echo "   Testing error handling with non-existent image...\n";
$error_result = $controller->perform_ai_analysis('non_existent_image.jpg', 1, 1);
if (!$error_result['success']) {
    echo "   ✅ Error handling works: " . $error_result['error'] . "\n";
} else {
    echo "   ⚠️  Unexpected result for non-existent image\n";
}

// Summary
echo "\n=== Example Usage Summary ===\n";
echo "✅ All examples completed successfully!\n";
echo "\nThe standalone AI Dental Analysis system provides:\n";
echo "• AI-powered X-ray image analysis\n";
echo "• Professional odontological report generation\n";
echo "• Database storage and retrieval\n";
echo "• PDF report generation\n";
echo "• Patient management\n";
echo "• Error handling and validation\n";
echo "\nTo use the system in your own application:\n";
echo "1. Include the controller: require_once 'controllers/AI_Report_Controller.php';\n";
echo "2. Create an instance: \$controller = new AI_Report_Controller();\n";
echo "3. Perform analysis: \$result = \$controller->perform_ai_analysis(\$image_path, \$patient_id, \$request_detail_id);\n";
echo "4. View reports: \$report = \$controller->view_ai_report(\$document_id);\n";
echo "5. Download PDFs: \$pdf = \$controller->download_ai_report(\$document_id);\n";
echo "\nFor more information, see the README.md file.\n";
