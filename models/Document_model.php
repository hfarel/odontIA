<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Document_model
{
    private $db;
    private $table = "document";

    public function __construct()
    {
        $this->init_database();
    }

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

    public function create($data = [])
    {
        $stmt = $this->db->prepare("INSERT INTO document (document_id, request_detail_id, document_type, observation, document, created_at, upload_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiissii",
            $data['document_id'],
            $data['request_detail_id'],
            $data['document_type'],
            $data['observation'],
            $data['document'],
            $data['created_at'],
            $data['upload_by']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function read()
    {
        $result = $this->db->query("SELECT * FROM document ORDER BY id DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function read_doc_by_id($document_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document WHERE document_id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $document = $result->fetch_assoc();
        $stmt->close();
        return $document;
    }

    public function read_doc_by_detail_id($request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("SELECT * FROM document WHERE request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("ii", $request_detail_id, $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $document = $result->fetch_assoc();
        $stmt->close();
        return $document;
    }

    public function doc_by_detail_id($request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("SELECT * FROM document WHERE request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("ii", $request_detail_id, $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $documents = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $documents;
    }

    public function get_ai_reports($request_detail_id = null)
    {
        $sql = "SELECT * FROM document WHERE document_type = 3";
        $params = [];
        $types = '';

        if ($request_detail_id) {
            $sql .= " AND request_detail_id = ?";
            $params[] = $request_detail_id;
            $types .= 'i';
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $reports = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $reports;
    }

    public function get_ai_report_by_id($document_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM document WHERE document_id = ? AND document_type = 3");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
        return $report;
    }

    public function update($data = [])
    {
        $stmt = $this->db->prepare("UPDATE document SET observation = ?, document = ? WHERE document_id = ? AND request_detail_id = ? AND document_type = ?");
        $stmt->bind_param(
            "ssiii",
            $data['observation'],
            $data['document'],
            $data['document_id'],
            $data['request_detail_id'],
            $data['document_type']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function delete($document_id, $request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("DELETE FROM document WHERE document_id = ? AND request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("iii", $document_id, $request_detail_id, $document_type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function get_document_count($request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM document WHERE request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("ii", $request_detail_id, $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'];
    }

    public function get_next_document_id($request_detail_id, $document_type)
    {
        $stmt = $this->db->prepare("SELECT MAX(document_id) as max_id FROM document WHERE request_detail_id = ? AND document_type = ?");
        $stmt->bind_param("ii", $request_detail_id, $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return ($row && $row['max_id']) ? ($row['max_id'] + 1) : 1;
    }
}
