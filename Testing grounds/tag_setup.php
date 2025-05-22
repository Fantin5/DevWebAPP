<?php
class TagManager {
    private $conn;
    private $tagClasses = ['primary', 'secondary', 'accent'];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function setupTables() {
        // Check if tag_definitions exists
        $tableExistsQuery = "SHOW TABLES LIKE 'tag_definitions'";
        $tableExists = $this->conn->query($tableExistsQuery)->num_rows > 0;
        
        if (!$tableExists) {
            // Create tag_definitions table
            $createTableSQL = "CREATE TABLE `tag_definitions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `display_name` varchar(100) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            
            if ($this->conn->query($createTableSQL)) {
                $this->insertDefaultTags();
            }
        }
        
        // Check if activity_tags exists
        $tableExistsQuery = "SHOW TABLES LIKE 'activity_tags'";
        $tableExists = $this->conn->query($tableExistsQuery)->num_rows > 0;
        
        if (!$tableExists) {
            $createTableSQL = "CREATE TABLE `activity_tags` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `activity_id` int(11) NOT NULL,
                `tag_definition_id` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `activity_tag_unique` (`activity_id`, `tag_definition_id`),
                FOREIGN KEY (`tag_definition_id`) REFERENCES `tag_definitions`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            
            $this->conn->query($createTableSQL);
        }
    }
    
    private function insertDefaultTags() {
        $insertTagsSQL = "INSERT INTO `tag_definitions` (`name`, `display_name`) VALUES
            ('interieur', 'Intérieur'),
            ('exterieur', 'Extérieur'),
            ('art', 'Art'),
            ('cuisine', 'Cuisine'),
            ('sport', 'Sport'),
            ('bien_etre', 'Bien-être'),
            ('creativite', 'Créativité'),
            ('ecologie', 'Écologie'),
            ('randonnee', 'Randonnée'),
            ('jardinage', 'Jardinage'),
            ('meditation', 'Méditation'),
            ('artisanat', 'Artisanat');";
        
        $this->conn->query($insertTagsSQL);
    }
    
    public function getAllTags() {
        $sql = "SELECT * FROM tag_definitions ORDER BY display_name";
        $result = $this->conn->query($sql);
        $tags = [];
        
        if ($result && $result->num_rows > 0) {
            $i = 0;
            while($row = $result->fetch_assoc()) {
                $tags[$row['name']] = [
                    'id' => $row['id'],
                    'display_name' => $row['display_name'],
                    'class' => $this->tagClasses[$i % count($this->tagClasses)]
                ];
                $i++;
            }
        }
        return $tags;
    }
    
    public function getActivityTags($activity_id) {
        $sql = "SELECT td.name, td.display_name 
                FROM activity_tags at 
                JOIN tag_definitions td ON at.tag_definition_id = td.id 
                WHERE at.activity_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function updateActivityTags($activity_id, $tag_names) {
        $this->conn->begin_transaction();
        
        try {
            // Delete existing tags
            $sql = "DELETE FROM activity_tags WHERE activity_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            
            if (!empty($tag_names)) {
                $sql = "INSERT INTO activity_tags (activity_id, tag_definition_id) 
                        SELECT ?, id FROM tag_definitions WHERE name = ?";
                $stmt = $this->conn->prepare($sql);
                
                foreach ($tag_names as $tag) {
                    $stmt->bind_param("is", $activity_id, $tag);
                    $stmt->execute();
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    public function getTagClass($tag) {
        $tags = $this->getAllTags();
        return isset($tags[$tag]) ? $tags[$tag]['class'] : 'primary';
    }
    
    public function getTagDisplayName($tag, $tagDisplayNames = null, $index = null) {
        if ($tagDisplayNames && $index !== null && isset($tagDisplayNames[$index])) {
            return $tagDisplayNames[$index];
        }
        $tags = $this->getAllTags();
        return isset($tags[$tag]) ? 
            $tags[$tag]['display_name'] : 
            ucfirst(str_replace('_', ' ', $tag));
    }
}

// Only run setup if this file is called directly
if (basename($_SERVER['PHP_SELF']) == 'tag_setup.php') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "activity";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $tagManager = new TagManager($conn);
    $tagManager->setupTables();
    echo "Tag system setup completed.";
}

