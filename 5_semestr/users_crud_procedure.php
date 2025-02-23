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

    // Создание пользователя
    public function createUser($username, $email, $password) {
        try {
            $query = "
                CALL UserCreate(:username,:email,:password)
            ";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['username' => $username, 'email' => $email, 'password' => $password]);
            echo "User created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating user: " . $e->getMessage() . "\n";
        }
    }

    // Получение всех пользователей
    public function retrieveAllUsers() {
        try {
            // Выполнение процедуры с курсором
            $stmt = $this->pdo->query("CALL UserRetrieveAll()");
            
            // Проверка успешности выполнения
            if ($stmt === false) {
                throw new PDOException("Query execution failed");
            }
            
            // Вывод результатов в консоль
            echo "Список пользователей:\n";
            echo "-------------------\n";
            
            // Перебор результатов с помощью курсора
            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "ID: {$user['user_id']}\n";
                echo "Username: {$user['username']}\n";
                echo "Email: {$user['email']}\n";
                echo "-------------------\n";
            }
            
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }

    // Получение пользователя по ID
    public function retrieveUser($user_id) {
        try {
            // Вызов процедуры для получения пользователя по ID
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                print_r($result);
            } else {
                echo "User with ID {$user_id} not found.\n";
            }
        } catch (PDOException $e) {
            echo "Error retrieving user: " . $e->getMessage() . "\n";
        }
    }

    // Обновление пользователя
    public function updateUser($user_id, $username, $email, $password) {
        try {
            // Вызов процедуры для обновления пользователя
            $stmt = $this->pdo->prepare("CALL UserUpdate(?, ?, ?, ?)");
            $stmt->execute([$user_id, $username, $email, $password]);
            echo "User updated successfully.\n";
        } catch (PDOException $e) {
            echo "Error updating user: " . $e->getMessage() . "\n";
        }
    }

    // Удаление пользователя
    public function deleteUser($user_id) {
        try {
            // Вызов процедуры для удаления пользователя
            $stmt = $this->pdo->prepare("CALL UserDelete(?)");
            $stmt->execute([$user_id]);
            echo "User deleted successfully.\n";
        } catch (PDOException $e) {
            echo "Error deleting user: " . $e->getMessage() . "\n";
        }
    }

    // Массовое удаление пользователей
    public function deleteManyUsers($user_ids) {
        if (empty($user_ids)) return;

        try {
            // Вызов процедуры для массового удаления пользователей
            // Передаем массив ID как строку в формате {1,2,3}
            $idsString = '{' . implode(',', array_map('intval', $user_ids)) . '}';
            
            // Подготавливаем запрос с массивом ID пользователей
            $stmt = $this->pdo->prepare("CALL UserDeleteMany(?)");
            $stmt->execute([$idsString]);
            
            echo "Users deleted successfully.\n";
        } catch (PDOException $e) {
            echo "Error deleting users: " . $e->getMessage() . "\n";
        }
    }

}

function main() {
    
    // Конфигурация базы данных
    
    /*
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'water7op'
    ];
    */
    
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'postgres',
        'user' => 'postgres',
        'password' => 'ardin2004'
    ];
    // Создаем экземпляр класса
    $crud = new UsersCRUD($dbConfig);
    
    while (true) {
        echo "\n1. Create\n2. Retrieve All\n3. Retrieve\n4. Update\n5. Delete\n6. Delete Many\n7. Exit\n";

       
        $choice = readline("Choose an option: ");
        switch ($choice) {
            case '1':
                // Создание пользователя
                $username = readline("Enter username: ");
                $email = readline("Enter email: ");
                $password = readline("Enter password: ");
                $crud->createUser($username, $email, $password);
                break;

            case '2':
                // Получение всех пользователей
                echo "All users:\n";
                $crud->retrieveAllUsers();
                break;

            case '3':
                // Получение пользователя по ID
                $user_id = readline("Enter user ID: ");
                if ($crud->retrieveUser($user_id)) {
                    echo "Check the logs for user details.\n"; // Уведомления будут выведены через RAISE NOTICE.
                } else {
                    echo "User not found.\n";
                }
                break;

            case '4':
                // Обновление пользователя
                $user_id = readline("Enter user ID to update: ");

                if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID. Please enter a valid integer.\n";
                    break;
                }

                if ($crud->retrieveUser($user_id)) {
                    $username = readline("Enter new username: ");
                    $email = readline("Enter new email: ");
                    $password = readline("Enter new password: ");
                    $crud->updateUser($user_id, $username, $email, $password);
                } else {
                    echo "User not found.\n";
                }
                break;

            case '5':
                // Удаление пользователя
                $user_id = readline("Enter user ID to delete: ");
                if ($crud->retrieveUser($user_id)) {
                    $crud->deleteUser($user_id);
                } else {
                    echo "User not found.\n";
                }
                break;

            case '6':
                // Массовое удаление пользователей
                $idsInput = readline("Enter user IDs to delete (comma-separated): ");
                // Преобразуем строку в массив
                if (!empty($idsInput)) {
                    $user_ids = explode(',', trim($idsInput));
                    // Удаляем пробелы и оставляем только валидные ID
                    foreach ($user_ids as &$id) {
                        $id = trim($id);
                    }
                    unset($id); // Удаляем ссылку на последний элемент массива
                    // Вызываем метод массового удаления
                    if (!empty(array_filter($user_ids))) {  // Проверяем на наличие валидных ID
                        $crud->deleteManyUsers(array_filter($user_ids));
                    } else {
                        echo "No valid user IDs provided for deletion.\n";
                    }
                } else {
                    echo "No user IDs provided for deletion.\n";
                }
                break;

            case '7':
                // Выход из программы
                echo "Exiting...\n";
                exit;

            default:
                echo "Invalid option. Please try again.\n";
        }
    }

}

main();

?>