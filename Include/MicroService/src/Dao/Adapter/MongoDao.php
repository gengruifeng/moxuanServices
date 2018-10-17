<?php
namespace MicroService\Dao\Adapter;

use MicroService\Dao;

class MongoDao extends Dao {
    protected function _connect($dbConfig) {
        // TODO: Implement _connect() method.
    }

    protected function ping($dbConn) {
        // TODO: Implement ping() method.
    }

    public function isTableExists($tableName, $isRealTableName = false) {
        // TODO: Implement isTableExists() method.
    }

    public function createTable($tableName, $schema, $isRealTableName = false) {
        // TODO: Implement createTable() method.
    }

    public function getTableSchema($tableName, $isRealTableName = false) {
        // TODO: Implement getTableSchema() method.
    }

    protected function isIntColumn($column) {
        // TODO: Implement isIntColumn() method.
    }

    protected function isDecimalColumn($column) {
        // TODO: Implement isDecimalColumn() method.
    }

    protected function isStrColumn($column) {
        // TODO: Implement isStrColumn() method.
    }

    protected function isDateColumn($column) {
        // TODO: Implement isDateColumn() method.
    }

    protected function isTimeColumn($column) {
        // TODO: Implement isTimeColumn() method.
    }

    protected function isDatetimeColumn($column) {
        // TODO: Implement isDatetimeColumn() method.
    }

    protected function isTimestampColumn($column) {
        // TODO: Implement isTimestampColumn() method.
    }

    public function getColumnType($column) {
        // TODO: Implement getColumnType() method.
    }

    protected function begin() {
        // TODO: Implement begin() method.
    }

    protected function commit() {
        // TODO: Implement commit() method.
    }

    protected function rollback() {
        // TODO: Implement rollback() method.
    }

    public function getLastQuery() {
        // TODO: Implement getLastQuery() method.
    }

    public function lastInsertId($sequenceName = '') {
        // TODO: Implement lastInsertId() method.
    }

    public function affectRows() {
        // TODO: Implement affectRows() method.
    }

    protected function closeConn($conn) {
        // TODO: Implement closeConn() method.
    }

    public function execute($strQuery) {
        // TODO: Implement execute() method.
    }

    public function getAll($strQuery, $order = '') {
        // TODO: Implement getAll() method.
    }

    public function getRow($strQuery) {
        // TODO: Implement getRow() method.
    }

    public function getOne($strQuery) {
        // TODO: Implement getOne() method.
    }

    public function getLimit($strQuery, $order = '', $currentPage = 1, $pageSize = 20) {
        // TODO: Implement getLimit() method.
    }

    public function getMapArray($strQuery, $mapCol, $valueCol = '', $order = '') {
        // TODO: Implement getMapArray() method.
    }

    public function randOrder() {
        // TODO: Implement randOrder() method.
    }

    public function quote($string) {
        // TODO: Implement quote() method.
    }

    public function strtotime($datetime) {
        // TODO: Implement strtotime() method.
    }

    public function datetime($datetime, $format = 'Y-m-d H:i:s') {
        // TODO: Implement datetime() method.
    }

    public function timestamp($timestamp) {
        // TODO: Implement timestamp() method.
    }

}