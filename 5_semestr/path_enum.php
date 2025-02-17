<?php
class PathEnumCRUD {
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

    public function _retrieve($results) {
        $header = [
            'id' => 'ID',
            'title' => 'Title',
            'path' => "Path"
        ];
        $fill_sym = ' ';
    
    
        $pages = $results;
        
    
        $width_columns = [];
        foreach ($header as $head_key => $head) {
            $width_columns[$head_key] = mb_strlen(trim($head));
        }
        
        // Определяем максимальные длины для каждого столбца
        foreach ($pages as $page) {
            foreach ($page as $column_key => $column_value) {
                $column_value_str = (string) ($column_value ?? '');
                if (isset($width_columns[$column_key])) {
                    $width_columns[$column_key] = max($width_columns[$column_key], mb_strlen(trim($column_value_str)));
                }
            }
        }
    
        // Формируем заголовок и разделитель
        $head_str = '';
        $divisor_str = '';
        foreach ($header as $head_key => $head) {
            $head_str .= $head . str_repeat($fill_sym, $width_columns[$head_key] - mb_strlen($head) + 2);
            $divisor_str .= str_repeat("-", $width_columns[$head_key]);
        }
    
        echo $head_str . "\n";
        echo $divisor_str . "\n";
    
        // Выводим данные страниц
        foreach ($pages as $page) {
            $page_str = '';
            foreach($page as $column_key => $column_value){
                $value_to_print = (string) ($page[$column_key] ?? '');
                $page_str .= $value_to_print.str_repeat($fill_sym, $width_columns[$column_key]-mb_strlen($value_to_print)+2);
            }
            echo $page_str."\n";
        }
    }

    public function retrieve($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }


    // Добавление листа
    public function addLeaf($title, $parentId) {
        $trimmedTitle = $this->mb_ucfirst(trim($title));
        $trimmedTitle = preg_replace('/\s+/', ' ', $trimmedTitle);
        $titleLenght = mb_strlen($trimmedTitle);

        if($titleLenght <= 1){
            throw new InvalidArgumentException("Incorrect title.");
        }

        if (preg_match('/[^a-zA-Zа-яА-ЯёЁ0-9 ,-]/u', $trimmedTitle)) {
            throw new InvalidArgumentException("Incorrect title.");
        }
        // Проверка на существование названия
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM path_enum WHERE (LOWER(title) = LOWER(:title))");
        $stmt->execute(['title' => $trimmedTitle]);
        $count = $stmt->fetchColumn();

       
        if ($count > 0) {
            throw new InvalidArgumentException("Title '{$trimmedTitle}' already exist.");
        }

        $parentCheckStmt = $this->pdo->prepare("SELECT COUNT(*) FROM path_enum WHERE id = :parentId");
        $parentCheckStmt->execute(['parentId' => $parentId]);
        $parentExists = $parentCheckStmt->fetchColumn();

        if ($parentExists == 0) {
            throw new InvalidArgumentException("Parent ID does not exist.");
        }

        $stmt = $this->pdo->query("SELECT nextval('path_enum_id_seq') AS next_id");
        $nextId = $stmt->fetchColumn();

        $parentPathStmt = $this->pdo->prepare("SELECT path FROM path_enum WHERE id = :parentId");
        $parentPathStmt->execute(['parentId' => $parentId]);
        $parentPath = rtrim($parentPathStmt->fetchColumn(), '/') . '/';
        
        // Создаем новый путь
        $newPath = "{$parentPath}{$nextId}/";
        
        // Вставляем новый узел
        $stmt = $this->pdo->prepare("INSERT INTO path_enum (title, path) VALUES (:title, :path)");
        $stmt->execute(['title' => $trimmedTitle, 'path' => $newPath]);
    }

    // Удаление листа
    public function deleteLeaf($id) {
        // Проверка, является ли узел листом
        $childCheckStmt = $this->pdo->prepare("SELECT COUNT(*) FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = :id) || '%/'");
        $childCheckStmt->execute(['id' => $id]);
        $hasChildren = $childCheckStmt->fetchColumn();
    
        if ($hasChildren > 0) {
            throw new InvalidArgumentException("Delete is not possible because the node is a parent.");
        }
    
        $deleteStmt = $this->pdo->prepare("DELETE FROM path_enum WHERE id = :id");
        return $deleteStmt->execute(['id' => $id]);
    }

    // Удаление поддерева
    public function deleteSubtree($id) {
        // Удаляем все узлы поддерева
        return $this->pdo->prepare("DELETE FROM path_enum WHERE path LIKE (SELECT path || '%' FROM path_enum WHERE id = :id)")->execute(['id' => $id]);
    }

