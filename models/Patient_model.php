<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Patient_model
{
    private $db;
    private $table = "patient";

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
        $stmt = $this->db->prepare("INSERT INTO patient (patient_id, firstname, lastname, date_of_birth, sex, mobile, address, insurance_id, picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
            "sssssssis",
            $data['patient_id'],
            $data['firstname'],
            $data['lastname'],
            $data['date_of_birth'],
            $data['sex'],
            $data['mobile'],
            $data['address'],
            $data['insurance_id'],
            $data['picture']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function read()
    {
        $result = $this->db->query("SELECT * FROM patient ORDER BY id DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function read_by_id($id = null)
    {
        $stmt = $this->db->prepare("SELECT p.*, i.name as insurance FROM patient AS p LEFT JOIN insurance AS i ON i.insurance_id = p.insurance_id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();
        return $patient;
    }

    public function read_by_ci($ci = null)
    {
        $stmt = $this->db->prepare("SELECT * FROM patient WHERE patient_id = ?");
        $stmt->bind_param("s", $ci);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();
        return $patient;
    }

    public function search($data = [])
    {
        $conditions = [];
        $params = [];
        $types = '';

        if (isset($data['patient_id']) && !empty($data['patient_id'])) {
            $conditions[] = "id = ?";
            $params[] = $data['patient_id'];
            $types .= 'i';
        }

        if (isset($data['firstname']) && !empty($data['firstname'])) {
            $conditions[] = "firstname LIKE ?";
            $params[] = '%' . $data['firstname'] . '%';
            $types .= 's';
        }

        if (isset($data['lastname']) && !empty($data['lastname'])) {
            $conditions[] = "lastname LIKE ?";
            $params[] = '%' . $data['lastname'] . '%';
            $types .= 's';
        }

        $sql = "SELECT id, picture, patient_id, firstname, lastname, mobile, date_of_birth FROM patient";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY id DESC LIMIT 500";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $patients = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $patients;
    }

    public function update($data = [])
    {
        $stmt = $this->db->prepare("UPDATE patient SET patient_id = ?, firstname = ?, lastname = ?, date_of_birth = ?, sex = ?, mobile = ?, address = ?, insurance_id = ?, picture = ? WHERE id = ?");
        $stmt->bind_param(
            "sssssssisi",
            $data['patient_id'],
            $data['firstname'],
            $data['lastname'],
            $data['date_of_birth'],
            $data['sex'],
            $data['mobile'],
            $data['address'],
            $data['insurance_id'],
            $data['picture'],
            $data['id']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function delete($id = null)
    {
        $stmt = $this->db->prepare("DELETE FROM patient WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function get_patient_list()
    {
        $result = $this->db->query("SELECT id, patient_id, firstname, lastname FROM patient ORDER BY firstname, lastname");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
