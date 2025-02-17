<?php
class UsersCRUD {
    private $pdo;

    
    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO("pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", $dbConfig['user'], $dbConfig['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Could not connect to the database: " . $e->getMessage());
        }
    }
    function createUser(
        PDO $connection, 
        string $user_id, 
        string $username, 
        string $password, 
    ): int {
        try {
            // Подготовка вызова процедуры
            $stmt = $connection->prepare("CALL acting_role_create(?, ?, ?)");
            
            // Выполнение процедуры с параметрами
            $stmt->execute([
                $user_id, 
                $username, 
                $password, 
            ]);
            
            // Получение ID созданного пользователя
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $created_id = $result['id'] ?? null;
            
            // Возврат ID
            return $created_id;
            
        } catch (PDOException $e) {
            // Обработка ошибок
            error_log("User creation error: " . $e->getMessage());
            throw $e;
        }
    }
}
function main() {
    
    // Конфигурация базы данных
    
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'water7op'
    ];
    $crud = new UsersCRUD($dbConfig);
}

main();