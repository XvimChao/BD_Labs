<?php
class GenresCRUD {
    private $pdo;

    
    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO("pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", $dbConfig['user'], $dbConfig['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Could not connect to the database: " . $e->getMessage());
        }
    }



    public function mb_str_pad($input, $pad_length) {
        $input_length = mb_strlen($input);
        
        if ($input_length >= $pad_length) {
            return $input;
        }
    
        $pad = str_repeat(" ", $pad_length - $input_length);

        return $input . $pad;
        
    }

    public function _retrieve_old($results) {

        if(!empty($results)){
            $pages = $results;
        }
        else{
            $pages = $this->retrieveAll();
        }

        $header = [
            'genre_id' => 'ID',
            'title' => 'Title',
            'description' => 'Description'
        ];
        
        $maxIdLength = strlen($header['genre_id']);
        $maxTitleLength = mb_strlen($header['title']);
        $maxDescriptionLength = 50;

        // Определяем максимальные длины для каждого столбца
        foreach ($pages as $page) {
            $maxTitleLength = max($maxTitleLength, mb_strlen(trim($page['title'])));
            $maxIdLength = max($maxTitleLength, strlen($page['genre_id']));
        }

        printf("%-*s\t%-*s \t%-*s\n", 
            $maxIdLength, trim($header['genre_id']), 
            $maxTitleLength, $this->mb_str_pad(trim($header['title']), $maxTitleLength),
            $maxDescriptionLength, trim($header['description'])
        );

        echo str_repeat("-", $maxIdLength + $maxTitleLength + $maxDescriptionLength + 4) . "\n";
        foreach ($pages as $page) {
            printf("%-*s\t%-*s \t%-*s\n", 
                $maxIdLength, trim($page['genre_id']), 
                $maxTitleLength, $this->mb_str_pad(trim($page['title']), $maxTitleLength),
                $maxDescriptionLength, trim($page['description'])
            );
        }
    }

    public function _retrieve($results) {

        $header = [
            'genre_id' => 'ID',
            'title' => 'Title',
            'description' => 'Description'
        ];
        $fill_sym = ' ';

        if(!empty($results)){
            $pages = $results;
        }
        else{
            $pages = $this->retrieveAll();
        }

        

        $width_columns = array();
        foreach($header as $head_key => $head){
            if(!isset($width_columns[$head_key])){
                $width_columns[$head_key] = '';
            }
            $width_columns[$head_key] = max($width_columns[$head_key], mb_strlen(trim($head)));
        }

        // Определяем максимальные длины для каждого столбца
        foreach ($pages as $page) {
            foreach($page as $column_key => $column_value){
                $width_columns[$column_key] = max($width_columns[$column_key], mb_strlen(trim($column_value)));
            }
        }

        $head_str = '';
        $divisor_str = '';
        foreach($header as $head_key => $head){
            $head_str .= $head.str_repeat($fill_sym, $width_columns[$head_key]-mb_strlen($head)+2);
            $divisor_str .= str_repeat("-", $width_columns[$head_key]);
        }

        echo $head_str."\n";
        echo $divisor_str."\n";
        
        foreach ($pages as $page) {
            $page_str = '';
            foreach($page as $column_key => $column_value){
                $page_str .= $column_value.str_repeat($fill_sym, $width_columns[$column_key]-mb_strlen($column_value)+2);
            }
            echo $page_str."\n";
        }
    }


    public function mb_ucfirst($str, $encoding='UTF-8') {
        $str = mb_strtolower($str, $encoding);
        // Делаем первую букву заглавной
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
    }

    function replaceSpaces($input) {
    // Заменяем пробелы в начале и конце строки на один пробел
    $output = preg_replace('/^\s+|\s+$/', ' ', $input);
    // Заменяем все множественные пробелы между словами на один пробел
    $output = preg_replace('/\s+/', ' ', $output);
    return $output;
}

