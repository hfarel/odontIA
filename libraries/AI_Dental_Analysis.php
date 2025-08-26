<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AI Dental Analysis Library - Standalone Version
 * 
 * This library provides AI-powered analysis of dental X-ray images
 * to automatically generate professional odontological reports.
 * 
 * @author CEDIR Digital
 * @version 1.0
 */
class AI_Dental_Analysis
{
    private $api_key;
    private $api_endpoint;
    private $model_version;
    private $db;
    private $config;

    public function __construct()
    {
        // Load configuration
        $this->load_config();

        // Initialize database connection
        $this->init_database();

        // Log configuration for debugging
        $this->log_message('debug', 'AI Dental Analysis - API Key: ' . substr($this->api_key, 0, 10) . '...');
        $this->log_message('debug', 'AI Dental Analysis - API Endpoint: ' . $this->api_endpoint);
        $this->log_message('debug', 'AI Dental Analysis - Model Version: ' . $this->model_version);
    }

    /**
     * Load configuration from file
     */
    private function load_config()
    {
        $config_file = __DIR__ . '/../config/ai_config.php';
        if (file_exists($config_file)) {
            include $config_file;
            $this->api_key = $config['openai_api_key'];
            $this->api_endpoint = $config['openai_api_endpoint'];
            $this->model_version = $config['openai_model'];
            $this->config = $config;
        } else {
            throw new Exception('AI configuration file not found');
        }
    }

    /**
     * Initialize database connection
     */
    private function init_database()
    {
        $db_config_file = __DIR__ . '/../config/database.php';
        if (file_exists($db_config_file)) {
            include $db_config_file;
            $this->db = new mysqli(
                $db['default']['hostname'],
                $db['default']['username'],
                $db['default']['password'],
                $db['default']['database']
            );

            if ($this->db->connect_error) {
                throw new Exception('Database connection failed: ' . $this->db->connect_error);
            }

            $this->db->set_charset($db['default']['char_set']);
        } else {
            throw new Exception('Database configuration file not found');
        }
    }

