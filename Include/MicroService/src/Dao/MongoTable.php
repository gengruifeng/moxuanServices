<?php
namespace MicroService\Dao;

class MongoTable  implements ITable {
    public function __construct($tableName, $connName) {

    }

    public function setSchema($schema) {
        // TODO: Implement setSchema() method.
    }

    public function getSchema($reload = false, $throw = false) {
        // TODO: Implement getSchema() method.
    }

    public function isExists() {
        // TODO: Implement isExists() method.
    }

    public function create($schema) {
        // TODO: Implement create() method.
    }

    public function toColumnName($colName) {
        // TODO: Implement toColumnName() method.
    }

    public function condition($condition) {
        // TODO: Implement condition() method.
    }

    public function idCondition($condition) {
        // TODO: Implement idCondition() method.
    }

    public function find($condition, $columns = '*') {
        // TODO: Implement find() method.
    }

    public function findAll($condition, $order = '', $columns = '*') {
        // TODO: Implement findAll() method.
    }

    public function findLimit($condition, $order, $currentPage = 1, $pageSize = 20, $columns = '*') {
        // TODO: Implement findLimit() method.
    }

    public function findMapArray($condition, $keyCol, $valueCol = '', $order = '', $columns = '*') {
        // TODO: Implement findMapArray() method.
    }

    public function save($data, $condition = array()) {
        // TODO: Implement save() method.
    }

    public function saveAll($dataArray) {
        // TODO: Implement saveAll() method.
    }

    public function delete($condition) {
        // TODO: Implement delete() method.
    }

    public function count($condition) {
        // TODO: Implement count() method.
    }

    public function lastInsertId() {
        // TODO: Implement lastInsertId() method.
    }


}