    public function create($title, $description) {

        $trimmedTitle = $this->mb_ucfirst(trim($title));
        $trimmedDescription = $this->mb_ucfirst(trim($description));

        $trimmedTitle = preg_replace('/\s+/', ' ', $trimmedTitle);
        $trimmedDescription = preg_replace('/\s+/', ' ', $trimmedDescription);
        
        //Удаляю пробелы до и после -
        $trimmedTitle = preg_replace('/\s*-\s*/', '-', $trimmedTitle);
        
        if (!preg_match('/^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ]+(-[a-zA-Zа-яА-ЯёЁ]+)?( [a-zA-Zа-яА-ЯёЁ]+(-[a-zA-Zа-яА-ЯёЁ]+)?)?$/u', $trimmedTitle)) {
            throw new InvalidArgumentException("Title must consist only of letters.");
        }

        if (preg_match('/[-]/', $trimmedTitle) && preg_match('/[ ]/', $trimmedTitle)) {
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

        $stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM genres 
        WHERE REPLACE(REPLACE(title, ' ', ''), '-', '') = REPLACE(REPLACE(:title, ' ', ''), '-', '')
        ");
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $title, $description) {
        $currentData = $this->retrieve($id);
        
        $trimmedTitle = $this->mb_ucfirst(trim($title));
        $trimmedDescription = $this->mb_ucfirst(trim($description));

        $trimmedTitle = preg_replace('/\s+/', ' ', $trimmedTitle);
        $trimmedDescription = preg_replace('/\s+/', ' ', $trimmedDescription);

        //Удаляю пробелы до и после -
        $trimmedTitle = preg_replace('/\s*-\s*/', '-', $trimmedTitle);

        $newTitle = !empty($trimmedTitle) ? $trimmedTitle : $currentData['title'];
        $newDescription = !empty($trimmedDescription) ? $trimmedDescription : $currentData['description'];

        if (!preg_match('/^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ]+(-[a-zA-Zа-яА-ЯёЁ]+)?( [a-zA-Zа-яА-ЯёЁ]+(-[a-zA-Zа-яА-ЯёЁ]+)?)?$/u', $newTitle)) {
            throw new InvalidArgumentException("Title must consist only of letters.");
        }

        if (preg_match('/[-]/', $trimmedTitle) && preg_match('/[ ]/', $trimmedTitle)) {
            throw new InvalidArgumentException("Title must consist only of letters.");
        }
        
        if (strlen($newTitle) > 255) {
            throw new InvalidArgumentException("Title cannot be longer than 255 letters.");
        }

        if (!empty($title) && empty($newTitle) || !empty($description) && empty($newDescription)) {
            throw new InvalidArgumentException("Title and description cannot be consist only of whitespace.");
        }
        
        if (!preg_match('/[a-zA-Zа-яА-ЯёЁ]/', $newDescription)) {
            throw new InvalidArgumentException("Description cannot consist only of special characters.");
        }
        
        $newTitle = $this->mb_ucfirst(trim($newTitle));
        $newDescription = $this->mb_ucfirst(trim($newDescription));

        // Проверка на существование названия или описания
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM genres WHERE (title = :title) AND genre_id != :id");
        $stmt->execute(['title' => $trimmedTitle,'id' => $id]);
        $count = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
        SELECT COUNT(*) FROM genres 
        WHERE REPLACE(REPLACE(title, ' ', ''), '-', '') = REPLACE(REPLACE(:title, ' ', ''), '-', '') AND genre_id != :id
        ");
        $stmt->execute(['title' => $trimmedTitle,'id' => $id]);
        $count = $stmt->fetchColumn();


        if ($count > 0) {
            throw new InvalidArgumentException("A genre with the title:'{$trimmedTitle}' already exist.");
        }
        $stmt = $this->pdo->prepare("UPDATE genres SET title = :title, description = :description WHERE genre_id = :id");
        $stmt->execute(['id' => $id, 'title' => $newTitle, 'description' => $newDescription]);
        
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM genres WHERE genre_id = :id AND genre_id != :id");
        $stmt->execute(['id' => $id]);
    }

    public function deleteMany($ids) {
        if (empty($ids)) return;
        
        // Создаем строку с параметрами для запроса
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM genres WHERE genre_id IN ($placeholders)");
        $stmt->execute($ids);  
    }
    
    public function nameSearch($title, $description, $limit = 5, $offset = 0) {
        $sql = "SELECT * FROM genres WHERE 1=1";
        $queryParams = [];

        
        if (!empty($title)) {
            if($title === ' '){
                $sql .= " AND title ILIKE :title";
                $queryParams[":title"] = '%' . ($title) . '%';
            }
            else{
                $title = preg_replace('/\s*-\s*/', '-', $title);
                $title = preg_replace('/^\s+|\s+$/', ' ', $title); // Заменяем пробелы в начале и конце на один пробел
                $title = preg_replace('/\s+/', ' ', $title); // Заменяем множественные пробелы на один
                $sql .= " AND title ILIKE :title";
                $queryParams[":title"] = '%' . ($title) . '%';
            }
        }
    
        // Проверяем наличие параметра description
        if (!empty($description)) {
            if($description === ' '){
                $sql .= " AND description ILIKE :description";
                $queryParams[":description"] = '%' . ($description) . '%';
            }
            else{
                if(empty(trim($description))){
                    $description = '';
                    $sql .= " AND description ILIKE :description";
                    $queryParams[":description"] = '%' . ($description) . '%';
                }
                $description = preg_replace('/^\s+|\s+$/', ' ', $description); // Заменяем пробелы в начале и конце на один пробел
                $description = preg_replace('/\s+/', ' ', $description); // Заменяем множественные пробелы на один
                $sql .= " AND description ILIKE :description OR description ILIKE :endDescr OR description ILIKE :startDescr";
                $queryParams[":description"] = '%' . ($description) . '%';
                $startDescription = ltrim($description);
                $queryParams[":startDescr"] = ($startDescription) . '%';
                $endDescription = rtrim($description);
                $queryParams[":endDescr"] = '%'. ($endDescription);
                
            }
        }

        $sql .= " ORDER BY genre_id";
    
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
        'host' => 'dpg-cute50vnoe9s73990f8g-a.oregon-postgres.render.com',
        'port' => '5432',
        'dbname' => 'bd_hsl9',
        'user' => 'bd_hsl9_user',
        'password' => '3cU6xyUqpiR6UrSnaRelGS3erErjzIHO'
    ];
    
    // Создаем экземпляр класса
    $crud = new GenresCRUD($dbConfig);
    
    while (true) {
        echo "\n1. Create\n2. Retrieve All\n3. Retrieve\n4. Update\n5. Delete\n6. Delete Many\n7. Search\n8. Exit\n";

       
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
                $results = "";
                $crud->_retrieve($results); 
                break;

            case '3':
                $id = readline("Enter ID: ");

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID. Please enter a valid integer.\n";
                    break;
                }
                $page = $crud->retrieve($id);
                if ($page) {
                    $crud->_retrieve($page); 
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
                
            case '7':
                $title = readline("Enter title to search (leave empty for no filter): ");
                $description = readline("Enter description to search (leave empty for no filter): ");
                    
                // Параметры пагинации
                $limit = readline("Enter number of results per page (default 5): ");

                if ($limit <= 0 || !filter_var($limit, FILTER_VALIDATE_INT)) { 
                    $limit = 5; 
                }
                    
                $offset = readline("Enter offset (default 0): ");

                if($offset < 0 || !filter_var($offset, FILTER_VALIDATE_INT)){
                    $offset = 0;
                }
                    
                try {
                    // Вызов метода nameSearch с двумя параметрами
                    $results = $crud->nameSearch($title, $description, $limit, $offset);
                    if (!empty($results)) {
                        $crud->_retrieve($results);
                    } else {
                        echo "No results found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
                

            case '8':
                exit("Exiting...\n");

            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();

?>