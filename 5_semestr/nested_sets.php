<?php
class NestedSetsCRUD {
    private $pdo;

    public function __construct($dbConfig) {
        try {
            $this->pdo = new PDO(
                "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}", 
                $dbConfig['user'], 
                $dbConfig['password']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Не удалось подключиться к базе данных: " . $e->getMessage());
        }
    }

    public function _retrieve($results) {
        $header = [
            'id' => 'ID',
            'title' => 'Title',
            'lft' => "Left",
            'rgt' => "Right"
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
    // Нормализация заголовка
    private function normalizeTitle($title) {
        $trimmedTitle = mb_convert_case(trim($title), MB_CASE_TITLE, 'UTF-8');
        $trimmedTitle = preg_replace('/\s+/', ' ', $trimmedTitle);
        
        if (strlen($trimmedTitle) <= 1) {
            throw new InvalidArgumentException("Некорректное название.");
        }

        if (preg_match('/[^a-zA-Zа-яА-ЯёЁ0-9 ,-]/u', $trimmedTitle)) {
            throw new InvalidArgumentException("Некорректное название.");
        }

        return $trimmedTitle;
    }

    // Добавление листа
    public function addLeaf($title, $parentId) {
        $parent = $this->getNodeById($parentId);
        if (!$parent) {
            throw new InvalidArgumentException("Родительский узел не найден");
        }

        $this->pdo->beginTransaction();
        try {
            // Обновляем существующие узлы
            $updateStmt = $this->pdo->prepare("
                UPDATE nested_sets 
                SET lft = CASE WHEN lft >= :parent_rgt THEN lft + 2 ELSE lft END,
                    rgt = rgt + 2
                WHERE rgt >= :parent_rgt
            ");
            $updateStmt->execute(['parent_rgt' => $parent['rgt']]);

            // Вставляем новый лист
            $insertStmt = $this->pdo->prepare("
                INSERT INTO nested_sets (title, lft, rgt) 
                VALUES (:title, :lft, :rgt)
            ");
            $insertStmt->execute([
                'title' => $title,
                'lft' => $parent['rgt'],
                'rgt' => $parent['rgt'] + 1
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Удаление листа
    public function deleteLeaf($id) {
        $node = $this->getNodeById($id);
        if (!$node) {
            throw new InvalidArgumentException("Узел не найден");
        }

        // Проверяем, что узел является листом
        if ($node['rgt'] - $node['lft'] > 1) {
            throw new InvalidArgumentException("Невозможно удалить узел с потомками");
        }

        $this->pdo->beginTransaction();
        try {
            // Удаляем узел
            $deleteStmt = $this->pdo->prepare("DELETE FROM nested_sets WHERE id = :id");
            $deleteStmt->execute(['id' => $id]);

            // Обновляем left и right значения
            $updateStmt = $this->pdo->prepare("
                UPDATE nested_sets 
                SET lft = CASE WHEN lft > :node_lft THEN lft - 2 ELSE lft END,
                    rgt = rgt - 2
                WHERE rgt > :node_rgt
            ");
            $updateStmt->execute([
                'node_lft' => $node['lft'],
                'node_rgt' => $node['rgt']
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    // Удаление поддерева
    public function deleteSubtree($id) {
        $node = $this->getNodeById($id);
        if (!$node) {
            throw new InvalidArgumentException("Узел не найден");
        }

        $this->pdo->beginTransaction();
        try {
            // Удаляем поддерево
            $deleteStmt = $this->pdo->prepare("
                DELETE FROM nested_sets 
                WHERE lft BETWEEN :node_lft AND :node_rgt
            ");
            $deleteStmt->execute([
                'node_lft' => $node['lft'],
                'node_rgt' => $node['rgt']
            ]);

            // Обновляем left и right значения
            $width = $node['rgt'] - $node['lft'] + 1;
            $updateStmt = $this->pdo->prepare("
                UPDATE nested_sets 
                SET lft = CASE WHEN lft > :node_rgt THEN lft - :width ELSE lft END,
                    rgt = rgt - :width
                WHERE rgt > :node_rgt
            ");
            $updateStmt->execute([
                'node_rgt' => $node['rgt'],
                'width' => $width
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Удаление узла без поддерева
    public function deleteNodeWithoutChildren($id) {
        $node = $this->getNodeById($id);
        if (!$node) {
            throw new InvalidArgumentException("Узел не найден");
        }

        // Проверяем, что узел является листом
        if ($node['rgt'] - $node['lft'] > 1) {
            throw new InvalidArgumentException("Невозможно удалить узел с потомками");
        }

        $this->pdo->beginTransaction();
        try {
            // Находим родителя
            $parentStmt = $this->pdo->prepare("
                SELECT parent.*
                FROM nested_sets AS h
                INNER JOIN nested_sets AS parent
                ON h.lft BETWEEN parent.lft AND parent.rgt
                LEFT JOIN nested_sets AS in_between ON 
                (h.lft BETWEEN in_between.lft AND in_between.rgt) AND 
                (in_between.lft BETWEEN parent.lft AND parent.rgt)
                WHERE h.id = :id AND in_between.id IS NULL
                ORDER BY parent.lft DESC
                LIMIT 1
            ");
            $parentStmt->execute(['id' => $id]);
            $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);

            // Удаляем узел
            $deleteStmt = $this->pdo->prepare("DELETE FROM nested_sets WHERE id = :id");
            $deleteStmt->execute(['id' => $id]);

            // Обновляем left и right значения
            $updateStmt = $this->pdo->prepare("
                UPDATE nested_sets 
                SET lft = CASE WHEN lft > :node_lft THEN lft - 2 ELSE lft END,
                    rgt = rgt - 2
                WHERE rgt > :node_rgt
            ");
            $updateStmt->execute([
                'node_lft' => $node['lft'],
                'node_rgt' => $node['rgt']
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Получение прямых потомков
    public function getDirectChildren($parentId) {
        $parent = $this->getNodeById($parentId);
        if (!$parent) {
            throw new InvalidArgumentException("Родительский узел не найден");
        }
    
        $stmt = $this->pdo->prepare("
            SELECT child.*
            FROM nested_sets AS h
            INNER JOIN nested_sets AS child
            ON child.lft BETWEEN h.lft AND h.rgt
            LEFT JOIN nested_sets AS in_between ON 
            (child.lft BETWEEN in_between.lft AND in_between.rgt) AND 
            (in_between.lft BETWEEN child.lft AND child.rgt)
            WHERE h.id = :parent_id AND in_between.id IS NULL
            ORDER BY child.lft
        ");
        $stmt->execute(['parent_id' => $parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение прямого родителя
    public function getDirectParent($id) {
        $stmt = $this->pdo->prepare("
            SELECT parent.*
            FROM nested_sets AS h
            INNER JOIN nested_sets AS parent
            ON h.lft BETWEEN parent.lft AND parent.rgt
            LEFT JOIN nested_sets AS in_between ON 
            (h.lft BETWEEN in_between.lft AND in_between.rgt) AND 
            (in_between.lft BETWEEN parent.lft AND parent.rgt)
            WHERE h.id = :id AND in_between.id IS NULL
            ORDER BY parent.lft DESC
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение всех потомков
    public function getAllDescendants($id) {
        $node = $this->getNodeById($id);
        if (!$node) {
            throw new InvalidArgumentException("Узел не найден.");
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM nested_sets 
            WHERE lft > :left AND rgt < :right 
            ORDER BY lft
        ");
        $stmt->execute([
            'left' => $node['lft'],
            'right' => $node['rgt']
        ]);
        $descendants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($descendants)) {
            throw new InvalidArgumentException("У узла нет потомков.");
        }

        return $descendants;
    }

    // Получение всех родителей
    public function getAllParents($id) {
        $node = $this->getNodeById($id);
        if (!$node) {
            throw new InvalidArgumentException("Узел не найден.");
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM nested_sets 
            WHERE lft < :left AND rgt > :right 
            ORDER BY lft
        ");
        $stmt->execute([
            'left' => $node['lft'],
            'right' => $node['rgt']
        ]);
        $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($parents)) {
            throw new InvalidArgumentException("У узла нет родителей.");
        }

        return $parents;
    }

    // Получение узла по ID
    public function getNodeById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM nested_sets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Подсчет количества потомков
    private function getChildrenCount($id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM nested_sets 
            WHERE parent_id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn();
    }

    public function displayTree() {
        $stmt = $this->pdo->query("
            SELECT 
                node.id, 
                node.title, 
                node.lft, 
                node.rgt,
                (COUNT(parent.id) - 1) AS depth
            FROM nested_sets AS node,
                nested_sets AS parent
            WHERE node.lft BETWEEN parent.lft AND parent.rgt
            GROUP BY 
                node.id, 
                node.title, 
                node.lft, 
                node.rgt
            ORDER BY node.lft
        ");
        
        $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($nodes as $node) {
            echo str_repeat('---', $node['depth']) . 
                 "> {$node['title']} (ID: {$node['id']})\n";
        }
    }
}

function main() {
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'your_dbname',
        'user' => 'postgres',
        'password' => 'water7op'
    ];
   
    // Создаем экземпляр класса
    $crud = new NestedSetsCRUD($dbConfig);
    
    while (true) {
        echo "\n1. Вывести дерево\n2. Добавить лист\n3. Удалить лист\n4. Удалить поддерево\n5. Удалить узел без поддерева\n6. Вывести прямых потомков\n7. Вывести прямого родителя\n8. Вывести всех потомков\n9. Вывести всех родителей\n10. Выйти\n";

        $choice = readline("Выберите опцию: ");
        
        switch ($choice) {
            case '1':
                try {
                    $crud->displayTree();
                } catch (Exception $e) {  
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;

            case '2':
                // Добавление узла
                $title = readline("Введите название листа: ");
                $parentId = readline("Введите ID родительского узла (по умолчанию 1): ");
                
                if (!$parentId) {
                    $parentId = 1;
                }

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
                // Удаление узла
                $leafIdToDelete = readline("Введите ID листа для удаления: ");
                
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
                // Получение потомков
                $subtreeIdToDelete = readline("Введите ID узла для удаления поддерева: ");

                if (!filter_var($subtreeIdToDelete, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $crud->deleteSubtree(intval($subtreeIdToDelete));
                    echo "Поддерево удалено.\n";
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;

                case '5':
                    // Удаление узла без поддерева
                    $nodeIdToDelete = readline("Введите ID узла для удаления: ");
    
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
                $parentIdForChildren = readline("Введите ID родительского узла: ");

                if (!filter_var($parentIdForChildren, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $children = $crud->getDirectChildren(intval($parentIdForChildren));
                    
                    if (!empty($children)) {
                        echo "Прямые потомки:\n";
                        foreach ($children as $child) {
                            echo "ID: {$child['id']}, Название: {$child['title']}\n";
                        }
                    } else {
                        echo "У узла нет прямых потомков.\n";
                    }
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            case '6':
                // Получение прямых потомков
                $parentIdForChildren = readline("Введите ID родительского узла: ");

                if (!filter_var($parentIdForChildren, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $children = $crud->getDirectChildren(intval($parentIdForChildren));
                    
                    if (!empty($children)) {
                        $crud->_retrieve($children);
                    } else {
                        throw new InvalidArgumentException ("No children found."); 
                    }
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            
                case '7':
                    // Получение прямого родителя
                    $nodeIdForParent = readline("Введите ID узла: ");
    
                    if (!filter_var($nodeIdForParent, FILTER_VALIDATE_INT)) {
                        echo "Некорректный ID.\n";
                        break;
                    }
    
                    try {
                        $parents = $crud->getDirectParent(intval($nodeIdForParent));
                        
                        if ($parents) {
                            $crud->_retrieve($parents);
                        } else {
                            throw new InvalidArgumentException ("Parent not found."); 
                        }
                    } catch (Exception $e) {
                        echo "Ошибка: " . $e->getMessage() . "\n";
                    }
                    break;
            
                 case '8':
                // Получение всех потомков
                $nodeIdForDescendants = readline("Введите ID узла: ");

                if (!filter_var($nodeIdForDescendants, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $descendants = $crud->getAllDescendants(intval($nodeIdForDescendants));
                    
                    if (!empty($descendants)) {
                        echo "Все потомки:\n";
                        foreach ($descendants as $descendant) {
                            echo "ID: {$descendant['id']}, Название: {$descendant['title']}\n";
                        }
                    } else {
                        echo "У узла нет потомков.\n";
                    }
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
                }
                break;
            
            case '9':
                // Получение всех родителей
                $nodeIdForParents = readline("Введите ID узла: ");

                if (!filter_var($nodeIdForParents, FILTER_VALIDATE_INT)) {
                    echo "Некорректный ID.\n";
                    break;
                }

                try {
                    $parents = $crud->getAllParents(intval($nodeIdForParents));
                    
                    if (!empty($parents)) {
                        $crud->printTree($parents);
                    } else {
                        echo "У узла нет родителей.\n";
                    }
                } catch (Exception $e) {
                    echo "Ошибка: " . $e->getMessage() . "\n";
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