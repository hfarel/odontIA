# odontIA - AI Dental Analysis Standalone System

## Overview
El presente proyecto incorpora capacidades avanzadas de análisis de inteligencia artificial para imágenes de rayos X odontológicos en un sistema integral de gestión de clínica dental. This is a standalone version of the AI Dental Analysis system extracted from the CEDIR dental clinic management system.

## Features
- **AI-Powered X-ray Analysis**: Uses Azure Cognitive Services with GPT-4o model
- **Professional Report Generation**: Creates structured odontological reports
- **Patient Context Integration**: Incorporates patient information in analysis
- **Database Storage**: Stores analysis results with full audit trail
- **PDF Report Generation**: Creates downloadable PDF reports
- **HIPAA Compliant**: Designed for medical data security

## System Requirements
- PHP 7.4 or higher
- MySQL/MariaDB database
- cURL extension enabled
- JSON extension enabled
- File upload capabilities

## Installation

### 1. Database Setup
```sql
-- Import the complete database schema
mysql -u root -p < database/schema.sql
```

### 2. Configuration
1. Update `config/ai_config.php` with your Azure Cognitive Services settings:
   - API Key
   - Endpoint URL
   - Deployment name
   - API version

### 3. File Permissions
Ensure the following directories are writable:
- `uploads/` (for X-ray images)
- `reports/` (for generated PDFs)
- `logs/` (for system logs)

## Usage

### Basic AI Analysis
```php
require_once 'controllers/AI_Report_Controller.php';

$controller = new AI_Report_Controller();
$result = $controller->perform_ai_analysis(
    'uploads/xray_image.jpg',
    $patient_id,
    $request_detail_id
);

if ($result['success']) {
    echo "Analysis completed. Document ID: " . $result['document_id'];
} else {
    echo "Error: " . $result['error'];
}
```

### View AI Report
```php
$report_data = $controller->view_ai_report($document_id);
```

### Download PDF Report
```php
$pdf_result = $controller->download_ai_report($document_id);
```

## API Configuration

### Azure Cognitive Services
The system is configured to work with Azure Cognitive Services:

```php
// Configuration in ai_config.php
$config['openai_api_key'] = 'your-azure-api-key';
$config['openai_api_endpoint'] = 'https://your-resource.cognitiveservices.azure.com/';
$config['openai_model'] = 'gpt-4o';
$config['azure_deployment_name'] = 'gpt-4o';
$config['azure_api_version'] = '2024-12-01-preview';
```

## File Structure
```
ai_dental_standalone/
├── config/
│   ├── ai_config.php
│   └── database.php
├── controllers/
│   └── AI_Report_Controller.php
├── libraries/
│   └── AI_Dental_Analysis.php
├── models/
│   ├── Patient_model.php
│   └── Document_model.php
├── views/
│   ├── ai_report.php
│   └── ai_report_pdf.php
├── database/
│   └── schema.sql
├── uploads/
├── reports/
├── logs/
├── test_standalone.php
├── example_usage.php
├── ARCHITECTURE.md
└── README.md
```

## Testing

### Run System Test
```bash
php test_standalone.php
```

### Run Example Usage
```bash
php example_usage.php
```

## Security Considerations
- API keys are stored in configuration files (should be secured in production)
- All database queries use prepared statements
- File uploads are validated for type and size
- HIPAA compliance measures implemented

## Troubleshooting

### Common Issues
1. **401 Unauthorized**: Check API key and endpoint configuration
2. **Image Upload Failures**: Verify file permissions and upload limits
3. **Database Connection**: Ensure database credentials are correct
4. **Memory Issues**: Increase PHP memory limit for large image processing

### Debug Mode
Enable debug logging by setting:
```php
$config['ai_debug'] = TRUE;
```

## License
This system is part of the CEDIR dental clinic management system.

## Support
For technical support, refer to the main CEDIR documentation or contact the development team.
