<?php
// api/models/Business.php

require_once __DIR__ . '/../utils/Database.php';

class Business {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $query = "SELECT b.*, c.name as category_name 
                  FROM businesses b 
                  JOIN categories c ON b.category_id = c.id 
                  WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND b.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (b.name LIKE ? OR b.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['city'])) {
            $query .= " AND b.city = ?";
            $params[] = $filters['city'];
        }

        // Sorting
        $sortOptions = [
            'newest' => 'b.created_at DESC',
            'rating' => 'b.rating DESC',
            'popular' => 'b.review_count DESC'
        ];
        
        $sort = $sortOptions[$filters['sort'] ?? 'newest'] ?? 'b.created_at DESC';
        $query .= " ORDER BY $sort";

        // Pagination
        $limit = $filters['limit'] ?? 10;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as total 
                  FROM businesses b 
                  JOIN categories c ON b.category_id = c.id 
                  WHERE 1=1";
        $params = [];

        // Apply the same filters as getAll()
        if (!empty($filters['category_id'])) {
            $query .= " AND b.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (b.name LIKE ? OR b.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['city'])) {
            $query .= " AND b.city = ?";
            $params[] = $filters['city'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    }

    public function getById($id) {
        $query = "SELECT b.*, c.name as category_name 
                  FROM businesses b 
                  JOIN categories c ON b.category_id = c.id 
                  WHERE b.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$business) {
            return null;
        }
        
        // Get images
        $query = "SELECT image_url, is_primary 
                  FROM business_images 
                  WHERE business_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $business['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $business;
    }

    public function create($data, $ownerId = null) {
        $query = "INSERT INTO businesses (
                    name, description, category_id, address, city, 
                    state, zip_code, phone, email, website, 
                    latitude, longitude, opening_hours, owner_id
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $data['phone'],
            $data['email'] ?? null,
            $data['website'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['opening_hours'] ?? null,
            $ownerId
        ]);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $query = "UPDATE businesses SET 
                    name = ?, description = ?, category_id = ?, address = ?, 
                    city = ?, state = ?, zip_code = ?, phone = ?, email = ?, 
                    website = ?, latitude = ?, longitude = ?, opening_hours = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $data['phone'],
            $data['email'] ?? null,
            $data['website'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['opening_hours'] ?? null,
            $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM businesses WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function addImage($businessId, $imageUrl, $isPrimary = false) {
        $query = "INSERT INTO business_images (business_id, image_url, is_primary) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$businessId, $imageUrl, $isPrimary ? 1 : 0]);
    }
}