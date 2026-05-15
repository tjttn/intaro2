<?php

function jsonToSql($jsonString) {
    $data = json_decode($jsonString, true);
    if ($data === null) {
        throw new Exception("Invalid JSON");
    }
    
    $sqlParts = [];
    
    // SELECT
    if (isset($data['select']) && !empty($data['select'])) {
        $selectFields = array_map(function($field) {
            return "`" . addslashes($field) . "`";
        }, $data['select']);
        $sqlParts[] = "select " . implode(", ", $selectFields);
    } else {
        $sqlParts[] = "select *";
    }
    

    // FROM
    if (!isset($data['from']) || empty($data['from'])) {
        throw new Exception("FROM is required");
    }
    $sqlParts[] = "from `" . addslashes($data['from']) . "`";
    
    // WHERE
    if (isset($data['where']) && !empty($data['where'])) {
        $whereCondition = parseWhereCondition($data['where']);
        if (!empty($whereCondition)) {
            $sqlParts[] = "where " . $whereCondition;
        }
    }
    
    // ORDER
    if (isset($data['order']) && !empty($data['order'])) {
        $orderKey = key($data['order']);
        $orderValue = $data['order'][$orderKey];
        $direction = strtolower($orderValue) === 'desc' ? 'desc' : 'asc';
        $sqlParts[] = "order by `" . addslashes($orderKey) . "` " . $direction;
    }
    
    // LIMIT
    if (isset($data['limit']) && !empty($data['limit'])) {
        $limit = (int)$data['limit'];
        $sqlParts[] = "limit " . $limit;
    }
    
    return implode("\n", $sqlParts) . ";";
}

function parseWhereCondition($condition, $isOr = false) {
    if (empty($condition)) {
        return '';
    }
    
    $conditions = [];
    $subConditions = [];
    
    foreach ($condition as $key => $value) {
        if (strpos($key, 'or_') === 0) {
            $subCond = parseWhereCondition($value, true);
            if (!empty($subCond)) {
                $subConditions['or'][] = $subCond;
            }
        } elseif (strpos($key, 'and_') === 0) {
            $subCond = parseWhereCondition($value, false);
            if (!empty($subCond)) {
                $subConditions['and'][] = $subCond;
            }
        } else {
            $conditionStr = parseSingleCondition($key, $value);
            if (!empty($conditionStr)) {
                $conditions[] = $conditionStr;
            }
        }
    }
    
    // Обрабатываем вложенные условия
    if (!empty($subConditions['or'])) {
        $conditions[] = "(" . implode(" or ", $subConditions['or']) . ")";
    }
    if (!empty($subConditions['and'])) {
        $conditions[] = "(" . implode(" and ", $subConditions['and']) . ")";
    }
    
    if (empty($conditions)) {
        return '';
    }
    
    $glue = $isOr ? " or " : " and ";
    return implode($glue, $conditions);
}

function parseSingleCondition($key, $value) {
    // Определяем оператор и поле
    $operators = ['<=', '>=', '<', '>', '=', '!'];
    $foundOp = null;
    $field = $key;
    
    foreach ($operators as $op) {
        if (strpos($key, $op) === 0) {
            $foundOp = $op;
            $field = substr($key, strlen($op));
            break;
        }
    }
    
    $escapedField = "`" . addslashes($field) . "`";
    $type = getValueType($value);
    
    switch ($foundOp) {
        case '<':
            return "$escapedField < " . formatValue($value, $type);
        case '<=':
            return "$escapedField <= " . formatValue($value, $type);
        case '>':
            return "$escapedField > " . formatValue($value, $type);
        case '>=':
            return "$escapedField >= " . formatValue($value, $type);
        case '=':
            return parseEqualityCondition($escapedField, $value, $type);
        case '!':
            return parseNotEqualityCondition($escapedField, $value, $type);
        default:
            // Нет оператора - используем LIKE для строк, = для остальных
            if ($type === 'string') {
                return "$escapedField like '" . addslashes($value) . "'";
            } else {
                return "$escapedField = " . formatValue($value, $type);
            }
    }
}

function parseEqualityCondition($field, $value, $type) {
    switch ($type) {
        case 'string':
            return "$field = '" . addslashes($value) . "'";
        case 'int':
        case 'float':
            return "$field = " . $value;
        case 'bool':
            return "$field is " . ($value ? 'true' : 'false');
        case 'null':
            return "$field is null";
        default:
            return "$field = " . formatValue($value, $type);
    }
}

