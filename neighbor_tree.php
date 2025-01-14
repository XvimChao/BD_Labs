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

    

    // Добавление листа
    public function addLeaf($title, $parentId) {
        $stmt = $this->pdo->prepare("INSERT INTO neighbor_tree (title, parent_id) VALUES (:title, :parent_id)");
        $stmt->execute(['title' => $title, 'parent_id' => $parentId]);
    }

    // Удаление листа
    public function deleteLeaf($id) {
        $stmt = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id AND NOT EXISTS (SELECT 1 FROM neighbor_tree WHERE parent_id = :id)");
        $stmt->execute(['id' => $id]);
    }

    // Удаление поддерева
    public function deleteSubtree($id) {
        $stmt = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id OR parent_id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Удаление узла без поддерева
    public function deleteNodeWithoutChildren($id) {
        $stmt = $this->pdo->prepare("DELETE FROM neighbor_tree WHERE id = :id AND NOT EXISTS (SELECT 1 FROM neighbor_tree WHERE parent_id = :id)");
        $stmt->execute(['id' => $id]);
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

// Основной код для взаимодействия с пользователем
function main() {
    
    // Конфигурация базы данных
    /*$dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'water7op'
    ];
    */
    
    // Пример конфигурации базы данных
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
        echo "\n1. Display Tree\n2. Add Leaf\n3. Delete Leaf\n4. Delete Subtree\n5. Delete Node Without Children\n6. Get Direct Children\n7. Get Parent\n8. Get All Descendants\n9. Get All Parents\n10. Exit\n";

        $choice = readline("Choose an option: ");
        
        switch ($choice) {
            case '1':
                try {
                    $crud->displayTree();  // Используем оператор '->' для вызова метода
                } catch (Exception $e) {  
                    echo "Error: " . $e->getMessage() . "\n";  // Используем '->' для получения сообщения об ошибке
                }
                break;
            case '2':
                // Добавление листа
                echo "Enter title: ";
                $title = readline();
                echo "Enter parent ID: ";
                $parentId = readline();
                
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
                    echo "Error: " . $e.getMessage() . "\n";
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
                    if ($crud->retrieve(intval($subtreeIdToDelete))) {  // Проверка существования узла
                        echo "Subtree deleted.\n";
                        crud.deleteSubtree(intval(subtreeIdToDelete));
                    } else {
                        echo "Node not found.\n";
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e.getMessage() . "\n";
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
                    $crud.deleteNodeWithoutChildren(intval($nodeIdToDelete));
                    echo "Node deleted.\n";
                } catch (Exception $e) {
                    echo "Error: " . $e.getMessage() . "\n";
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
                   $childrenResults = crud.getDirectChildren(intval($parentIDForChildren));
                   if (!empty($childrenResults)) {
                       printf("%-5s %-20s \n", "ID", "Title");
                       foreach ($childrenResults as $child) { 
                           printf("%-5s %-20s \n", $child['id'], $child['title']);
                       }
                   } else { 
                       echo "No children found.\n"; 
                   }
               } catch (Exception $e) { 
                   echo "Error: ". $e.getMessage() . "\n"; 
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
                   $parentResult= $crud.getParent(intval($nodeIDForParent)); 
                   if ($parentResult){ 
                       printf("%-5s %-20s \n", "ID", "Title"); 
                       printf("%-5s %-20s \n", $parentResult['id'], $parentResult['title']); 
                   } else { 
                       echo "Parent not found.\n"; 
                   } 
               } catch (Exception $e){ 
                   echo "Error: ". $e.getMessage() . "\n"; 
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
                   $descendantsResults= $crud.getAllDescendants(intval($nodeIDForDescendants)); 
                   if (!empty($descendantsResults)){ 
                       printf("%-5s %-20s \n", "ID", "Title"); 
                       foreach ($descendantsResults as $descendant){ 
                           printf("%-5s %-20s \n", $descendant['id'], $descendant['title']); 
                       } 
                   } else { 
                       echo "No descendants found.\n"; 
                   } 
               } catch (Exception $e){ 
                   echo "Error: ". $e.getMessage() . "\n"; 
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
                   $parentsResults= $rud.getAllParents(intval($nodeIDForParents)); 

                   if (!empty($parentsResults)){ 
                       printf("%-5s %-20s \n", "ID", "Title"); 

                       foreach ($parentsResults as $parent){  
                           printf("%-5s %-20s \n", $arent['id'], $parent['title']);  
                       }  
                   } else {  
                       echo "No parents found.\n";  
                   }  
               } catch (Exception $e){  
                   echo "Error: ". e.getMessage() . "\n";  
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
/*Получение прямого родителя:
SELECT * FROM neighbor_tree WHERE id = (SELECT parent_id FROM neighbor_tree WHERE id = 1);

Получение всех родителей:
WITH RECURSIVE parents AS (
    SELECT * FROM neighbor_tree WHERE id = child_node_id_value
    UNION ALL
    SELECT nt.* FROM neighbor_tree nt INNER JOIN parents p ON nt.id = p.parent_id
)
SELECT * FROM parents;

Получение прямых потомков:
SELECT * FROM neighbor_tree WHERE parent_id = 1;

Получение всех потомков:
WITH RECURSIVE descendants AS (
    SELECT * FROM neighbor_tree WHERE id = root_node_id_value
    UNION ALL
    SELECT nt.* FROM neighbor_tree nt INNER JOIN descendants d ON nt.parent_id = d.id
)
SELECT * FROM descendants;

Удаление узла без поддерева:
DELETE FROM neighbor_tree WHERE id = node_id_value;

Удаление поддерева:
DELETE FROM neighbor_tree WHERE id IN (SELECT id FROM neighbor_tree WHERE parent_id = subtree_root_id);

Добавление листа:
INSERT INTO neighbor_tree (title, parent_id) VALUES ('Новый продукт', parent_id_value);

Удаление листа:
DELETE FROM neighbor_tree WHERE id = leaf_id_value;


*/


