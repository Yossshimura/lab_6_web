<?php

namespace App;

use App\Helpers\ClientFactory;

class ClickhouseExample
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://clickhouse:8123/');
    }

    public function query($sql)
    {
        $response = $this->client->post('', [
            'body' => $sql
        ]);
        return $response->getBody()->getContents();
    }

    public function queryJson($sql)
    {
        $response = $this->client->post('', [
            'body' => $sql,
            'headers' => ['Accept' => 'application/json']
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function createTable($tableName, $columns)
    {
        $sql = "CREATE TABLE IF NOT EXISTS $tableName ($columns) ENGINE = MergeTree() ORDER BY id";
        return $this->query($sql);
    }

    public function insert($tableName, $data)
    {
        $columns = implode(', ', array_keys($data[0]));
        $values = [];
        foreach ($data as $row) {
            $values[] = "(" . implode(', ', array_map(function($val) {
                return is_numeric($val) ? $val : "'$val'";
            }, array_values($row))) . ")";
        }
        $sql = "INSERT INTO $tableName ($columns) VALUES " . implode(', ', $values);
        return $this->query($sql);
    }

    public function select($tableName, $limit = 10)
    {
        return $this->queryJson("SELECT * FROM $tableName LIMIT $limit");
    }
}