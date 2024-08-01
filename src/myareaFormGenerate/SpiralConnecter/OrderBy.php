<?php

namespace SiLibrary\SpiralConnecter;

use SiLibrary\Collection;
use LogicException;

class OrderBy
{
    public string $field;
    public string $ascOrDesc;

    public function __construct(string $field, string $ascOrDesc)
    {
        $this->field = $field;

        if ($ascOrDesc !== 'asc' && $ascOrDesc !== 'desc') {
            throw new LogicException('Please specify asc or desc');
        }
        $this->ascOrDesc = $ascOrDesc;
    }

    public function getRequestParam()
    {
        return ['name' => $this->field, 'order' => $this->ascOrDesc];
    }
}

class JoinDb
{
    public SpiralManager $db;
    public string $field = '';
    public string $op = '';
    public string $toField = '';
    public string $asDbName = '';
    public bool $hasOne = false;
    public Collection $result;

    public function __construct(
        SpiralManager $db,
        string $field,
        string $op,
        string $toField,
        string $asDbName = ''
    ) {
        $this->db = $db;
        $this->field = $field;
        $this->op = $op;
        $this->toField = $toField;
        $this->asDbName = $asDbName;
    }

    public function setHasOne()
    {
        $this->hasOne = true;
    }

    public function isHasOne()
    {
        return $this->hasOne;
    }

    public function getDbAsName()
    {
        return empty($this->asDbName) ? $this->db->getTitle() : $this->asDbName;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function exec(Collection $collection)
    {
        if ($this->op == '=') {
            $this->result = $this->db
                ->whereIn($this->field, $collection->column($this->toField))
                ->get();
        } else {
            $this->result = $this->db
                ->orWhereNotIn(
                    $this->field,
                    $collection->column($this->toField)
                )
                ->get();
        }
    }

    public function fetch(Collection $collection)
    {
        if ($this->op == '=') {
            $result = $this->result->where(
                $this->field,
                $collection->{$this->toField}
            );
        } else {
            $result = $this->result->whereNot(
                $this->field,
                $collection->{$this->toField}
            );
        }

        if ($this->isHasOne()) {
            return $result->first();
        }
        return $result;
    }
}
