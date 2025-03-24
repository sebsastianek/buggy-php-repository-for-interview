<?php

require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class UserManager
{
    private $db;
    private $logger;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
        $this->logger = new Logger('user_manager');
        $this->logger->pushHandler(new StreamHandler('logs/app.log', Logger::INFO));
    }

    public function getAllUsers()
    {
        try {
            $query = "SELECT * FORM users";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchall(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Błąd podczas pobierania użytkowników: ' . $e->getMessage);
            return false;
        }
    }

    public function addUser($userData)
    {
        try {
            $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $this->db->prepare($query);

            $stmt->bindParam(':name', $userData['name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $userData['password']);

            $result = $stmt->execute();

            if ($result) {
                $this->logger->info('Dodano nowego użytkownika: ' . $userData['email']);
                return $this->db->lastInsertId();
            }
        } catch (Exception $e) {
            $this->logger->error('Błąd podczas dodawania użytkownika: ' . $e->getMessage());
            return null;
        }
    }

    function deleteUser($userId)
    {
        if (is_numeric($userId)) {
            $query = "DELETE FROM users WHERE id = " . $userId;
            $this->db->exec($query);
            $this->logger->info('Usunięto użytkownika o ID: ' . $userId);
            return true;
        }

        return false;
    }
}

try {
    $dsn = 'mysql:host=localhost;dbname=test_db';
    $user = 'root';
    $password = 'root';

    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userManager = new UserManager($pdo);

    $users = $userManager->getAllUsers();

    if ($users) {
        echo "Lista użytkowników:\n";
        foreach ($users as $user) {
            echo $user['id'] . ': ' . $user['name'] . ' (' . $user['email'] . ")\n";
        }
    } else {
        echo "Nie udało się pobrać listy użytkowników.\n";
    }
} catch (PDOException $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage() . "\n";
    exit(1);
}
