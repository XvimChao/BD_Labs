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
        $stmt = $this->pdo->prepare("INSERT INTO anime_pages (title, description) VALUES (:title, :description)");
        $stmt->execute(['title' => $title, 'description' => $description]);
    }

    public function retrieveAll() {
        $stmt = $this->pdo->query("SELECT * FROM anime_pages");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function retrieve($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM anime_pages WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $title, $description) {
        $stmt = $this->pdo->prepare("UPDATE anime_pages SET title = :title, description = :description WHERE id = :id");
        $stmt->execute(['id' => $id, 'title' => $title, 'description' => $description]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM anime_pages WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function deleteMany($ids) {
        if (empty($ids)) return;
        
        // Создаем строку с параметрами для запроса
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM anime_pages WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
}

function main() {
    // Конфигурация базы данных
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_database_name',
        'user' => 'your_username',
        'password' => 'your_password'
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
                $crud->create($title, $description);
                echo "Anime page created.\n";
                break;

            case '2':
                $pages = $crud->retrieveAll();
                foreach ($pages as $page) {
                    echo "ID: {$page['id']}, Title: {$page['title']}, Description: {$page['description']}\n";
                }
                break;

            case '3':
                $id = (int)readline("Enter ID: ");
                $page = $crud->retrieve($id);
                if ($page) {
                    echo "ID: {$page['id']}, Title: {$page['title']}, Description: {$page['description']}\n";
                } else {
                    echo "Anime page not found.\n";
                }
                break;

            case '4':
                $id = (int)readline("Enter ID: ");
                if ($crud->retrieve($id)) {
                    $title = readline("Enter new title: ");
                    $description = readline("Enter new description: ");
                    $crud->update($id, $title, $description);
                    echo "Anime page updated.\n";
                } else {
                    echo "Anime page not found.\n";
                }
                break;

            case '5':
                $id = (int)readline("Enter ID: ");
                if ($crud->retrieve($id)) {
                    $crud->delete($id);
                    echo "Anime page deleted.\n";
                } else {
                    echo "Anime page not found.\n";
                }
                break;

            case '6':
                $idsInput = readline("Enter IDs separated by commas: ");
                if (!empty(trim($idsInput))) {
                    // Преобразуем строку в массив целых чисел
                    $ids = array_map('intval', explode(',', trim($idsInput)));
                    if (!empty($ids)) {
                        $crud->deleteMany($ids);
                        echo "Anime pages deleted.\n";
                    }
                } else {
                    echo "No IDs provided.\n";
                }
                break;

            case '7':
                exit("Exiting...\n");

            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();