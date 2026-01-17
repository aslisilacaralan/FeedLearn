<?php
require_once __DIR__ . '/config/db.php';
echo "Checking activities table...\n";
$pdo = db_connect();
$stmt = $pdo->query('SELECT * FROM activities');
$activities = $stmt->fetchAll();

echo "Count: " . count($activities) . "\n";
print_r($activities);

if (count($activities) === 0) {
    echo "Table empty. Attempting seed...\n";
    // Check if the seed logic in db_init_schema works or if we need to manually trigger it.
    // db_init_schema is called on connection, so it SHOULD be populated.
    // Maybe checking invalid file permission or path?
    
    // Let's try to force insert.
     $stmt = $pdo->prepare(
        'INSERT INTO activities (title, description, activity_type, is_enabled)
         VALUES (:title, :description, :activity_type, :is_enabled)'
    );
     $activities = [
        ['Speaking', 'Record audio...', 'speaking', 1],
        ['Writing', 'Write text...', 'writing', 1],
        ['Quiz', 'Multiple choice...', 'quiz', 1]
     ];
     foreach($activities as $a) {
         $stmt->execute(['title'=>$a[0], 'description'=>$a[1], 'activity_type'=>$a[2], 'is_enabled'=>$a[3]]);
     }
     echo "Forced insertion done.\n";
}
