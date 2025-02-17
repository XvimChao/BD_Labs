<?php
// Конфигурация базы данных
$dbConfig = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'your_dbname',
    'user' => 'postgres',
    'password' => 'water7op'
];

// Функция подключения к базе данных
function getDatabaseConnection(array $config): PDO {
    try {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};";
        
        $connection = new PDO(
            $dsn, 
            $config['user'], 
            $config['password'], 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true
            ]
        );
        
        return $connection;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw $e;
    }
}

// Функция создания пользователя
function createUser(
    PDO $connection, 
    string $user_id, 
    string $username, 
    string $password, 
): int {
    try {
        // Подготовка вызова процедуры
        $stmt = $connection->prepare("CALL CreateUser(?, ?, ?)");
        
        // Выполнение процедуры с параметрами
        $stmt->execute([
            $user_id, 
            $username, 
            $password
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
// Функция консольного ввода
function getConsoleInput(string $prompt): string {
    echo $prompt;
    return trim(fgets(STDIN));
}

// Основной скрипт
try {
    // Получаем соединение с базой данных
    $connection = getDatabaseConnection($dbConfig);
    
    // Консольный ввод данных
    echo "Регистрация нового пользователя\n";
    
    $user_id = getConsoleInput("Введите ID пользователя: ");
    $username = getConsoleInput("Введите имя пользователя: ");
    $password = getConsoleInput("Введите пароль: ");
    
    // Хеширование пароля
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Создаем пользователя
    $newUserId = createUser(
        $connection, 
        $user_id, 
        $username, 
        $hashedPassword
    );
    
    echo "Создан пользователь с ID: " . $newUserId . "\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}