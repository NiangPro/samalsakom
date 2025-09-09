<?php
/**
 * Configuration de la base de données
 * Plateforme SamalSakom - Gestion de Tontines et Épargne
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'samalsakom_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Connexion à la base de données avec PDO
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            echo "Erreur de connexion: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