    /**
     * Analyze dental X-ray image and generate report
     */
    public function analyze_dental_xray($image_path, $patient_id, $request_detail_id)
    {
        try {
            $this->log_message('debug', 'AI Dental Analysis - Starting analysis for patient: ' . $patient_id);

            // Validate image file
            if (!$this->_validate_image($image_path)) {
                throw new Exception('Invalid image file or format');
            }

            // Prepare image for AI analysis
            $image_data = $this->_prepare_image_for_analysis($image_path);

            // Get patient context
            $patient_data = $this->_get_patient_context($patient_id);

            // Perform AI analysis
            $analysis_result = $this->_perform_ai_analysis($image_data, $patient_data);

            // Generate structured report
            $report = $this->_generate_structured_report($analysis_result, $patient_data);

            // Store report in database
            $document_id = $this->_store_ai_report($report, $request_detail_id, $patient_id);

            return array(
                'success' => true,
                'document_id' => $document_id,
                'report' => $report,
                'message' => 'AI analysis completed successfully'
            );
        } catch (Exception $e) {
            $this->log_message('error', 'AI Dental Analysis Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Validate uploaded image
     */
    private function _validate_image($image_path)
    {
        if (!file_exists($image_path)) {
            return false;
        }

        $allowed_types = $this->config['ai_supported_image_types'];
        $file_extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            return false;
        }

        $image_info = getimagesize($image_path);
        return $image_info !== false;
    }

    /**
     * Prepare image for AI analysis
     */
    private function _prepare_image_for_analysis($image_path)
    {
        $image_data = base64_encode(file_get_contents($image_path));
        $image_info = getimagesize($image_path);

        return array(
            'base64_data' => $image_data,
            'mime_type' => $image_info['mime'],
            'width' => $image_info[0],
            'height' => $image_info[1],
            'file_size' => filesize($image_path)
        );
    }

    /**
     * Get patient context for analysis
     */
    private function _get_patient_context($patient_id)
    {
        $stmt = $this->db->prepare("SELECT id, patient_id, firstname, lastname, date_of_birth, sex FROM patient WHERE id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        return array(
            'patient_id' => $patient['id'],
            'name' => $patient['firstname'] . ' ' . $patient['lastname'],
            'age' => $this->_calculate_age($patient['date_of_birth']),
            'gender' => $patient['sex']
        );
    }

    /**
     * Calculate patient age
     */
    private function _calculate_age($birth_date)
    {
        if (empty($birth_date)) {
            return 'Unknown';
        }

        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);

        return $age->y;
    }

    /**
     * Perform AI analysis using Azure Cognitive Services
     */
    private function _perform_ai_analysis($image_data, $patient_data)
    {
        $prompt = $this->_build_ai_prompt($patient_data);

        $request_data = array(
            'model' => $this->model_version,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => 'data:' . $image_data['mime_type'] . ';base64,' . $image_data['base64_data']
                            )
                        )
                    )
                )
            ),
            'max_tokens' => $this->config['ai_max_tokens'],
            'temperature' => $this->config['ai_temperature']
        );

        $response = $this->_make_api_request($request_data);

        if (!$response['success']) {
            throw new Exception('AI API request failed: ' . $response['error']);
        }

        return $this->_parse_ai_response($response['data']);
    }

    /**
     * Build AI prompt for dental analysis
     */
    private function _build_ai_prompt($patient_data)
    {
        return "You are a PhD-level odontologist with 20+ years of experience in dental radiology. 
        
        Please analyze this dental X-ray image and provide a comprehensive odontological report following this structure:
        
        PATIENT INFORMATION:
        - Patient ID: {$patient_data['patient_id']}
        - Name: {$patient_data['name']}
        - Age: {$patient_data['age']}
        - Gender: {$patient_data['gender']}
        
        RADIOGRAPHIC FINDINGS:
        - Image quality assessment
        - Anatomical structures identification
        - Pathological findings
        - Dental conditions observed
        
        DIAGNOSIS:
        - Primary diagnosis
        - Secondary findings
        - Differential diagnosis if applicable
        
        TREATMENT RECOMMENDATIONS:
        - Immediate actions required
        - Treatment plan
        - Follow-up recommendations
        
        TECHNICAL NOTES:
        - Radiographic technique assessment
        - Image quality notes
        - Additional imaging recommendations if needed
        
        Please provide the report in professional odontological language suitable for medical records.";
    }

    /**
     * Make API request to Azure Cognitive Services
     */
    private function _make_api_request($request_data)
    {
        $endpoint = $this->_construct_azure_endpoint();

        $headers = array(
            'Content-Type: application/json',
            'api-key: ' . $this->api_key
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->config['ai_timeout'],
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return array('success' => false, 'error' => 'cURL Error: ' . $error);
        }

        if ($http_code !== 200) {
            return array('success' => false, 'error' => 'HTTP Error: ' . $http_code . ' - ' . $response);
        }

        $response_data = json_decode($response, true);

        if (!$response_data) {
            return array('success' => false, 'error' => 'Invalid JSON response');
        }

        return array('success' => true, 'data' => $response_data);
    }

    /**
     * Construct proper Azure Cognitive Services endpoint
     */
    private function _construct_azure_endpoint()
    {
        $base_url = rtrim($this->api_endpoint, '/');
        $deployment_name = $this->config['azure_deployment_name'];
        $api_version = $this->config['azure_api_version'];

        return $base_url . '/openai/deployments/' . $deployment_name . '/chat/completions?api-version=' . $api_version;
    }

    /**
     * Parse AI response
     */
    private function _parse_ai_response($response_data)
    {
        if (!isset($response_data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid AI response format');
        }

        $content = $response_data['choices'][0]['message']['content'];

        return array(
            'raw_response' => $content,
            'findings' => $this->_extract_section($content, 'RADIOGRAPHIC FINDINGS'),
            'diagnosis' => $this->_extract_section($content, 'DIAGNOSIS'),
            'treatment' => $this->_extract_section($content, 'TREATMENT RECOMMENDATIONS'),
            'technical_notes' => $this->_extract_section($content, 'TECHNICAL NOTES'),
            'analysis_timestamp' => date('Y-m-d H:i:s'),
            'ai_model_used' => $this->model_version
        );
    }

    /**
     * Extract section from AI response
     */
    private function _extract_section($content, $section_name)
    {
        $pattern = '/(' . preg_quote($section_name) . '):(.*?)(?=\n[A-Z\s]+:|$)/s';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[2]);
        }
        return '';
    }

    /**
     * Generate structured report
     */
    private function _generate_structured_report($analysis_result, $patient_data)
    {
        return array(
            'report_type' => 'AI_ODONTOLOGICAL_ANALYSIS',
            'patient_id' => $patient_data['patient_id'],
            'patient_name' => $patient_data['name'],
            'analysis_date' => date('Y-m-d'),
            'analysis_time' => date('H:i:s'),
            'ai_model_version' => $analysis_result['ai_model_used'],
            'report_sections' => array(
                'detailed_findings' => $analysis_result['findings'],
                'clinical_diagnosis' => $analysis_result['diagnosis'],
                'treatment_plan' => $analysis_result['treatment'],
                'technical_assessment' => $analysis_result['technical_notes']
            ),
            'raw_response' => $analysis_result['raw_response']
        );
    }

    /**
     * Store AI report in database
     */
    private function _store_ai_report($report, $request_detail_id, $patient_id)
    {
        $storage_data = array(
            'raw_response' => $report['raw_response'],
            'findings' => $report['report_sections']['detailed_findings'],
            'diagnosis' => $report['report_sections']['clinical_diagnosis'],
            'treatment' => $report['report_sections']['treatment_plan'],
            'technical_notes' => $report['report_sections']['technical_assessment'],
            'ai_model_used' => $report['ai_model_version'],
            'analysis_timestamp' => date('Y-m-d H:i:s'),
            'patient_id' => $patient_id,
            'request_detail_id' => $request_detail_id
        );

        $document_id = $this->_get_next_document_id($request_detail_id, 3);

        $stmt = $this->db->prepare("INSERT INTO document (document_id, request_detail_id, document_type, observation, created_at) VALUES (?, ?, ?, ?, ?)");
        $observation = json_encode($storage_data);
        $created_at = date('Y-m-d');
        $document_type = 3;

        $stmt->bind_param("iiiss", $document_id, $request_detail_id, $document_type, $observation, $created_at);
        $stmt->execute();
        $stmt->close();

        return $document_id;
    }

    /**
     * Get next document ID for a specific type
     */
    private function _get_next_document_id($request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("SELECT MAX(document_id) as max_id FROM document WHERE request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("ii", $request_detail_id, $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return ($row && $row['max_id']) ? ($row['max_id'] + 1) : 1;
    }

    /**
     * Simple logging function
     */
    private function log_message($level, $message)
    {
        if ($this->config['ai_debug'] || $level === 'error') {
            $log_file = __DIR__ . '/../logs/ai_analysis.log';
            $log_dir = dirname($log_file);

            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }

            $timestamp = date('Y-m-d H:i:s');
            $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Test AI service connection
     */
    public function test_connection()
    {
        try {
            $data = array(
                'model' => $this->model_version,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello, this is a test message. Please respond with "Connection successful" if you can read this.'
                    )
                ),
                'max_tokens' => 50,
                'temperature' => 0.3
            );

            $response = $this->_make_api_request($data);

            return array(
                'success' => $response['success'],
                'message' => $response['success'] ? 'Connection successful' : $response['error']
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
}
