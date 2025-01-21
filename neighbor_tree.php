<?php
class TreeCRUD {
    private $pdo;

    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO("pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", $dbConfig['user'], $dbConfig['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Could not connect to the database: " . $e->getMessage());
        }
    }

    public function _retrieve($results) {
        $header = [
            'id' => 'ID',
            'title' => 'Title',
            'parent_id' => "Parent_ID"
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
        $stmt = $this->pdo->prepare("SELECT * FROM neighbor_tree WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; // Возвращает true, если узел существует
    }

    // Добавление листа
    public function addLeaf($title, $parentId) {
        $trimmedTitle = trim($title);

        if (preg_match('/[^a-zA-Zа-яА-ЯёЁ0-9 ,-]/u', $trimmedTitle)) {
            throw new InvalidArgumentException("Incorrect title.");
        }
        // Проверка на существование названия
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM neighbor_tree WHERE (title = :title)");
        $stmt->execute(['title' => $trimmedTitle]);
        $count = $stmt->fetchColumn();

       
        if ($count > 0) {
            throw new InvalidArgumentException("Title '{$trimmedTitle}' already exist.");
        }
        $stmt = $this->pdo->prepare("INSERT INTO neighbor_tree (title, parent_id) VALUES (:title, :parent_id)");
        $stmt->execute(['title' => $trimmedTitle, 'parent_id' => $parentId]);
    }

    // Удаление листа
    public function deleteLeaf($id) {
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM neighbor_tree WHERE parent_id = :id");
        $stmtCheck->execute(['id' => $id]);
        $isParent = $stmtCheck->fetchColumn() > 0;

        if ($isParent) {
            throw new InvalidArgumentException("Delete is not possible because the node is a parent.");
        }

        // Если узел не является родителем, выполняем удаление
        $stmt = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Удаление поддерева
    public function deleteSubtree($id) {
        $stmt = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id OR parent_id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Удаление узла без поддерева
    public function deleteNodeWithoutChildren($id) {
        $stmtUpdateChildren = $this->pdo->prepare("UPDATE neighbor_tree SET parent_id = (SELECT parent_id FROM neighbor_tree WHERE id = :id) WHERE parent_id = :id");
        $stmtUpdateChildren->execute(['id' => $id]);

        $stmtDelete = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id");
        $stmtDelete->execute(['id' => $id]);
    }

    // Получение прямых потомков
    public function getDirectChildren($parentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM neighbor_tree WHERE parent_id = :parent_id");
        $stmt->execute(['parent_id' => $parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение прямого родителя
    public function getParent($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM neighbor_tree WHERE id = (SELECT parent_id FROM neighbor_tree WHERE id = :id)");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение всех потомков
    public function getAllDescendants($id) {
        // Рекурсивная функция для получения всех потомков
        return $this->fetchDescendants($id);
    }

    private function fetchDescendants($parentId) {
        $children = [];
        $stmt = $this->pdo->prepare("SELECT * FROM neighbor_tree WHERE parent_id = :parent_id");
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
            $stmt = $this->pdo->prepare("SELECT * FROM neighbor_tree WHERE id = (SELECT parent_id FROM neighbor_tree WHERE id = :child_id)");
            $stmt->execute(['child_id' => $childId]);
            if ($parent = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_unshift($parents, $parent);  // Добавляем в начало массива, чтобы сохранить порядок
                // Обновляем childId для следующей итерации
                $childId = $parent['id'];
            } else {
                break;
            }
        }
        
        return $parents;
    }

    // Получение всех узлов дерева
    private function fetchTree() {
        $stmt = $this->pdo->query("SELECT * FROM neighbor_tree");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Рекурсивная функция для печати дерева
    private function printTree($nodes, $parentId = null, $level = 0) {
        foreach ($nodes as $node) {
            if ($node['parent_id'] === $parentId) {
                echo str_repeat('---', $level) . "> " . $node['title'] . " (ID: " . $node['id'] . ")\n";
                // Рекурсивный вызов для вывода потомков
                $this->printTree($nodes, $node['id'], $level + 1);
            }
        }
    }

    // Метод для отображения всего дерева
    public function displayTree() {
        $tree = $this->fetchTree();
        $this->printTree($tree);
    }
}

function main() {
 
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
    $crud = new TreeCRUD($dbConfig);
    
    while (true) {
        echo "\n1. Вывести дерево\n2. Добавить лист\n3. Удалить лист\n4. Удалить поддерево\n5. Удалить узел без поддерева\n6. Вывести прямых потомков\n7. Вывести прямого родителя\n8. Вывести всех потомков\n9. Вывести всех родителей\n10. Выйти\n";

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
                // Добавление листа
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
                // Удаление листа
                echo "Enter ID of the leaf to delete: ";
                $leafIdToDelete = readline();
                
                if (!filter_var($leafIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Invalid ID.\n";
                    break;
                }
                
                try {
                    $crud->deleteLeaf(intval($leafIdToDelete));
                    echo "Leaf deleted.\n";
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
                        echo "Subtree deleted.\n";
                        $crud->deleteSubtree(intval($subtreeIdToDelete));
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
                    $crud->deleteNodeWithoutChildren(intval($nodeIdToDelete));
                    echo "Node deleted.\n";
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
               // Получение всех потомков
               echo "Enter ID to get all descendants: ";
               $nodeIDForDescendants= readline();

               if (!filter_var($nodeIDForDescendants, FILTER_VALIDATE_INT)){ 
                   echo "Invalid Node ID.\n"; 
                   break; 
               }

               try { 
                   $descendantsResults= $crud->getAllDescendants(intval($nodeIDForDescendants)); 
                   if (!empty($descendantsResults)){ 
                        $crud->_retrieve($descendantsResults);
                   } else { 
                       throw new InvalidArgumentException ("No descendants found."); 
                   } 
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
                   $parentsResults= $crud->getAllParents(intval($nodeIDForParents)); 

                   if (!empty($parentsResults)){ 
                        $crud->_retrieve($parentsResults);  
                   } else {  
                       throw new InvalidArgumentException ("No parents found.");  
                   }  
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

