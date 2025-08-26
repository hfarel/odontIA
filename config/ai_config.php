<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| AI Configuration
|--------------------------------------------------------------------------
|
| Configuration settings for AI-powered dental analysis system
|
*/

// Azure Cognitive Services Configuration
$config['openai_api_key'] = '';
$config['openai_api_endpoint'] = 'https://farel-meom6r3c-eastus2.cognitiveservices.azure.com/'; // Azure Cognitive Services endpoint
$config['openai_model'] = 'gpt-4o'; // Vision-capable model for X-ray analysis

// Azure OpenAI Configuration
$config['azure_deployment_name'] = 'gpt-4o'; // The deployment name in Azure OpenAI
$config['azure_api_version'] = '2024-12-01-preview'; // Azure OpenAI API version

// AI Analysis Settings
$config['ai_max_tokens'] = 2000;
$config['ai_temperature'] = 0.3;
$config['ai_timeout'] = 60;

// Document Types
$config['document_type_ai_report'] = 3; // New document type for AI-generated reports

// File Upload Settings
$config['ai_supported_image_types'] = array('jpg', 'jpeg', 'png', 'bmp', 'gif');
$config['ai_max_image_size'] = 10485760; // 10MB in bytes

// Report Templates
$config['ai_report_template'] = 'odontological_standard';
$config['ai_confidence_levels'] = array('Low', 'Medium', 'High', 'Professional Grade');

// Audit and Compliance
$config['ai_audit_enabled'] = TRUE;
$config['ai_hipaa_compliant'] = TRUE;
$config['ai_data_retention_days'] = 2555; // 7 years for medical records

// Debug Settings
$config['ai_debug'] = FALSE; // Set to TRUE for detailed logging
$config['ai_log_level'] = 'info'; // debug, info, warning, error

// File Paths
$config['ai_upload_path'] = 'uploads/';
$config['ai_report_path'] = 'reports/';
$config['ai_temp_path'] = 'temp/';

// Database Settings
$config['ai_db_prefix'] = ''; // Database table prefix if any
