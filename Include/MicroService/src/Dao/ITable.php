<?php
namespace MicroService\Dao;

interface ITable {
    public function setSchema($schema);

    public function getSchema($reload=false, $throw=false);

    public function isExists();

    public function create($schema);

    public function toColumnName($colName);

    public function condition($condition);

    public function idCondition($condition);

    public function find($condition, $columns='*');

    public function findAll($condition, $order='', $columns='*');

    public function findLimit($condition, $order, $currentPage=1, $pageSize=20, $columns='*');

    public function findMapArray($condition, $keyCol, $valueCol='', $order='', $columns='*');

    public function save($data, $condition=array());

    public function saveAll($dataArray);

    public function delete($condition);

    public function count($condition);

    public function lastInsertId();
}