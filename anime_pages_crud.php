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

        if (empty($trimmedTitle) || empty($trimmedDescription)) {
            throw new InvalidArgumentException("Title and description cannot be empty or consist only of whitespace.");
        }
        else{
            $stmt = $this->pdo->prepare("INSERT INTO anime_pages (title, description) VALUES (:title, :description)");
            $stmt->execute(['title' => $title, 'description' => $description]);
        }
    }

    public function retrieveAll() {
        $stmt = $this->pdo->query("SELECT * FROM anime_pages ORDER BY anime_page_id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function retrieve($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM anime_pages WHERE anime_page_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $title, $description) {
        $trimmedTitle = trim($title);
        $trimmedDescription = trim($description);

        if (empty($trimmedTitle) || empty($trimmedDescription)) {
            throw new InvalidArgumentException("Title and description cannot be empty or consist only of whitespace.");
        }
        else{
            $stmt = $this->pdo->prepare("UPDATE anime_pages SET title = :title, description = :description WHERE anime_page_id = :id");
            $stmt->execute(['id' => $id, 'title' => $title, 'description' => $description]);
        }
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM anime_pages WHERE anime_page_id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function deleteMany($ids) {
        if (empty($ids)) return;
        
        // Создаем строку с параметрами для запроса
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM anime_pages WHERE anime_page_id IN ($placeholders)");
        $stmt->execute($ids);
    }

    public function nameSearch($title, $description, $limit = 5, $offset = 0){
        $sql = "SELECT * FROM anime_pages";
        $queryParams = [];

        foreach($searchParams as $key => $value){
            if(!empty($value)){
                $sql .= "AND $key =:$key ";
                $queryParams[":$key"]=$value;
            }
        }

        $sql .= "LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        $stmt->binValue(':limit',(int)$limit,PDO::PARAM_INT);
        $stmt->binValue(':offset',(int)$offset,PDO::PARAM_INT);

        foreach($queryParams as $param => $value){
            $stmt->binValue($param,$value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
        echo "\n1. Create\n2. Retrieve All\n3. Retrieve\n4. Update\n5. Delete\n6. Delete Many\n7. nameSearch\n8. Exit\n";

        $choice = readline("Choose an option: ");
        
        switch ($choice) {
            case '1':
                $title = readline("Enter title: ");
                $description = readline("Enter description: ");
                try {
                    $crud->create($title, $description);
                    echo "Anime page created.\n";
                } catch (InvalidArgumentException $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;

            case '2':
                $pages = $crud->retrieveAll();
                foreach ($pages as $page) {
                    echo "ID: {$page['anime_page_id']}, Title: {$page['title']}, Description: {$page['description']}\n";
                }
                break;

            case '3':
                $id = (int)readline("Enter ID: ");
                $page = $crud->retrieve($id);
                if ($page) {
                    echo "ID: {$page['anime_page_id']}, Title: {$page['title']}, Description: {$page['description']}\n";
                } else {
                    echo "Anime page not found.\n";
                }
                break;

            case '4':
                $id = (int)readline("Enter ID: ");
                if ($crud->retrieve($id)) {
                    $title = readline("Enter new title: ");
                    $description = readline("Enter new description: ");
                    try{
                        $crud->update($id, $title, $description);
                        echo "Anime page updated.\n";
                    }
                    catch (InvalidArgumentException $e) {
                        echo "Error: " . $e->getMessage() . "\n";
                    }
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
                $title = readline("Enter title: ");
                $description = readline("Enter description: ");
                $searchParams = [
                    'title' => 'N',
                    'description' => 'N'
                ];
                $results=$crud->nameSearch($searchParams,5,0);
                print_r($results);

            case '8':
                exit("Exiting...\n");

            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();