function parseNotEqualityCondition($field, $value, $type) {
    switch ($type) {
        case 'string':
            return "$field != '" . addslashes($value) . "'";
        case 'int':
        case 'float':
            return "$field != " . $value;
        case 'bool':
            return "$field is not " . ($value ? 'true' : 'false');
        case 'null':
            return "$field is not null";
        default:
            return "$field != " . formatValue($value, $type);
    }
}

function getValueType($value) {
    if (is_null($value)) return 'null';
    if (is_bool($value)) return 'bool';
    if (is_int($value)) return 'int';
    if (is_float($value)) return 'float';
    if (is_string($value)) return 'string';
    if (is_array($value)) return 'array';
    return 'unknown';
}

function formatValue($value, $type) {
    switch ($type) {
        case 'string':
            return "'" . addslashes($value) . "'";
        case 'int':
        case 'float':
            return $value;
        case 'bool':
            return $value ? 'true' : 'false';
        case 'null':
            return 'null';
        default:
            return $value;
    }
}

// Тестирование
$testJson = '{
    "select": ["first", "second", "third"],
    "from": "test_table",
    "where": {"<first": 10, "=second": 10, "or_1": {"third": "val", "<=fourth": 20, "and_2": {">fifth": 30, "!sixth": 40}}},
    "order": {"first": "asc"},
    "limit": 10
}';

$result = jsonToSql($testJson);
echo "Input JSON:\n$testJson\n\n";
echo "Output SQL:\n$result\n\n";

// Дополнительные тесты
$testCases = [
    '{"from": "users"}' => 'select *\nfrom `users`;',
    '{"select": ["id", "name"], "from": "products"}' => 'select `id`, `name`\nfrom `products`;',
    '{"from": "orders", "where": {"status": "active"}, "limit": 5}' => 'select *\nfrom `orders`\nwhere `status` like \'active\'\nlimit 5;',
];

foreach ($testCases as $json => $expected) {
    $result = jsonToSql($json);
    echo "Test: $json\n";
    echo "Result: $result\n";
    echo "---\n";
}

// Функция для обработки файлового ввода
function processFileC($inputFile, $outputFile) {
    $json = file_get_contents($inputFile);
    $sql = jsonToSql($json);
    file_put_contents($outputFile, $sql);
}

// Генерация тестов
function generateTestsC($count = 20) {
    $dir = 'tests_c';
    if (!is_dir($dir)) mkdir($dir);
    
    $fields = ['id', 'name', 'price', 'status', 'created_at', 'updated_at', 'category_id', 'count'];
    $tables = ['users', 'products', 'orders', 'categories', 'items', 'logs'];
    $operators = ['<', '<=', '>', '>=', '=', '!', ''];
    $values = [10, 20, 100, '"active"', '"inactive"', 'true', 'false', 'null'];
    
    for ($t = 0; $t < $count; $t++) {
        $json = [];
        
        // SELECT (опционально)
        if (rand(0, 1) && rand(0, 2) > 0) {
            $selectCount = rand(1, 5);
            $selectFields = [];
            for ($i = 0; $i < $selectCount; $i++) {
                $selectFields[] = $fields[array_rand($fields)];
            }
            $json['select'] = $selectFields;
        }
        
        // FROM (обязательно)
        $json['from'] = $tables[array_rand($tables)];
        
        // WHERE (опционально)
        if (rand(0, 3) > 0) {
            $where = [];
            $condCount = rand(1, 4);
            for ($i = 0; $i < $condCount; $i++) {
                $op = $operators[array_rand($operators)];
                $field = $fields[array_rand($fields)];
                $value = $values[array_rand($values)];
                $key = $op . $field;
                $where[$key] = json_decode($value);
            }
            $json['where'] = $where;
        }
        
        // ORDER (опционально)
        if (rand(0, 1)) {
            $orderField = $fields[array_rand($fields)];
            $orderDir = rand(0, 1) ? 'asc' : 'desc';
            $json['order'] = [$orderField => $orderDir];
        }
        
        // LIMIT (опционально)
        if (rand(0, 1)) {
            $json['limit'] = rand(1, 100);
        }
        
        $jsonString = json_encode($json, JSON_PRETTY_PRINT);
        file_put_contents("$dir/input_$t.json", $jsonString);
        
        try {
            $sql = jsonToSql($jsonString);
            file_put_contents("$dir/expected_$t.sql", $sql);
        } catch (Exception $e) {
            file_put_contents("$dir/expected_$t.sql", "Error: " . $e->getMessage());
        }
        
        echo "Generated test $t\n";
    }
}

generateTestsC(20);
?>