    // Удаление узла без поддерева
    public function deleteNodeWithoutChildren($id) {
        $stmtCheck = $this->pdo->prepare("SELECT path FROM path_enum WHERE id = :id");
        $stmtCheck->execute(['id' => $id]);
        $node = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $nodePath = $node['path'];

        $stmtChildCheck = $this->pdo->prepare("SELECT COUNT(*) FROM path_enum WHERE path LIKE :path");
        $stmtChildCheck->execute(['path' => $nodePath . '%']);
        $hasChildren = $stmtChildCheck->fetchColumn();

        if ($hasChildren > 0) {
            // Если у узла есть потомки, обновляем их пути
            $stmtUpdateChildren = $this->pdo->prepare("UPDATE path_enum SET path = REPLACE(path, :oldPath, :newPath) WHERE path LIKE :oldPath");

            $newPath = substr($nodePath, 0, strrpos($nodePath, '/'));
            $stmtUpdateChildren->execute(['oldPath' => $nodePath . '%', 'newPath' => $newPath . '/']);
        }

        $stmtDelete = $this->pdo->prepare("DELETE FROM path_enum WHERE id = :id");
        return $stmtDelete->execute(['id' => $id]);
    }

    // Получение прямых потомков 
    public function getDirectChildren($id) {
        $parentPathStmt = $this->pdo->prepare("SELECT path FROM path_enum WHERE id = :id");
        $parentPathStmt->execute(['id' => $id]);
        $parentPath = $parentPathStmt->fetchColumn();
    
        
        if ($parentPath === false) {
            return []; 
        }

        $query = "
            SELECT * FROM path_enum 
            WHERE path LIKE :pathPattern 
            AND path != :currentPath
        ";
    
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'pathPattern' => $parentPath . '%',
            'currentPath' => $parentPath
        ]);
    
        $children = array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), function($child) use ($parentPath) {
            return substr_count($child['path'], '/') === substr_count($parentPath, '/') + 1;
        });
    
        return array_values($children); // Возвращаем массив с прямыми потомками
    }

    // Получение прямого родителя 
    public function getParent($id) { 
        $stmt = $this->pdo->prepare("SELECT path FROM path_enum WHERE id = :id"); 
        $stmt->execute(['id' => $id]); 
        $node = $stmt->fetch(PDO::FETCH_ASSOC); 

        if ($node) { 
            // Получаем путь текущего узла 
            $nodePath = rtrim($node['path'], '/'); 
            
            // Находим индекс последнего слэша в пути 
            $lastSlashPosition = strrpos($nodePath, '/'); 

            if ($lastSlashPosition !== false) { 
                // Путь родителя будет до последнего слэша 
                $parentPath = substr($nodePath, 0, $lastSlashPosition + 1); 
                // Получаем родителя по пути 
                $parentStmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE path = :parentPath"); 
                $parentStmt->execute(['parentPath' => $parentPath]); 
                return $parentStmt->fetchAll(PDO::FETCH_ASSOC); 
            } 
        } 

        return null;
    } 

    // Получение всех потомков
    public function getAllDescendants($id) {
        $descendants = $this->fetchDescendants($id);
        if (!empty($descendants)) {
            $this->printTree($descendants);
        } else {
            throw new InvalidArgumentException("No descendants found.");
        }
    }

    public function fetchDescendants($id) {
        $query = " 
            SELECT * FROM path_enum 
            WHERE path LIKE (
            SELECT path || '%' FROM path_enum WHERE id = :id
            )
        "; 
        
        $stmt = $this->pdo->prepare($query); 
        $stmt->execute(['id' => $id]); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    // Получение всех родителей 
    public function getAllParents($id) { 
        $parents = $this->fetchParents($id);

        if (!empty($parents)) {
            // Переворачиваем массив, чтобы показать родителей от корня к текущему узлу
            $this->printTree($parents);
        } else {
            throw new InvalidArgumentException("No parents found.");
        }
    } 

    private function fetchParents($id) {
        $query = " 
            SELECT * FROM path_enum 
            WHERE (
            SELECT path FROM path_enum WHERE id = :id
            ) LIKE path || '%'
        "; 
        
        $stmt = $this->pdo->prepare($query); 
        $stmt->execute(['id' => $id]); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    

    // Получение всех узлов дерева
    private function fetchTree() {
        $stmt = $this->pdo->query("SELECT * FROM path_enum");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Метод для отображения дерева
    private function printTree($nodes, $parentPath = '', $level = 0, &$printedNodes = []) {
        foreach ($nodes as $node) {
            
            if (strpos($node['path'], $parentPath) === 0 && $node['path'] !== $parentPath) {
                // Проверяем, был ли узел уже выведен
                if (!in_array($node['id'], $printedNodes)) {
                    echo str_repeat('---', $level) . ">" . $node['title'] . " (ID: " . $node['id'] . ")\n";
                    $printedNodes[] = $node['id'];
                    $this->printTree($nodes, $node['path'], $level + 1, $printedNodes);
                }
            }
        }
    }
    
    public function displayTree() {
        $tree = $this->fetchTree(); 
        $this->printTree($tree);
    }


}

// Основная функция программы
function main() {
   
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'water7op'
    ];
    
    
   /* 
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'postgres',
        'user' => 'postgres',
        'password' => 'ardin2004'
    ];
    */
    $crud = new PathEnumCRUD($dbConfig);
   
   while (true) {
        echo "\nВыберите действие:\n";
        echo "1. Вывести дерево\n";
        echo "2. Добавить лист\n";
        echo "3. Удалить лист\n";
        echo "4. Удалить поддерево\n";
        echo "5. Удалить узел без поддерева\n";
        echo "6. Вывести прямых потомков\n";
        echo "7. Вывести прямого родителя\n";
        echo "8. Вывести всех потомков\n";
        echo "9. Вывести всех родителей\n";
        echo "10. Выйти\n";

        $choice = readline("Choose an option: ");
        
        switch ($choice) {
            case '1':
                try {
                    $crud->displayTree();
                } catch (Exception $e) {  
                    echo "Error: " . $e->getMessage() . "\n";  
                }
                break;
            case '2':
                $title = readline("Enter title: ");
                $parentId = readline("Enter parent ID: ");
                
                if (!filter_var($parentId, FILTER_VALIDATE_INT)) {
                    echo "Invalid Parent ID.\n";
                    break;
                }
                
                try {
                    $crud->addLeaf($title, intval($parentId));
                    echo "Leaf added.\n";
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
            case '3':
                echo "Enter ID of the leaf to delete: ";
                $leafIdToDelete = readline();
                
                if (!filter_var($leafIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID.\n";
                    break;
                }
                
                try {
                    if ($crud->retrieve(intval($leafIdToDelete))){
                        $crud->deleteLeaf(intval($leafIdToDelete));
                        echo "Leaf deleted.\n";
                    }
                    else{
                        echo "Leaf not fount\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
            case '4':
                // Удаление поддерева
                echo "Enter ID of the subtree to delete: ";
                $subtreeIdToDelete = readline();

                if (!filter_var($subtreeIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID.\n";
                    break;
                }

                try {
                    if ($crud->retrieve(intval($subtreeIdToDelete))) { 
                        $crud->deleteSubtree(intval($subtreeIdToDelete));
                        echo "Subtree deleted.\n";
                    } else {
                        echo "Node not found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
            case '5':
                // Удаление узла без поддерева
                echo "Enter ID of the node to delete: ";
                $nodeIdToDelete = readline();

                if (!filter_var($nodeIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID.\n";
                    break;
                }

                try {
                    if ($crud->retrieve(intval($nodeIdToDelete))) { 
                        $crud->deleteNodeWithoutChildren(intval($nodeIdToDelete));
                        echo "Node deleted.\n";
                    }
                    else{
                        echo "Node not found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
            case '6':
                 // Получение прямых потомков
               echo "Enter Parent ID to get direct children: ";
               $parentIDForChildren = readline();

               if (!filter_var($parentIDForChildren, FILTER_VALIDATE_INT)) {
                   echo "Invalid Parent ID.\n";
                   break;
               }

               try {
                    $childrenResults = $crud->getDirectChildren(intval($parentIDForChildren));
                    if (!empty($childrenResults)) {
                        $crud->_retrieve($childrenResults);
                    }
                    else{
                        throw new InvalidArgumentException ("No children found."); 
                    }
               } catch (Exception $e) { 
                   echo "Error: ". $e->getMessage() . "\n"; 
               }
               break;
            case '7':
                // Получение прямого родителя
               echo "Enter ID to get parent: ";
               $nodeIDForParent= readline();

               if (!filter_var($nodeIDForParent, FILTER_VALIDATE_INT)) { 
                   echo "Invalid Node ID.\n"; 
                   break; 
               }

               try { 
                    $parentResult= $crud->getParent(intval($nodeIDForParent)); 
                    if ($parentResult){ 
                        $crud->_retrieve($parentResult);
                    } else { 
                        throw new InvalidArgumentException ("Parent not found."); 
                    } 
               } catch (Exception $e){ 
                   echo "Error: ". $e->getMessage() . "\n"; 
               }
               break;
            case '8':
                /// Получение всех потомков
               echo "Enter ID to get all descendants: ";
               $nodeIDForDescendants= readline();

               if (!filter_var($nodeIDForDescendants, FILTER_VALIDATE_INT)){ 
                   echo "Invalid Node ID.\n"; 
                   break; 
               }

               try { 
                   $crud->getAllDescendants(intval($nodeIDForDescendants)); 
                   
               } catch (Exception $e){ 
                   echo "Error: ". $e->getMessage() . "\n"; 
               }
               break;
            case '9':
                // Получение всех родителей
               echo "Enter ID to get all parents: ";
               $nodeIDForParents= readline();

               if (!filter_var($nodeIDForParents, FILTER_VALIDATE_INT)){ 
                   echo "Invalid Node ID.\n"; 
                   break; 
               }

               try { 
                   $crud->getAllParents(intval($nodeIDForParents)); 
               } catch (Exception $e){  
                   echo "Error: ". $e->getMessage() . "\n";  
               }  
              break;
            case '10':
                exit("Exiting...\n");
            default:
                echo "Invalid option. Please try again.\n";
        }
    }
}

main();