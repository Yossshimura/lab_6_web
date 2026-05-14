<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\ClickhouseExample;

echo "<h1>Лабораторная работа №6 - Вариант 13: Банковские транзакции</h1>";
echo "<p><strong>База данных:</strong> ClickHouse (колоночная БД для аналитики)</p>";

$click = new ClickhouseExample();

echo "<h2>1. Создание таблицы для транзакций</h2>";

$click->query("DROP TABLE IF EXISTS bank_transactions");

$click->query("
    CREATE TABLE IF NOT EXISTS bank_transactions (
        transaction_id UInt32,
        user_id UInt32,
        user_name String,
        amount Float64,
        currency String,
        transaction_type String,
        status String,
        created_at DateTime DEFAULT now()
    ) ENGINE = MergeTree()
    ORDER BY (created_at, user_id)
");

echo "Таблица 'bank_transactions' успешно создана<br>";

echo "<h2>2. Добавление тестовых транзакций</h2>";

$click->query("INSERT INTO bank_transactions (transaction_id, user_id, user_name, amount, currency, transaction_type, status) VALUES 
    (1, 101, 'Иван Петров', 15000.50, 'RUB', 'deposit', 'completed'),
    (2, 101, 'Иван Петров', 5000.00, 'RUB', 'withdrawal', 'completed'),
    (3, 102, 'Мария Сидорова', 25000.00, 'RUB', 'deposit', 'completed'),
    (4, 102, 'Мария Сидорова', 3000.00, 'RUB', 'transfer', 'pending'),
    (5, 103, 'Алексей Иванов', 100.00, 'USD', 'deposit', 'completed'),
    (6, 103, 'Алексей Иванов', 50.00, 'USD', 'withdrawal', 'completed'),
    (7, 101, 'Иван Петров', 1200.00, 'RUB', 'transfer', 'completed'),
    (8, 104, 'Екатерина Смирнова', 50000.00, 'RUB', 'deposit', 'completed'),
    (9, 104, 'Екатерина Смирнова', 10000.00, 'RUB', 'withdrawal', 'failed'),
    (10, 105, 'Дмитрий Козлов', 300.00, 'EUR', 'deposit', 'completed')
");

echo "Добавлено 10 тестовых транзакций<br>";

echo "<h2>3. Аналитика транзакций</h2>";

echo "<h3>3.1 Общая статистика по всем транзакциям:</h3>";
$totalStats = $click->queryJson("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount,
        COUNT(DISTINCT user_id) as unique_users
    FROM bank_transactions
");
echo "<pre>";
print_r($totalStats['data'][0] ?? $totalStats);
echo "</pre>";

echo "<h3>3.2 Статистика по типу транзакций:</h3>";
$typeStats = $click->queryJson("
    SELECT 
        transaction_type,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount
    FROM bank_transactions
    GROUP BY transaction_type
    ORDER BY total_amount DESC
");
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>Тип транзакции</th><th>Количество</th><th>Сумма</th><th>Средняя сумма</th></tr>";
foreach ($typeStats['data'] ?? [] as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['transaction_type']) . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
    echo "<td>" . number_format($row['avg_amount'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3.3 Топ-5 пользователей по сумме транзакций:</h3>";
$topUsers = $click->queryJson("
    SELECT 
        user_id,
        user_name,
        SUM(amount) as total_amount,
        COUNT(*) as transaction_count
    FROM bank_transactions
    WHERE status = 'completed'
    GROUP BY user_id, user_name
    ORDER BY total_amount DESC
    LIMIT 5
");
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>User ID</th><th>Имя пользователя</th><th>Общая сумма</th><th>Количество транзакций</th></tr>";
foreach ($topUsers['data'] ?? [] as $row) {
    echo "<tr>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
    echo "<td>" . $row['transaction_count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3.4 Статистика по валютам:</h3>";
$currencyStats = $click->queryJson("
    SELECT 
        currency,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount
    FROM bank_transactions
    WHERE status = 'completed'
    GROUP BY currency
    ORDER BY total_amount DESC
");
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>Валюта</th><th>Количество</th><th>Общая сумма</th><th>Средняя сумма</th></tr>";
foreach ($currencyStats['data'] ?? [] as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['currency']) . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
    echo "<td>" . number_format($row['avg_amount'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. Отчёт по статусам транзакций</h2>";
$statusStats = $click->queryJson("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM bank_transactions
    GROUP BY status
");
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>Статус</th><th>Количество</th><th>Общая сумма</th></tr>";
foreach ($statusStats['data'] ?? [] as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. Все транзакции в таблице</h2>";
$allTransactions = $click->queryJson("
    SELECT 
        transaction_id,
        user_id,
        user_name,
        amount,
        currency,
        transaction_type,
        status,
        created_at
    FROM bank_transactions
    ORDER BY created_at DESC
");
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr>";
echo "<th>ID</th><th>User ID</th><th>Имя</th><th>Сумма</th><th>Валюта</th><th>Тип</th><th>Статус</th><th>Дата</th>";
echo "</tr>";
foreach ($allTransactions['data'] ?? [] as $row) {
    echo "<tr>";
    echo "<td>" . $row['transaction_id'] . "</td>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
    echo "<td>" . number_format($row['amount'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($row['currency']) . "</td>";
    echo "<td>" . htmlspecialchars($row['transaction_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='index.php'>🔄 Обновить страницу</a></p>";
?>