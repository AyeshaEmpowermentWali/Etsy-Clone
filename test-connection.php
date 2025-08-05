<?php
// Database connection test file
echo "<h2>Database Connection Test</h2>";

$host = 'localhost';
$dbname = 'dbgyzgim7k72e5';
$username = 'ugrj543f7lree';
$password = 'cgmq43woifko';

echo "<p><strong>Testing connection with:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Database: $dbname</li>";
echo "<li>Username: $username</li>";
echo "<li>Password: " . str_repeat('*', strlen($password)) . "</li>";
echo "</ul>";

try {
    echo "<p>Attempting to connect...</p>";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'><strong>✅ Connection successful!</strong></p>";
    
    // Test if database exists and has tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: orange;'><strong>⚠️ Database is empty. You need to run the SQL file to create tables.</strong></p>";
        echo "<p>Please run the database.sql file in your MySQL to create the required tables.</p>";
    } else {
        echo "<p style='color: green;'><strong>✅ Database has tables:</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '$dbname'");
    $result = $stmt->fetch();
    echo "<p><strong>Total tables in database:</strong> " . $result['count'] . "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'><strong>❌ Connection failed:</strong></p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting Tips:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Verify the database name exists: <strong>$dbname</strong></li>";
    echo "<li>Check if the username has proper permissions</li>";
    echo "<li>Try connecting with a MySQL client first</li>";
    echo "<li>If using remote database, change 'localhost' to the actual server IP</li>";
    echo "</ul>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    
    h2 {
        color: #333;
        border-bottom: 2px solid #ff6b35;
        padding-bottom: 10px;
    }
    
    ul {
        background: white;
        padding: 15px 30px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    p {
        background: white;
        padding: 10px 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>
