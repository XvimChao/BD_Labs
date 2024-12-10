<?php

class AnimePagesCRUD {
    
    
    private $pdo;

    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO("pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", $dbConfig['user'], $dbConfig['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Could not connect to the database: " . $e->getMessage());
        }
    }

    public function create($title, $description) {
        $trimmedTitle = trim($title);
        $trimmedDescription = trim($description);

        if (empty($trimmedTitle) || empty($trimmedDescription) || strlen($trimmedTitle) > 255) {
            throw new InvalidArgumentException("Title and description cannot be empty or consist only of whitespace and title cannot be longer than 255 letters.");
        }
        else{
             // Проверка на существование названия
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM genres WHERE title = :title OR description = :description");
            $stmt->execute(['title' => $trimmedTitle, 'description' => $trimmedDescription]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                throw new InvalidArgumentException("A genre with the title:'{$trimmedTitle}' or description: '{$trimmedDescription}' already exists.");
            }

            // Вставка нового жанра
            $stmt = $this->pdo->prepare("INSERT INTO genres (title, description) VALUES (:title, :description)");
            $stmt->execute(['title' => $trimmedTitle, 'description' => $trimmedDescription]);
        }
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
        $trimmedTitle = trim($title);
        $trimmedDescription = trim($description);

        $newTitle = !empty($trimmedTitle) ? $trimmedTitle : $currentData['title'];
        $newDescription = !empty($trimmedDescription) ? $trimmedDescription : $currentData['description'];

        if (empty($newTitle) || empty($newDescription) || strlen($newTitle) > 255) {
            throw new InvalidArgumentException("Title and description cannot be empty or consist only of whitespace and title cannot be longer than 255 letters.");
        }
        else{
            $stmt = $this->pdo->prepare("UPDATE genres SET title = :title, description = :description WHERE genre_id = :id");
            $stmt->execute(['id' => $id, 'title' => $newTitle, 'description' => $newDescription]);
        }
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

    /*public function nameSearch($title, $description, $limit = 5, $offset = 0){
        $sql = "SELECT * FROM anime_pages WHERE 1=1"; // 1=1 — трюк для упрощения добавления условий
        $queryParams = [];

        // Динамическое построение SQL на основе предоставленных параметров поиска
        foreach ($searchParams as $key => $value) {
            if (!empty($value)) {
                $sql .= " AND $key ILIKE :$key"; 
                $queryParams[":$key"] = '%' . $value . '%';
            }
        }

        $sql .= "LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        foreach ($queryParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }*/
}

function main() {
    
    // Конфигурация базы данных
    /*$dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'ardin2004'
    ];*/
    
    $dbConfig = [
        'host' => 'dpg-csu02hl6l47c739df8og-a.oregon-postgres.render.com',
        'port' => '5432',
        'dbname' => 'arddb_61ib',
        'user' => 'arddb_61ib_user',
        'password' => 'ta81qJ4ZSiUwAU253KuqEk0ZLK3g1HXp'
    ];

    // Создаем экземпляр класса
    $crud = new AnimePagesCRUD($dbConfig);
    
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
                // Заголовки столбцов
                printf("%-5s %-25s %-30s\n", "ID", "Title", "Description");
                echo str_repeat("-", 60) . "\n"; // Разделительная линия
                foreach ($pages as $page) {
                    printf("%-5d %-35s %-30s\n", $page['genre_id'], $page['title'], $page['description']);
                }
                break;
            case '3':
                $id = (int)readline("Enter ID: ");
                $page = $crud->retrieve($id);
                if ($page) {
                    echo "ID: {$page['genre_id']},     Title: {$page['title']},      Description: {$page['description']}\n";
                } else {
                    echo "Genre not found.\n";
                }
                break;

            case '4':
                $id = (int)readline("Enter ID: ");
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
                $id = (int)readline("Enter ID: ");
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
                    $ids = array_map('intval', explode(',', trim($idsInput)));

                    $existingIds = [];
                    $nonExistingIds = [];

                    foreach ($ids as $id) {
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
                $title = readline("Enter title: ");
                $description = readline("Enter description: ");
                $searchParams = [
                    'title' => trim($title),
                    'description' => trim($description)
                ];
                $limit = (int)readline("Enter number of results per page (default 5): ");
                if ($limit <= 0) {
                    $limit = 5; // Default value
                }
                $offset = (int)readline("Enter offset (default 0): ");
                try {
                    $results = $crud->nameSearch($searchParams, $limit, $offset);
                    if (!empty($results)) {
                        print_r($results);
                    } else {
                        echo "No results found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            */
            case '7':
                exit("Exiting...\n");

            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();