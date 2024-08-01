<?php

namespace SiLibrary\SpiralConnecter;

use SiLibrary\Collection;

class SpiralFilterManager
{
    private $connection;
    private ?HttpRequestParameter $request = null;

    public function __construct(?SpiralConnecterInterface $connector = null)
    {
        if (is_null($connector)) {
            $this->connection = \SpiralDB::getConnection();
        } else {
            $this->connection = $connector;
        }
        $this->request = new HttpRequestParameter();
    }

    public function setTitle($title)
    {
        $this->request->set('db_title', $title);
        return $this;
    }

    public function selectName($selectName)
    {
        $this->request->set('select_name', $selectName);
        return $this;
    }

    public function idRange($ge, $lt)
    {
        $this->request->set('id_range', ['ge' => $ge, 'lt' => $lt]);
        return $this;
    }

    public function modulo($divisor, $surplus)
    {
        $this->request->set('modulo', [
            'divisor' => $divisor,
            'lt' => $surplus,
        ]);
        return $this;
    }

    public function limit($limit)
    {
        $this->request->set('limit', $limit);
        return $this;
    }

    public function registAuthorizer($registAuthorizer)
    {
        $this->request->set('regist_authorizer', $registAuthorizer);
        return $this;
    }

    public function exists($dbTitle, $dbFilterId, $include)
    {
        $this->request->set('exists', [
            'db_title' => $dbTitle,
            'db_filter_id' => $dbFilterId,
            'include' => $include,
        ]);
        return $this;
    }

    public function id($id)
    {
        $this->request->set('id', $id);
        return $this;
    }

    public function addField(DbFilterField $dbFilterField)
    {
        $fileds = $this->request->get('fields');
        if (empty($fields)) {
            $fileds = [];
        }
        $fileds[] = $dbFilterField->toArray();
        $this->request->set('fields', $fileds);
        return $this;
    }

    public function create()
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('db_filter', 'create');
        $response = $this->connection->request(
            $xSpiralApiHeader,
            $this->request
        );
        return $response['id'];
    }

    public function list()
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('db_filter', 'list');
        $response = $this->connection->request(
            $xSpiralApiHeader,
            $this->request
        );
        return new Collection($response['data']);
    }

    public function get()
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('db_filter', 'get');
        $response = $this->connection->request(
            $xSpiralApiHeader,
            $this->request
        );
        return new Collection($response);
    }

    public function delete(): void
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('db_filter', 'delete');
        $this->connection->request($xSpiralApiHeader, $this->request);
    }
}

class DbFilterField
{
    private string $name;
    private string $label = '';
    private string $value1 = '';
    private string $value2 = '';
    private string $condition = '=';
    private string $exclude = 'f';

    public function __construct(
        $name,
        $label = '',
        $value1 = '',
        $value2 = '',
        $condition = '=',
        $exclude = 'f'
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->value1 = $value1;
        $this->value2 = $value2;
        $this->condition = $condition;
        $this->exclude = $exclude;
    }

    public function toArray()
    {
        $array = [];
        if (!empty($this->name)) {
            $array['name'] = $this->name;
        }
        if (!empty($this->label)) {
            $array['label'] = $this->label;
        }
        if (!empty($this->value1)) {
            $array['value1'] = $this->value1;
        }
        if (!empty($this->value2)) {
            $array['value2'] = $this->value2;
        }
        if (!empty($this->condition)) {
            $array['condition'] = $this->condition;
        }
        if (!empty($this->exclude)) {
            $array['exclude'] = $this->exclude;
        }

        return $array;
    }
}
