<?php

require_once __DIR__ . '\yaml.php';
require_once __DIR__ . '\log.php';

class PdoConnect{

    private $log = null;
    private $host = null;
    private $username = null;
    private $password = null;
    private $database = null;
    private $conn = null;
    private $table = null;
    private $port = null;

    public function setDatabase($database){
        $this->database = $database;
    }
    public function setTable($table){
        $this->table = $table;
    }
    public function __construct(){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->log = new Logger();
        $this->getConfig();
        $this->connect();
    }
    private function getConfig(){
        $data           = Spyc::YAMLLoad(__DIR__ . '\config.yaml');
        $data           = $data['mysql'];

        $this->host     = $data['host'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->database = $data['database'];
        $this->port     = $data['port'];
    }
    private function connect(){
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->database;charset=utf8;port=$this->port",$this->username,$this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $e){
            $this->log->write($e->getMessage());
        }
        return true;
    }
    private function _buildWhere(array $arrs){
        $w = '';
        foreach ($arrs as $key => $val){
            $w .= ' AND ' . $key . ' = "' . $val . '"';
        }
        $w = trim($w,' AND ');
        return $w;
    }
    private function _buildWherIn(array $arrs){
        //
    }
    private function _buildOrder(array $arrs){
        $order = ' order by ';
        foreach ($arrs as $item => $val){
            $order .= ' ' . $item . ' ' . $val . ',';
        }
        $order = trim($order,',');
        return $order;
    }
    private function _exec($sql){
        try{
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            preg_match('/(UPDATE)|(DELETE)/', $sql, $matches, PREG_OFFSET_CAPTURE);
            if(count($matches) > 0){
                return true;
            }
            if(preg_match('/(INSERT)/', $sql)){
                return $this->conn->lastInsertId();
            }
            return $stmt->fetchAll();
        }catch (PDOException $e){
            $this->log->write($e->getMessage());
            return false;
        }
    }
    public function all($order = null){
        $sql = 'SELECT * from ' . $this->table;
        if($order != null){
            $sql .= $this->_buildOrder($order);
        }
        $result = $this->_exec($sql);
        if(!$result){
            $this->log->write('Get all error');
            return false;
        }
        return $result;
    }
    public function get(array $where, $limit = null, $location = null){
        $sql = 'SELECT * from ' . $this->table . ' where ' . $this->_buildWhere($where);
        if($limit != null){
            $sql .= ' limit ' . $limit;
        }
        $result = $this->_exec($sql);
        if(!$result){
            $this->log->write('function get error');
            return false;
        }
        return $result;
    }
    public function one(array $where){
        $result = $this->get($where, 1);
        return $result[0];
    }
    public function update(array $updates, array $where){
        $codition = '';
        foreach($updates as $key=>$value) {
            if(is_numeric($value))
                $codition .= $key . " = " . $value . ", ";
            else
                $codition .= $key . " = " . "'" . $value . "'" . ", ";
        }

        $codition = trim($codition, ' ');
        $codition = trim($codition, ',');

        $sql = 'UPDATE ' . $this->table . ' SET ' . $codition . ' where ' . $this->_buildWhere($where);

        $result = $this->_exec($sql);
        if(!$result){
            $this->log->write('function update error ' . $sql);
            return false;
        }
        return $result;
    }
    public function delete(array $arrs){
        $sql = 'DELETE FROM ' . $this->table . ' where ' . array_keys($arrs)[0] . ' = "' . $arrs[array_keys($arrs)[0]] . '"';
        $result = $this->_exec($sql);
        if(!$result){
            $this->log->write('function update error ' . $sql);
            return false;
        }
        return $result;
    }
    public function insert(array $arrs){
        $key = array_keys($arrs);
        $val = array_values($arrs);
        $key = '(' . implode(',', $key) . ')';
        $val = '("' . implode('","', $val) . '")';

        $sql = 'INSERT INTO ' . $this->table . $key . ' VALUES ' . $val;

        $result = $this->_exec($sql);
        if(!$result){
            $this->log->write('function update error ' . $sql);
            return false;
        }
        return $result;
    }
}

