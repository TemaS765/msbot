<?php
/**
 * Created by PhpStorm.
 * User: shtorm
 * Date: 10.11.18
 * Time: 2:57
 */

namespace B24Process;

class DB
{
    protected static $pdo;

    public function __construct($config)
    {
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];
        self::$pdo = new \PDO($config['driver'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=utf8', $config['user'], $config['password'], $options);
    }

    public function execute($sql, $data = [])
    {
        $stmt = self::$pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);

        return $stmt->execute($data);
    }

    public function query($sql, $data = [])
    {

        $stmt = self::$pdo->prepare($sql);
        $res = $stmt->execute($data);

        if ($res !== false) {
            return $stmt->fetchAll();
        }

        return [];
    }
}