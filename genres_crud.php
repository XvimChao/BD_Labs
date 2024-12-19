<?php
class GenresCRUD {
    
    // Проверка на вставку символов
    private $pdo;

    

    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO("pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", $dbConfig['user'], $dbConfig['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Could not connect to the database: " . $e->getMessage());
        }
    }

    public function mb_ucfirst($str, $encoding='UTF-8') {
        $str = mb_strtolower($str, $encoding);
        // Делаем первую букву заглавной
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
    }

    public function mf_first($str, $encoding='UTF-8') {
        // Делаем первую букву заглавной
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
    }
    
    public function create($title, $description) {
     
        //$trimmedTitle = ucfirst(strtolower(trim($title)));
        //$trimmedDescription = ucfirst(strtolower(trim($description)));
        
        $trimmedTitle = $this->mb_ucfirst($title);
        $trimmedDescription = $this->mf_first(trim($description));
        
        if (!preg_match('/^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ\-]+$/u', $trimmedTitle)) {
            throw new InvalidArgumentException("Title must consist only of letters.");
        }
        
        if (strlen($trimmedTitle) > 255) {
            throw new InvalidArgumentException("Title cannot be longer than 255 letters.");
        }
        
        if (!preg_match('/[a-zA-Zа-яА-ЯёЁ]/', $trimmedDescription)) {
            throw new InvalidArgumentException("Description cannot consist only of special characters.");
        }
        
        
        
        /* if (empty($trimmedTitle) || empty($trimmedDescription)) {
             throw new InvalidArgumentException("Title and description cannot be empty or consist only of whitespace.");
        }*/

        
        // Проверка на существование названия или описания
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM genres WHERE (title = :title)");
        $stmt->execute(['title' => $trimmedTitle]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new InvalidArgumentException("A genre with the title:'{$trimmedTitle}' already exist.");
        }

        $stmt = $this->pdo->prepare("INSERT INTO genres (title, description) VALUES (:title, :description)");
        $stmt->execute(['title' => $trimmedTitle, 'description' => $trimmedDescription]);
        
    }

