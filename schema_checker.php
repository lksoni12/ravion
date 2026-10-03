<?php

$models = glob(__DIR__ . '/app/Models/*.php');
$modelData = [];
foreach ($models as $f) {
    $content = file_get_contents($f);
    $table = '';
    if (preg_match('/protected\s+\$table\s*=\s*[\'"]?([a-zA-Z0-9_]+)[\'"]?/', $content, $m)) {
        $table = $m[1];
    }
    $fields = [];
    if (preg_match('/protected\s+\$allowedFields\s*=\s*\[(.*?)\]/s', $content, $m)) {
        if (preg_match_all('/[\'"]([a-zA-Z0-9_]+)[\'"]/', $m[1], $fm)) {
            $fields = $fm[1];
        }
    }
    $useTimestamps = preg_match('/protected\s+\$useTimestamps\s*=\s*true/i', $content);
    $useSoftDeletes = preg_match('/protected\s+\$useSoftDeletes\s*=\s*true/i', $content);
    
    if ($table) {
        if ($useTimestamps) {
            $fields[] = 'created_at';
            $fields[] = 'updated_at';
        }
        if ($useSoftDeletes) {
            $fields[] = 'deleted_at';
        }
        $modelData[$table] = $fields;
    }
}

$migrations = glob(__DIR__ . '/app/Database/Migrations/*.php');
$migratedData = [];

// Simplified migration parsing
foreach ($migrations as $f) {
    $content = file_get_contents($f);
    
    // Catch addField([...]) followed by createTable('table_name')
    if (preg_match_all('/addField\(\[(.*?)\]\);.*?createTable\([\'"]([a-zA-Z0-9_]+)[\'"]/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $table = $m[2];
            if (!isset($migratedData[$table])) $migratedData[$table] = [];
            if (preg_match_all('/[\'"]([a-zA-Z0-9_]+)[\'"]\s*=>/s', $m[1], $fm)) {
                $migratedData[$table] = array_merge($migratedData[$table], $fm[1]);
            }
        }
    }
    
    // Catch addColumn('table_name', [...])
    if (preg_match_all('/addColumn\([\'"]([a-zA-Z0-9_]+)[\'"]\s*,\s*\[(.*?)\]/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $table = $m[1];
            if (!isset($migratedData[$table])) $migratedData[$table] = [];
            if (preg_match_all('/[\'"]([a-zA-Z0-9_]+)[\'"]\s*=>/s', $m[2], $fm)) {
                $migratedData[$table] = array_merge($migratedData[$table], $fm[1]);
            }
        }
    }
}

$missing = [];
foreach ($modelData as $table => $modelFields) {
    if (!isset($migratedData[$table])) {
        $missing[$table] = ['TABLE_MISSING' => true];
        continue;
    }
    $dbFields = $migratedData[$table];
    $missingFields = [];
    foreach ($modelFields as $f) {
        if (!in_array($f, $dbFields)) {
            $missingFields[] = $f;
        }
    }
    if (!empty($missingFields)) {
        $missing[$table] = $missingFields;
    }
}

echo "--- Missing Database Elements Based on Migrations ---\n";
print_r($missing);

