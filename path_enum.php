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

    
    // Получение всех узлов дерева
    private function fetchTree() {
        $stmt = $this->pdo->query("SELECT * FROM path_enum ORDER BY path");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Метод для отображения дерева
    private function printTree($nodes, $parentPath = '1/', $level = 0) {
        foreach ($nodes as $node) {
            // Проверяем, является ли текущий узел дочерним по отношению к родительскому пути
            if (strpos($node['path'], $parentPath) === 0 && $node['path'] !== $parentPath) {
                echo str_repeat('   ', $level) . "└── " . $node['title'] . " (ID: " . $node['id'] . ")\n";
                // Рекурсивный вызов для вывода потомков
                $this->printTree($nodes, $node['path'], $level + 1);
            }
        }
    }

    public function displayTree() {
        $tree = $this->fetchTree(); 
        $this->printTree($tree);
    }

    public function retrieve($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }


    // Добавление листа
    public function addLeaf($title, $parentPath) {
        // Генерируем новый путь
        $stmt = $this->pdo->query("SELECT nextval('path_enum_id_seq') AS next_id");
        $nextId = $stmt->fetchColumn();
        $newPath = $parentPath . $nextId . '/';
        
        $stmt = $this->pdo->prepare("INSERT INTO path_enum (title, path) VALUES (:title, :path)");
        $stmt->execute(['title' => $title, 'path' => $newPath]);
    }

    // Удаление листа
    public function deleteLeaf($id) {
        $stmt = $this->pdo->prepare("DELETE FROM path_enum WHERE id = :id AND NOT EXISTS (SELECT 1 FROM path_enum WHERE path LIKE :path)");
        $stmt->execute(['id' => $id, 'path' => "%/$id/%"]);
    }

    // Удаление поддерева
    public function deleteSubtree($id) {
        $stmt = $this->pdo->prepare("DELETE FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = :id) || '%'");
        $stmt->execute(['id' => $id]);
    }

    // Удаление узла без поддерева
    public function deleteNodeWithoutChildren($id) {
        $stmt = $this->pdo->prepare("DELETE FROM path_enum WHERE id = :id AND NOT EXISTS (SELECT 1 FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = :id) || '%')");
        $stmt->execute(['id' => $id]);
    }

    // Получение прямых потомков
    public function getDirectChildren($parentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = :parentId) || '%/'");
        $stmt->execute(['parentId' => $parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение прямого родителя
    public function getParent($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE id = (SELECT parent_id FROM path_enum WHERE id = :id)");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Получение всех потомков
    public function getAllDescendants($id) {
        return $this->fetchDescendants($id);
    }

    private function fetchDescendants($parentId) {
        $children = [];
        // Получаем всех прямых потомков
        $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = :parent_id) || '%/'");
        $stmt->execute(['parent_id' => $parentId]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Добавляем текущий узел к потомкам
            $children[] = $row;
            // Рекурсивно добавляем потомков текущего узла
            array_push($children, ...$this->fetchDescendants($row['id']));
        }
        
        return $children;
    }

    // Получение всех родителей
    public function getAllParents($id) {
        return $this->fetchParents($id);
    }

    private function fetchParents($childId) {
        $parents = [];
        
        while ($childId !== null) {
            // Получаем родителя текущего узла
            $stmt = $this->pdo->prepare("SELECT * FROM path_enum WHERE id = (SELECT parent_id FROM path_enum WHERE id = :child_id)");
            $stmt->execute(['child_id' => $childId]);
            if ($parent = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_unshift($parents, $parent);  // Добавляем в начало массива, чтобы сохранить порядок
                // Обновляем childId для следующей итерации
                $childId = $parent['id'];
            } else {
                break;  // Если родитель не найден, выходим из цикла
            }
        }
        
        return $parents;
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
                echo "Введите название: ";
                $title = readline();
                echo "Введите ID родителя: ";
                $parentId = readline();
                
                if (!filter_var($parentId, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID родителя.\n";
                    break;
                }
                
                try {
                    $crud->addLeaf($title, intval($parentId));
                    echo "Лист добавлен.\n";
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            case '3':
                echo "Введите ID листа для удаления: ";
                $leafIdToDelete = readline();
                
                if (!filter_var($leafIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }
                
                try {
                    $crud->deleteLeaf(intval($leafIdToDelete));
                    echo "Лист удален.\n";
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            case '4':
                // Удаление поддерева
                echo "Введите ID поддерева для удаления: ";
                $subtreeIdToDelete = readline();

                if (!filter_var($subtreeIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    if ($crud->retrieve(intval($subtreeIdToDelete))) { 
                        $crud->deleteSubtree(intval($subtreeIdToDelete));
                        echo "Поддерево удалено.\n";
                    } else {
                        echo "Узел не найден.\n";
                    }
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            case '5':
                // Удаление узла без поддерева
                echo "Введите ID узла для удаления: ";
                $nodeIdToDelete = readline();

                if (!filter_var($nodeIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $crud->deleteNodeWithoutChildren(intval($nodeIdToDelete));
                    echo "Узел удален.\n";
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            case '6':
                 // Получение прямых потомков
               echo "Введите ID родителя для получения прямых потомков: ";
               $parentIDForChildren = readline();

               if (!filter_var($parentIDForChildren, FILTER_VALIDATE_INT)) {
                   echo "Некорректный ID родителя.\n";
                   break;
               }

               try {
                   $childrenResults = $crud->getDirectChildren(intval($parentIDForChildren));
                   if (!empty($childrenResults)) {
                       printf("%-5s %-20s \n", "ID", "Название");
                       foreach ($childrenResults as $child) { 
                           printf("%-5s %-20s \n", $child['id'], $child['title']);
                       }
                   } else { 
                       echo "Дети не найдены.\n"; 
                   }
               } catch (Exception $e) { 
                   echo "Ошибка: ". $e->getMessage() . "\n"; 
               }
               break;
            case '7':
                // Получение прямого родителя
               echo "Введите ID для получения родителя: ";
               $nodeIDForParent= readline();

               if (!filter_var($nodeIDForParent, FILTER_VALIDATE_INT)) { 
                   echo "Некорректный ID узла.\n"; 
                   break; 
               }

               try { 
                   $parentResult= $crud->getParent(intval($nodeIDForParent)); 
                   if ($parentResult){ 
                       printf("%-5s %-20s \n", "ID", "Название"); 
                       printf("%-5s %-20s \n", $parentResult['id'], $parentResult['title']); 
                   } else { 
                       echo "Родитель не найден.\n"; 
                   } 
               } catch (Exception $e){ 
                   echo "Ошибка: ". $e->getMessage() . "\n"; 
               }
               break;
            case '8':
                /// Получение всех потомков
               echo "Введите ID для получения всех потомков: ";
               $nodeIDForDescendants= readline();

               if (!filter_var($nodeIDForDescendants, FILTER_VALIDATE_INT)){ 
                   echo "Некорректный ID узла.\n"; 
                   break; 
               }

               try { 
                   $descendantsResults= $crud->getAllDescendants(intval($nodeIDForDescendants)); 
                   if (!empty($descendantsResults)){ 
                       printf("%-5s %-20s \n", "ID", "Название"); 
                       foreach ($descendantsResults as $descendant){ 
                           printf("%-5s %-20s \n", $descendant['id'], $descendant['title']); 
                       } 
                   } else { 
                       echo "Потомки не найдены.\n"; 
                   } 
               } catch (Exception $e){ 
                   echo "Ошибка: ". $e->getMessage() . "\n"; 
               }
               break;
            case '9':
                // Получение всех родителей
               echo "Введите ID для получения всех родителей: ";
               $nodeIDForParents= readline();

               if (!filter_var($nodeIDForParents, FILTER_VALIDATE_INT)){ 
                   echo "Некорректный ID узла.\n"; 
                   break; 
               }

               try { 
                   $parentsResults= $crud->getAllParents(intval($nodeIDForParents)); 

                   if (!empty($parentsResults)){ 
                       printf("%-5s %-20s \n", "ID", "Название"); 

                       foreach ($parentsResults as $parent){  
                           printf("%-5s %-20s \n", $parent['id'], $parent['title']);  
                       }  
                   } else {  
                       echo "Родители не найдены.\n";  
                   }  
               } catch (Exception $e){  
                   echo "Ошибка: ". $e->getMessage() . "\n";  
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


/*Добавление листа:
INSERT INTO path_enum (title) VALUES ('Новый продукт');
UPDATE path_enum SET path = (SELECT path FROM path_enum WHERE id = parent_id_value) || LAST_INSERT_ID() || '/' WHERE id = LAST_INSERT_ID();

Удаление узла:
DELETE FROM path_enum WHERE id = node_id_value;
UPDATE path_enum SET path = REPLACE(path, CONCAT('/' , node_to_delete_path), '/') WHERE path LIKE CONCAT(node_to_delete_path, '%');

Удаление поддерева:
DELETE FROM path_enum WHERE path LIKE (SELECT path || '%' FROM path_enum WHERE id = subtree_root_id);

Получение прямых потомков:
SELECT * FROM path_enum WHERE path LIKE (SELECT path FROM path_enum WHERE id = parent_id_value) || '%';

Получение прямого родителя:
SELECT * FROM path_enum WHERE path = SUBSTRING_INDEX((SELECT path FROM path_enum WHERE id = child_id_value), '/', -2);

Получение всех потомков:
SELECT * FROM path_enum WHERE path LIKE (SELECT path || '%' FROM path_enum WHERE id = root_node_id);

Получение всех родителей:
SELECT * FROM path_enum WHERE (SELECT path FROM path_enum WHERE id = child_node_id_value) LIKE CONCAT(path, '%');

*/