<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\RedisExample;
use App\ElasticExample;
use App\ClickhouseExample;

echo "<h1>Лабораторная работа №6 - Работа с NoSQL базами данных</h1>";

echo "<h2>1. Redis (Ключ-значение)</h2>";

$redis = new RedisExample();

$redis->setValue('framework', 'predis');
$redis->setValue('language', 'PHP');
$redis->setValue('year', '2025');

echo "framework: " . $redis->getValue('framework') . "<br>";
echo "language: " . $redis->getValue('language') . "<br>";
echo "year: " . $redis->getValue('year') . "<br>";

$redis->increment('counter');
$redis->increment('counter');
echo "counter: " . $redis->getValue('counter') . "<br>";

echo "<hr>";

echo "<h2>2. Elasticsearch (Поисковый движок)</h2>";

$elastic = new ElasticExample();

$doc1 = $elastic->indexDocument('quiz_participants', 1, [
    'name' => 'Иван Петров',
    'age' => 25,
    'topic' => 'IT'
]);
echo "Документ 1 добавлен: " . ($doc1['result'] ?? 'успешно') . "<br>";

$doc2 = $elastic->indexDocument('quiz_participants', 2, [
    'name' => 'Мария Сидорова',
    'age' => 30,
    'topic' => 'Science'
]);
echo "Документ 2 добавлен: " . ($doc2['result'] ?? 'успешно') . "<br>";

$searchResult = $elastic->search('quiz_participants', ['name' => 'Иван']);
echo "Результат поиска по имени 'Иван': <br>";
echo "<pre>" . print_r($searchResult, true) . "</pre>";

echo "<hr>";

echo "<h2>3. ClickHouse (Колоночная БД для аналитики)</h2>";

$click = new ClickhouseExample();

$click->query("DROP TABLE IF EXISTS users");

$click->query("
    CREATE TABLE IF NOT EXISTS users (
        id UInt32,
        name String,
        age UInt8,
        created_at DateTime DEFAULT now()
    ) ENGINE = MergeTree()
    ORDER BY id
");

echo "Таблица 'users' создана<br>";

$click->query("INSERT INTO users (id, name, age) VALUES (1, 'Алексей', 25)");
$click->query("INSERT INTO users (id, name, age) VALUES (2, 'Екатерина', 30)");
$click->query("INSERT INTO users (id, name, age) VALUES (3, 'Дмитрий', 28)");
echo "Данные добавлены в ClickHouse<br>";

$result = $click->queryJson("SELECT * FROM users ORDER BY age");
echo "Список пользователей из ClickHouse:<br>";
echo "<pre>";
foreach ($result['data'] ?? [] as $row) {
    echo "ID: {$row['id']}, Имя: {$row['name']}, Возраст: {$row['age']}<br>";
}
echo "</pre>";

echo "<hr>";

echo "<h2>Вариант №13: Банковские транзакции (ClickHouse)</h2>";

$click->query("DROP TABLE IF EXISTS transactions");

$click->query("
    CREATE TABLE IF NOT EXISTS transactions (
        transaction_id UInt32,
        user_id UInt32,
        amount Float64,
        currency String,
        status String,
        created_at DateTime DEFAULT now()
    ) ENGINE = MergeTree()
    ORDER BY transaction_id
");

$click->query("INSERT INTO transactions (transaction_id, user_id, amount, currency, status) VALUES 
    (1, 101, 1500.50, 'RUB', 'completed'),
    (2, 102, 250.00, 'USD', 'pending'),
    (3, 101, 3000.00, 'RUB', 'completed'),
    (4, 103, 75.25, 'EUR', 'completed')
");

$transactions = $click->queryJson("SELECT user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount FROM transactions WHERE status = 'completed' GROUP BY user_id");
echo "Аналитика транзакций:<br>";
echo "<pre>";
foreach ($transactions['data'] ?? [] as $row) {
    echo "User {$row['user_id']}: {$row['transaction_count']} транзакций, сумма: {$row['total_amount']}<br>";
}
echo "</pre>";

echo "<br><a href='index.php'>Обновить страницу</a>";