    public function retrieveAll() {
        $stmt = $this->pdo->query("SELECT * FROM genres ORDER BY genre_id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function retrieve($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM genres WHERE genre_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $title, $description) {
        $currentData = $this->retrieve($id);
        
        $trimmedTitle = $this->mb_ucfirst($title);
        $trimmedDescription = $this->mf_first(trim($description));
        
        $newTitle = !empty($trimmedTitle) ? $trimmedTitle : $currentData['title'];
        $newDescription = !empty($trimmedDescription) ? $trimmedDescription : $currentData['description'];

        if (!preg_match('/^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ\-]+$/u', $newTitle)) {
            throw new InvalidArgumentException("Title must consist only of letters.");
        }

        if (strlen($newTitle) > 255) {
            throw new InvalidArgumentException("Title cannot be longer than 255 letters.");
        }

        if (!empty($title) && empty($trimmedTitle) || !empty($description) && empty($trimmedDescription)) {
            throw new InvalidArgumentException("Title and description cannot be consist only of whitespace.");
        }
        
        if (!preg_match('/[a-zA-Zа-яА-ЯёЁ]/', $newDescription)) {
            throw new InvalidArgumentException("Description cannot consist only of special characters.");
        }
        
        $newTitle = ucfirst(strtolower(trim($newTitle)));
        $newDescription = trim($newDescription);

        // Проверка на существование названия или описания
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM genres WHERE (title = :title) AND genre_id != :id");
        $stmt->execute(['title' => $trimmedTitle,'id' => $id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new InvalidArgumentException("A genre with the title:'{$trimmedTitle}' already exist.");
        }
        $stmt = $this->pdo->prepare("UPDATE genres SET title = :title, description = :description WHERE genre_id = :id");
        $stmt->execute(['id' => $id, 'title' => $newTitle, 'description' => $newDescription]);
        
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM genres WHERE genre_id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function deleteMany($ids) {
        if (empty($ids)) return;
        
        // Создаем строку с параметрами для запроса
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM genres WHERE genre_id IN ($placeholders)");
        $stmt->execute($ids);
        
    }
    
    /*
    public function nameSearch($title, $description, $limit = 5, $offset = 0) {
        $sql = "SELECT * FROM genres WHERE 1=1"; // Измените на вашу таблицу
        $queryParams = [];
    
        // Проверяем наличие параметра title
        if (!empty($title)) {
            $sql .= " AND title ILIKE :title";
            $queryParams[":title"] = '%' . trim($title) . '%'; // Используем ILIKE для регистронезависимого поиска
        }
    
        // Проверяем наличие параметра description
        if (!empty($description)) {
            $sql .= " AND description ILIKE :description";
            $queryParams[":description"] = '%' . trim($description) . '%'; // Используем ILIKE для регистронезависимого поиска
        }
    
        // Добавляем параметры пагинации
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
        // Привязываем параметры поиска
        foreach ($queryParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
    
        // Выполняем запрос
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    */
    
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
    /*
    $dbConfig = [
        'host' => 'dpg-csu02hl6l47c739df8og-a.oregon-postgres.render.com',
        'port' => '5432',
        'dbname' => 'arddb_61ib',
        'user' => 'arddb_61ib_user',
        'password' => 'ta81qJ4ZSiUwAU253KuqEk0ZLK3g1HXp'
    ];
    */
    // Создаем экземпляр класса
    $crud = new GenresCRUD($dbConfig);
    
    while (true) {
        echo "\n1. Create\n2. Retrieve All\n3. Retrieve\n4. Update\n5. Delete\n6. Delete Many\n7. Exit\n";

        $choice = readline("Choose an option: ");
        
        switch ($choice) {
            case '1':
                $title = readline("Enter title: ");
                $description = readline("Enter description: ");
                try {
                    $crud->create($title, $description);
                    echo "Genre created.\n";
                } catch (InvalidArgumentException $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;

            case '2':
                $pages = $crud->retrieveAll();
                
                printf("%-5s %-15s \t %-30s\n", "ID", "Title", "Description");
                echo str_repeat("-", 60) . "\n";
                foreach ($pages as $page) {
                    printf("%-5s  %-15s \t %-30s\n", trim($page['genre_id']), trim($page['title']), trim($page['description']));
                }
                break;
            case '3':
                $id = readline("Enter ID: ");

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID. Please enter a valid integer.\n";
                    break;
                }
                $page = $crud->retrieve($id);
                if ($page) {
                    printf("%-5s %-15s %-30s\n", "ID", "Title", "Description");
                    echo str_repeat("-", 60) . "\n";
                    printf("%-5s  %-15s \t %-30s\n", trim($page['genre_id']), trim($page['title']), trim($page['description']));
                    
                } else {
                    echo "Genre not found.\n";
                }
                break;

            case '4':
                $id = readline("Enter ID: ");

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID. Please enter a valid integer.\n";
                    break;
                }

                if ($crud->retrieve($id)) {
                    $title = readline("Enter new title: ");
                    $description = readline("Enter new description: ");
                    try{
                        $crud->update($id, $title, $description);
                        echo "Genre updated.\n";
                    }
                    catch (InvalidArgumentException $e) {
                        echo "Error: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "Genre not found.\n";
                }
                break;

            case '5':
                $id = readline("Enter ID: ");

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID. Please enter a valid integer.\n";
                    break;
                }

                if ($crud->retrieve($id)) {
                    $crud->delete($id);
                    echo "Genre deleted.\n";
                } else {
                    echo "Genre not found.\n";
                }
                break;

            case '6':
                $idsInput = readline("Enter IDs separated by commas: ");
                if (!empty(trim($idsInput))) {
                    // Преобразуем строку в массив целых чисел
                    $ids = array_unique(array_map('trim', explode(',', trim($idsInput))));

                    $existingIds = [];
                    $nonExistingIds = [];

                    foreach ($ids as $id) {

                        if (!filter_var(trim($id), FILTER_VALIDATE_INT)) {
                            $nonExistingIds[] = trim($id);
                            continue;
                        }
                        // Проверяем, существует ли запись с текущим ID
                        if ($crud->retrieve($id)){
                            $existingIds[] = $id;
                        } else {
                            $nonExistingIds[] = $id;
                        }
                    }

                    if (empty($existingIds)) {
                        echo "No genres with the given IDs.\n";
                    } else {
                        // Выводим отсутствующие ID, если они есть
                        if (!empty($nonExistingIds)) {
                            echo "The following IDs were not found: " . implode(', ', $nonExistingIds) . "\n";
                        }
                        $crud->deleteMany($existingIds);
                        echo "Genres with IDs " . implode(', ', $existingIds) . " have been deleted.\n";
                    }
                } else {
                    echo "No IDs provided.\n";
                }
                break;
                
            /*case '7':
                $title = readline("Enter title to search (leave empty for no filter): ");
                $description = readline("Enter description to search (leave empty for no filter): ");
                    
                // Параметры пагинации
                $limit = (int)readline("Enter number of results per page (default 5): ");

                if ($limit <= 0) { 
                    $limit = 5; 
                }
                    
                    $offset = (int)readline("Enter offset (default 0): ");
                    
                try {
                    // Вызов метода nameSearch с двумя параметрами
                    $results = $crud->nameSearch($title, $description, $limit, $offset);
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            echo "ID: {$result['genre_id']}, Title: {$result['title']}, Description: {$result['description']}\n";
                        }
                    } else {
                        echo "No results found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
                */

            case '7':
                exit("Exiting...\n");

            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();