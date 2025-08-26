-- AI Dental Analysis System Database Schema
-- Standalone Version

-- Create database
CREATE DATABASE IF NOT EXISTS `ai_dental_db` 
CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `ai_dental_db`;

-- Patient table
CREATE TABLE `patient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(50) NOT NULL COMMENT 'Patient identification number',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text,
  `insurance_id` int(11) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insurance table (optional)
CREATE TABLE `insurance` (
  `insurance_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`insurance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Request table (simplified)
CREATE TABLE `request` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_detail_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Document table (for storing AI reports and other documents)
CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL COMMENT 'Document identifier within request',
  `request_detail_id` int(11) NOT NULL COMMENT 'Reference to request detail',
  `document_type` int(11) NOT NULL COMMENT '1=X-ray, 2=Other image, 3=AI Report',
  `observation` text COMMENT 'JSON data for AI reports, text for others',
  `document` varchar(255) DEFAULT NULL COMMENT 'File path for uploaded documents',
  `created_at` date DEFAULT NULL,
  `upload_by` int(11) DEFAULT NULL COMMENT 'User ID who uploaded the document',
  PRIMARY KEY (`id`),
  KEY `request_detail_id` (`request_detail_id`),
  KEY `document_type` (`document_type`),
  KEY `idx_ai_reports` (`document_type`, `request_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sample data for testing
INSERT INTO `insurance` (`name`, `status`) VALUES 
('Private Insurance', 1),
('Public Health', 1),
('No Insurance', 1);

INSERT INTO `patient` (`patient_id`, `firstname`, `lastname`, `date_of_birth`, `sex`, `mobile`, `address`) VALUES 
('P001', 'John', 'Doe', '1985-03-15', 'Male', '555-0101', '123 Main St, City'),
('P002', 'Jane', 'Smith', '1990-07-22', 'Female', '555-0102', '456 Oak Ave, Town'),
('P003', 'Mike', 'Johnson', '1978-11-08', 'Male', '555-0103', '789 Pine Rd, Village');

INSERT INTO `request` (`request_detail_id`, `patient_id`, `service_id`) VALUES 
(1, 1, 1),
(2, 2, 1),
(3, 3, 1);

-- Sample AI report (document_type = 3)
INSERT INTO `document` (`document_id`, `request_detail_id`, `document_type`, `observation`, `created_at`, `upload_by`) VALUES 
(1, 1, 3, '{"raw_response":"Sample AI analysis response","findings":"Normal dental structures observed","diagnosis":"No significant findings","treatment":"Regular follow-up recommended","technical_notes":"Good image quality","ai_model_used":"gpt-4o","analysis_timestamp":"2024-01-15 10:30:00","patient_id":1,"request_detail_id":1}', '2024-01-15', 1);

-- Create indexes for better performance
CREATE INDEX `idx_patient_search` ON `patient` (`firstname`, `lastname`, `patient_id`);
CREATE INDEX `idx_document_created` ON `document` (`created_at`);
CREATE INDEX `idx_request_patient` ON `request` (`patient_id`, `request_detail_id`);

-- Create views for easier querying
CREATE VIEW `ai_reports_view` AS
SELECT 
    d.id,
    d.document_id,
    d.request_detail_id,
    d.observation,
    d.created_at,
    p.patient_id as patient_code,
    p.firstname,
    p.lastname,
    p.date_of_birth,
    p.sex
FROM document d
LEFT JOIN request r ON d.request_detail_id = r.request_detail_id
LEFT JOIN patient p ON r.patient_id = p.id
WHERE d.document_type = 3;

-- Create stored procedure for getting AI reports by patient
DELIMITER //
CREATE PROCEDURE GetAIReportsByPatient(IN patient_id INT)
BEGIN
    SELECT 
        d.*,
        p.firstname,
        p.lastname,
        p.patient_id as patient_code
    FROM document d
    LEFT JOIN request r ON d.request_detail_id = r.request_detail_id
    LEFT JOIN patient p ON r.patient_id = p.id
    WHERE p.id = patient_id AND d.document_type = 3
    ORDER BY d.created_at DESC;
END //
DELIMITER ;

-- Create stored procedure for getting patient with AI reports count
DELIMITER //
CREATE PROCEDURE GetPatientsWithAIReports()
BEGIN
    SELECT 
        p.*,
        COUNT(d.id) as ai_reports_count,
        MAX(d.created_at) as last_ai_report_date
    FROM patient p
    LEFT JOIN request r ON p.id = r.patient_id
    LEFT JOIN document d ON r.request_detail_id = d.request_detail_id AND d.document_type = 3
    GROUP BY p.id
    ORDER BY p.firstname, p.lastname;
END //
DELIMITER ;
