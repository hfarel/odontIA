<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AI Report Controller - Standalone Version
 * 
 * Handles AI report viewing and management
 */
class AI_Report_Controller
{
    private $ai_analysis;
    private $document_model;
    private $patient_model;

    public function __construct()
    {
        require_once __DIR__ . '/../libraries/AI_Dental_Analysis.php';
        require_once __DIR__ . '/../models/Document_model.php';
        require_once __DIR__ . '/../models/Patient_model.php';

        $this->ai_analysis = new AI_Dental_Analysis();
        $this->document_model = new Document_model();
        $this->patient_model = new Patient_model();
    }

    /**
     * View AI report
     */
    public function view_ai_report($document_id)
    {
        try {
            $report_data = $this->_get_ai_report($document_id);

            if (!$report_data) {
                throw new Exception('AI report not found');
            }

            $data = array(
                'report' => $report_data,
                'patient' => null,
                'original_image' => null,
                'original_form_url' => null
            );

            // Get patient information
            $patient_id = isset($report_data['patient_id']) ? $report_data['patient_id'] : null;
            if ($patient_id) {
                $data['patient'] = $this->patient_model->read_by_id($patient_id);
            }

            // Get original X-ray image
            $request_detail_id = isset($report_data['request_detail_id']) ? $report_data['request_detail_id'] : null;
            if ($request_detail_id) {
                $data['original_image'] = $this->_get_original_xray_image($request_detail_id);
                $data['original_form_url'] = $this->_get_original_document_form_url($request_detail_id);
            }

            return $data;
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage(),
                'report' => null,
                'patient' => null,
                'original_image' => null,
                'original_form_url' => null
            );
        }
    }

    /**
     * Download AI report as PDF
     */
    public function download_ai_report($document_id)
    {
        try {
            $report_data = $this->_get_ai_report($document_id);

            if (!$report_data) {
                throw new Exception('AI report not found');
            }

            $data = array(
                'report' => $report_data,
                'patient' => null,
                'original_image' => null,
                'is_pdf' => true
            );

            // Get patient information
            $patient_id = isset($report_data['patient_id']) ? $report_data['patient_id'] : null;
            if ($patient_id) {
                $data['patient'] = $this->patient_model->read_by_id($patient_id);
            }

            // Get original X-ray image
            $request_detail_id = isset($report_data['request_detail_id']) ? $report_data['request_detail_id'] : null;
            if ($request_detail_id) {
                $data['original_image'] = $this->_get_original_xray_image($request_detail_id);
            }

            return $this->_generate_pdf($data);
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Perform AI analysis
     */
    public function perform_ai_analysis($image_path, $patient_id, $request_detail_id)
    {
        try {
            $result = $this->ai_analysis->analyze_dental_xray($image_path, $patient_id, $request_detail_id);

            return $result;
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Test AI connection
     */
    public function test_ai_connection()
    {
        try {
            $result = $this->ai_analysis->test_connection();
            return $result;
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get AI report data
     */
    private function _get_ai_report($document_id)
    {
        $document = $this->document_model->get_ai_report_by_id($document_id);

        if (!$document) {
            return null;
        }

        $observation = json_decode($document['observation'], true);

        if (!$observation || !is_array($observation)) {
            // Try to parse as plain text
            $observation = $this->_parse_ai_response_sections($document['observation']);
        }

        return array_merge($document, $observation);
    }

    /**
     * Parse AI response sections from plain text
     */
    private function _parse_ai_response_sections($content)
    {
        $sections = array();
        $section_patterns = array(
            'findings' => array('RADIOGRAPHIC FINDINGS', 'FINDINGS', 'RADIOGRAPHIC ANALYSIS', 'IMAGE ANALYSIS'),
            'diagnosis' => array('DIAGNOSIS', 'CLINICAL DIAGNOSIS', 'DIAGNOSTIC IMPRESSION'),
            'treatment' => array('TREATMENT RECOMMENDATIONS', 'TREATMENT PLAN', 'RECOMMENDATIONS', 'TREATMENT'),
            'technical_notes' => array('TECHNICAL NOTES', 'TECHNICAL ASSESSMENT', 'IMAGE QUALITY', 'TECHNICAL')
        );

        foreach ($section_patterns as $section_key => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('/' . preg_quote($pattern, '/') . ':(.*?)(?=' . implode('|', array_merge(...array_values($section_patterns))) . ':|$)/is', $content, $matches)) {
                    $sections[$section_key] = trim($matches[1]);
                    break;
                }
            }
        }

        return $sections;
    }

    /**
     * Get original X-ray image
     */
    private function _get_original_xray_image($request_detail_id)
    {
        try {
            // First try to get original X-ray (type 1)
            $original_document = $this->document_model->read_doc_by_detail_id($request_detail_id, 1);

            if (!$original_document || empty($original_document['document'])) {
                // Try to get any image document (type 1 or 2)
                $documents = $this->document_model->doc_by_detail_id($request_detail_id, 1);
                if (empty($documents)) {
                    $documents = $this->document_model->doc_by_detail_id($request_detail_id, 2);
                }

                foreach ($documents as $doc) {
                    if (!empty($doc['document'])) {
                        return $doc['document'];
                    }
                }
            } else {
                return $original_document['document'];
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get original document form URL
     */
    private function _get_original_document_form_url($request_detail_id)
    {
        // In standalone version, return a simple URL structure
        return "document/form/{$request_detail_id}";
    }

    /**
     * Generate PDF report
     */
    private function _generate_pdf($data)
    {
        try {
            // Simple PDF generation using HTML to PDF conversion
            $html_content = $this->_generate_pdf_html($data);

            // For standalone version, we'll return the HTML content
            // In a full implementation, you would use a PDF library like TCPDF or DOMPDF
            return array(
                'success' => true,
                'html_content' => $html_content,
                'filename' => 'ai_report_' . date('Y-m-d_H-i-s') . '.html'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Generate PDF HTML content
     */
    private function _generate_pdf_html($data)
    {
        $report = $data['report'];
        $patient = $data['patient'];
        $original_image = $data['original_image'];

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AI Dental Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #2c3e50; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; }
        .patient-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .image-container { text-align: center; margin: 20px 0; }
        .image-container img { max-width: 100%; max-height: 400px; border: 1px solid #ddd; }
        .report-content { line-height: 1.6; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Dental Analysis Report</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </div>';

        if ($patient) {
            $html .= '
    <div class="section">
        <h3>Patient Information</h3>
        <div class="patient-info">
            <p><strong>Name:</strong> ' . htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']) . '</p>
            <p><strong>Patient ID:</strong> ' . htmlspecialchars($patient['patient_id']) . '</p>
            <p><strong>Date of Birth:</strong> ' . htmlspecialchars($patient['date_of_birth']) . '</p>
            <p><strong>Gender:</strong> ' . htmlspecialchars($patient['sex']) . '</p>
        </div>
    </div>';
        }

        if ($original_image) {
            $html .= '
    <div class="section">
        <h3>Original X-ray Image</h3>
        <div class="image-container">
            <img src="' . htmlspecialchars($original_image) . '" alt="X-ray Image">
        </div>
    </div>';
        }

        $html .= '
    <div class="section">
        <h3>AI Analysis Report</h3>
        <div class="report-content">';

        if (isset($report['findings']) && !empty($report['findings'])) {
            $html .= '
            <h4>Radiographic Findings</h4>
            <p>' . nl2br(htmlspecialchars($report['findings'])) . '</p>';
        }

        if (isset($report['diagnosis']) && !empty($report['diagnosis'])) {
            $html .= '
            <h4>Diagnosis</h4>
            <p>' . nl2br(htmlspecialchars($report['diagnosis'])) . '</p>';
        }

        if (isset($report['treatment']) && !empty($report['treatment'])) {
            $html .= '
            <h4>Treatment Recommendations</h4>
            <p>' . nl2br(htmlspecialchars($report['treatment'])) . '</p>';
        }

        if (isset($report['technical_notes']) && !empty($report['technical_notes'])) {
            $html .= '
            <h4>Technical Notes</h4>
            <p>' . nl2br(htmlspecialchars($report['technical_notes'])) . '</p>';
        }

        if (isset($report['raw_response']) && !empty($report['raw_response'])) {
            $html .= '
            <h4>Complete Analysis</h4>
            <p>' . nl2br(htmlspecialchars($report['raw_response'])) . '</p>';
        }

        $html .= '
        </div>
    </div>
    
    <div class="footer">
        <p>This report was generated using AI analysis and should be reviewed by a qualified dental professional.</p>
        <p>AI Model: ' . (isset($report['ai_model_used']) ? htmlspecialchars($report['ai_model_used']) : 'Unknown') . '</p>
    </div>
</body>
</html>';

        return $html;
    }
}
