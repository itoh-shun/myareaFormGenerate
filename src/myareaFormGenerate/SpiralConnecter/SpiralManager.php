<?php

namespace SiLibrary\SpiralConnecter;

use SiLibrary\Collection;
use LogicException;

class SpiralManager
{
    private $connection;
    private ?HttpRequestParameter $request = null;
    private array $searchCondition = [];
    private array $fields = [];
    private array $defaultFields = [];
    private ?OrderBy $orderBy = null;
    private string $dataformat = '';
    private array $changeLabelFields = [];
    private string $selectName = '';
    //private array $leftJoin = [];
    private array $join = [];
    private ?Collection $mstData = null;
    private ?SpiralRedis $cache = null;
    private bool $isCache = false;

    public function __construct(?SpiralConnecterInterface $connector = null , ?SpiralRedis $spiralRedis = null)
    {
        if($spiralRedis){
            $this->isCache = true;
        }
        $this->cache = $spiralRedis;

        if (is_null($connector)) {
            $this->connection = \SpiralDB::getConnection();
        } else {
            $this->connection = $connector;
        }
        $this->request = new HttpRequestParameter();
        $this->page(1);
    }

    public function fields($fields)
    {
        $this->defaultFields = $fields;
        return $this;
    }

    public function setTitle($title)
    {
        $this->request->set('db_title', $title);
        return $this;
    }

    public function getTitle()
    {
        return $this->request->get('db_title');
    }

    public function dontCache(){
        $this->isCache = false;
        return $this;
    }

    /*
    public function setDbAsName($dbAsName)
    {
        $this->dbAsName = $dbAsName;
    }

    public function getDbAsName()
    {
        return $this->dbAsName;
    }
    */
    public function where(string $field, string $value, string $operator = '=')
    {
        $this->searchCondition[] = new SearchCondition(
            $field,
            $value,
            $operator
        );

        $this->request->set(
            'search_condition',
            array_map(function (SearchCondition $searchCondition) {
                return $searchCondition->getRequestParam();
            }, $this->searchCondition)
        );

        return $this;
    }

    public function orWhere(
        string $field,
        string $value,
        string $operator = '='
    ) {
        $this->searchCondition[] = new SearchCondition(
            $field,
            $value,
            $operator,
            false
        );

        $this->request->set(
            'search_condition',
            array_map(function (SearchCondition $searchCondition) {
                return $searchCondition->getRequestParam();
            }, $this->searchCondition)
        );

        return $this;
    }

    public function whereIn(string $field, array $values)
    {
        foreach ($values as $value) {
            $this->orWhere($field, $value, '=');
        }
        return $this;
    }

    public function whereNotIn(string $field, array $values)
    {
        foreach ($values as $value) {
            $this->where($field, $value, '!=');
        }
        return $this;
    }

    public function orWhereNotIn(string $field, array $values)
    {
        foreach ($values as $value) {
            $this->orWhere($field, $value, '!=');
        }
        return $this;
    }

    public function whereNull(string $field)
    {
        $this->where($field, '0', 'ISNULL');
        return $this;
    }

    public function whereNotNull(string $field)
    {
        $this->where($field, '0', 'ISNOTNULL');
        return $this;
    }

    public function orderBy(string $field, string $ascOrDesc)
    {
        $this->orderBy = new OrderBy($field, $ascOrDesc);
        $this->request->set('sort', [$this->orderBy->getRequestParam()]);
        return $this;
    }

    public function groupBy($value)
    {
        $this->fields = [];
        if (is_array($value)) {
            $this->fields = $value;
        }
        if (is_string($value)) {
            $this->fields[] = $value;
        }

        $this->request->set('select_columns', $this->fields);
        $this->request->set('group_by', $this->fields);

        return $this;
    }

    public function changeLabelFields($fields){
        if (is_array($fields)) {
            $this->changeLabelFields = $fields;
        }
        if (is_string($fields)) {
            $this->changeLabelFields[] = $fields;
        }
        return $this;
    }

    public function value($value)
    {
        if (is_array($value)) {
            $this->fields = array_merge($this->fields, $value);
        }

        if (is_string($value)) {
            $this->fields[] = $value;
        }

        $this->request->set('select_columns', $this->fields);

        return $this;
    }

    private function combine($keys, $values)
    {
        foreach ($values as &$val) {
            $val = array_combine($keys, $val);
        }
        return $values;
    }

    /*
    public function on( string $joinKey , string $joinOperator , string $leftKey )
    {
        $this->leftJoin['joinKey'] = $joinKey;
        $this->leftJoin['leftKey'] = $leftKey;
        $this->leftJoin['joinOperator'] = $joinOperator;

        if(!is_null($this->mstData))
        {
            foreach($this->mstData as $m)
            {
                $this->orWhere($joinKey , $m->{$leftKey} , $joinOperator);
            }
        }
        return $this;
    }

    public function leftJoin(string $joinTitle , $joinKey , ?string $joinOperator = "" , ?string $leftKey = "" , ?array $fields = [])
    {
        SpiralDB::setConnecter($this->connection);
        if(class_exists($joinTitle))
        {
            $db = ( new $joinTitle()) ->init();
            if(! empty($joinTitle::$dbAsName) )
            {
                $db->setDbAsName($joinTitle::$dbAsName);
            }
        }
        else
        {
            $db = SpiralDB::title($joinTitle);
        }
        $this->joinDbs[] = $db;
        if(is_callable($joinKey))
        {
            $db->leftJoin = [ 'title' => $this->request->get('db_title') , 'callable' => $joinKey , 'joinOperator' => $joinOperator , 'leftKey' => $leftKey , 'fields' => $fields , 'joinKey' => null];
            return $this;
        }
        $db->leftJoin = [ 'title' => $this->request->get('db_title') , 'joinKey' => $joinKey , 'joinOperator' => $joinOperator , 'leftKey' => $leftKey , 'fields' => $fields];
        return $this;
    }

    public function setMstData(Collection $col)
    {
        $this->mstData = $col;
    }

    public function getLeftJoin(Collection $collection)
    {
        $col = $collection->toArray();
        if(count($col) == 0)
        {
            return $collection;
        }

        foreach($this->joinDbs as $joinDb)
        {
            $joinDb->setMstData($collection);
            $j = $joinDb->leftJoin;
            if($j['title'] !== $this->request->get('db_title'))
            {
                throw new LogicException('title is Not Equal');
            }
            if(isset($j['callable']))
            {
                $j['callable']($joinDb);
            }
            else
            {
                foreach( $col as $c )
                {
                    $joinDb->orWhere( $j['joinKey'] , $c[$j['leftKey']] , '=' );
                }

                $joinDb->value($j['fields']);
            }

            $j = $joinDb->leftJoin;

            $res = $joinDb->get();
            $res = $res->toArray();

            foreach( $col as &$c )
            {
                foreach( $res as $r)
                {
                    if( $c[$j['leftKey']] == $r[$j['joinKey']] )
                    {
                        $titleKey = $joinDb->getTitle();
                        if(! empty($joinDb->getDbAsName()))
                        {
                            $titleKey = $joinDb->getDbAsName();
                        }
                        $c[$titleKey] = $r;
                    }
                }
            }
        }


        return new Collection($col);
    }

    */

    public function join(
        SpiralManager $db,
        string $field,
        string $op,
        string $toField,
        string $asDbName = ''
    ) {
        $this->join[] = new JoinDb($db, $field, $op, $toField, $asDbName);
        return $this;
    }

    public function hasMany(
        SpiralManager $db,
        string $field,
        string $op,
        string $toField,
        string $asDbName = ''
    ) {
        return $this->join($db, $field, $op, $toField, $asDbName);
    }

    public function hasOne(
        SpiralManager $db,
        string $field,
        string $op,
        string $toField,
        string $asDbName = ''
    ) {
        $join = new JoinDb($db, $field, $op, $toField, $asDbName);
        $join->setHasOne();
        $this->join[] = $join;
        return $this;
    }

    public function dataFormat(
        string $field,
        string $type = 'thumbnail_image_url'
    ) {
        $types = [
            'normal',
            'thumbnail_image_base64',
            'thumbnail_image_url',
            'original_image_url',
        ];
        if (!in_array($type, $types, true)) {
            throw new LogicException(
                "type is 'normal','thumbnail_image_base64','thumbnail_image_url','original_image_url'",
                '999'
            );
        }
        $this->request->set('data_format', [$field => $type]);
        return $this;
    }

    public function page(int $page)
    {
        if ($page < 1) {
            throw new LogicException(
                'page must be greater than or equal to 1',
                501
            );
        }
        $this->request->set('page', $page);
        return $this;
    }

    public function find(int $id)
    {
        $this->where('id', $id, '=');
        $res = $this->get();
        if (count($res->toArray()) === 0) {
            return null;
        }
        return $res->first();
    }

    public function findOrFail(int $id)
    {
        $res = $this->find($id);
        if (is_null($res)) {
            throw new \Exception('Not Found', 404);
        }

        return $res;
    }

    public function paginate(int $limit = 1000)
    {
        if ($limit > 1000 || $limit < 1) {
            throw new LogicException(
                'limit must be greater than or equal to 1 and less than or equal to 1000 ',
                501
            );
        }

        if (!$this->orderBy) {
            $this->orderBy('id', 'asc');
        }

        if (is_null($this->request->get('select_columns'))) {
            if (count($this->defaultFields) > 0) {
                $this->value($this->defaultFields);
            } else {
                $this->value('id');
            }
        }

        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'select');

        $this->request->set('lines_per_page', $limit);

        $this->request->set('labels_target', 'all');

        $response = $this->requestBuilder(
            $xSpiralApiHeader,
            $this->request
        );

        $res = $this->combine(
            $this->request->get('select_columns'),
            $response['data']
        );

        $labels = [];
        foreach($response['label'] as $label){
            $labels[] = (array)$label;
        }
        $labels = $this->combine($this->request->get('select_columns'), [$labels])[0];

        foreach ($res as $key => $value) {
            foreach ($this->changeLabelFields as $field) {
                if (isset($labels[$field][$value[$field]])) {
                    $res[$key][$field] = $labels[$field][$value[$field]];
                }
            }
        }

        $res = new Collection($res);
        //$res = $this->getLeftJoin($res);
        foreach ($this->join as $join) {
            $join->exec($res);
            $tmp = $res->all();
            foreach ($tmp as $key => $r) {
                $r->set($join->getDbAsName(), $join->fetch($r));
                $tmp[$key] = $r;
            }
            $res = new Collection($tmp);
        }

        return new Paginator(
            $res,
            (int) $this->request->get('page'),
            1,
            ceil($response['count'] / $limit),
            $limit,
            $response['count'],
            $this->orderBy
        );
    }

    public function getMulti(array $fields = [])
    {
        $count = 1; // 1件以上ある想定
        $res = [];
        if (is_null($this->request->get('select_columns'))) {
            if (count($this->defaultFields) > 0) {
                $this->value($this->defaultFields);
            } else {
                $this->value('id');
            }
        }

        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'select');
        $this->value($fields);

        $result = $this->paginate(1);

        $count = $result->getTotal();

        $requests = [];

        $this->request->set('lines_per_page', 1000);

        for ($page = 1; $page <= ceil($count / 1000); $page++) {
            $request = $this->request->remake();
            $request->set('page', $page);
            $requests[] = $request;
        }
        $res = $this->connection->bulkRequest($xSpiralApiHeader, $requests);

        $res = $this->combine($this->request->get('select_columns'), $res);
        return new Collection($res);
    }

    public function get(array $fields = [])
    {
        $count = 1; // 1件以上ある想定
        $res = [];

        if (!$this->orderBy && in_array('id', $this->fields)) {
            $this->orderBy('id', 'asc');
        }

        if (is_null($this->request->get('select_columns'))) {
            if (count($this->defaultFields) > 0) {
                $this->value($this->defaultFields);
            } else {
                $this->value('id');
            }
        }

        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'select');
        $this->value($fields);
        
        $this->request->set('labels_target', 'all');
        $this->request->set('lines_per_page', 1000);
        $this->request->set('page', 1);

        for ($page = 1; $page <= ceil($count / 1000); $page++) {
            $this->request->set('page', $page);
            $response = $this->requestBuilder(
                $xSpiralApiHeader,
                $this->request
            );
            $count = (int) $response['count'];
            $res = array_merge($res, $response['data']);
        }
        $res = $this->combine($this->request->get('select_columns'), $res);

        $labels = [];
        foreach($response['label'] as $label){
            $labels[] = (array)$label;
        }
        $labels = $this->combine($this->request->get('select_columns'), [$labels])[0];

        foreach ($res as $key => $value) {
            foreach ($this->changeLabelFields as $field) {
                if (isset($labels[$field][$value[$field]])) {
                    $res[$key][$field] = $labels[$field][$value[$field]];
                }
            }
        }

        $res = new Collection($res);
        foreach ($this->join as $join) {
            $join->exec($res);
            $tmp = $res->all();
            foreach ($tmp as $key => $r) {
                $r->set($join->getDbAsName(), $join->fetch($r));
                $tmp[$key] = $r;
            }
            $res = new Collection($tmp);
        }

        return $res;
    }

    private function makeCacheKey(){
        return md5($this->request->toJson());
    }

    private function requestBuilder(XSpiralApiHeaderObject $xSpiralApiHeader , HttpRequestParameter $httpRequestParameter){
        $result = null;

        if($xSpiralApiHeader->func() == 'database')
        {
            if($this->isCache){
                $resultKeys = [];
                if($this->cache->exists($this->request->get('db_title'))){
                    $resultKeys = json_decode($this->cache->get($this->request->get('db_title')), true) ?? [];
                }
                $result = null;
                if(in_array($this->makeCacheKey(),$resultKeys)){
                    $result = $this->cache->get($this->makeCacheKey()) ?? null;
                    $result = json_decode($result, true);
                }

            }
            
            if(!$result){
                $result = $this->connection->request($xSpiralApiHeader, $this->request);
            }

            
            if($this->isCache && $xSpiralApiHeader->method() == 'select'){
                $resultKeys = [];
                if($this->cache->exists($this->request->get('db_title'))){
                    $resultKeys = json_decode($this->cache->get($this->request->get('db_title')), true) ?? [];
                }
                
                if(!in_array($this->makeCacheKey(),$resultKeys)){
                    $resultKeys[] = $this->makeCacheKey();
                    $this->cache->set($this->request->get('db_title'), json_encode($resultKeys , true));
                }
                $this->cache->set($this->makeCacheKey(), json_encode($result, true));
            }
    
            if($xSpiralApiHeader->method() != 'select'){
                $resultKeys = [];
                if($this->cache->exists($this->request->get('db_title'))){
                    $resultKeys = json_decode($this->cache->get($this->request->get('db_title')), true) ?? [];
                }
    
                foreach($resultKeys as $key) {
                    $this->cache->delete($key);
                }
                
                $this->cache->delete($this->request->get('db_title'));
            }
        } else {
            $result = $this->connection->request($xSpiralApiHeader, $this->request);
        }

        return $result;
    }

    public function getFile($fileField, $keyField, $value)
    {
        $this->request->set('db_title', $this->request->get('db_title'));
        $this->request->set('file_field_title', $fileField);
        $this->request->set('key_field_title', $keyField);
        $this->request->set('key_field_value', $value);
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'get_file');
        return $this->requestBuilder($xSpiralApiHeader, $this->request);
    }

    public function create(array $create)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'insert');

        $data = [];
        foreach ($create as $key => $v) {
            $data[] = ['name' => $key, 'value' => $v];
        }

        $this->request->set('data', $data);
        $res = $this->requestBuilder($xSpiralApiHeader, $this->request);

        $create['id'] = $res['id'];

        return new Collection($create);
    }

    public function insert($insert)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject(
            'database',
            'bulk_insert'
        );

        $fields = [];
        foreach ($insert[0] as $key => $v) {
            $fields[] = $key;
        }

        $data = [];
        foreach ($insert as $i) {
            $data[] = array_values($i);
        }

        $this->request->set('columns', $fields);

        foreach (array_chunk($data, 1000) as $d) {
            $this->request->set('data', $d);

            $this->requestBuilder($xSpiralApiHeader, $this->request);
        }

        return true;
    }

    public function update($update)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'update');

        $data = [];
        foreach ($update as $key => $v) {
            $data[] = ['name' => $key, 'value' => $v];
        }

        $this->request->set('data', $data);
        $res = $this->requestBuilder($xSpiralApiHeader, $this->request);

        return (int) $res['count'];
    }

    public function upsert($uniqKey, $upsert)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'upsert');

        $data = [];
        foreach ($upsert as $key => $v) {
            $data[] = ['name' => $key, 'value' => $v];
        }

        $this->request->set('key', $uniqKey);
        $this->request->set('data', $data);

        $res = $this->requestBuilder($xSpiralApiHeader, $this->request);

        return (int) $res['status'];
    }

    public function setFile($field , $name , $binary){
        $files = $this->request->get('files') ?? [];
        $files[] = [ 'field' => $field, 'name' => $name, 'binary' => $binary];
        $this->request->set('files', $files);
        return $this;
    }

    public function delete(): int
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'delete');
        $res = $this->requestBuilder($xSpiralApiHeader, $this->request);
        return (int) $res['count'];
    }

    public function destroy($id)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'delete');
        $this->where('id', $id, '=');
        $this->requestBuilder($xSpiralApiHeader, $this->request);
    }

    public function updateBulk($primaryKey, $update)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject(
            'database',
            'bulk_update'
        );

        $fields = [];
        foreach ($update[0] as $key => $v) {
            $fields[] = $key;
        }

        $data = [];
        foreach ($update as $i) {
            $data[] = array_values($i);
        }

        $this->request->set('columns', $fields);
        $this->request->set('key', $primaryKey);

        $count = 0;

        foreach (array_chunk($data, 1000) as $d) {
            $this->request->set('data', $d);
            $res = $this->requestBuilder(
                $xSpiralApiHeader,
                $this->request
            );
            $count = $count + count($res['results']);
        }

        return $count;
    }

    public function upsertBulk($primaryKey, $upsert)
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject(
            'database',
            'bulk_upsert'
        );

        $fields = [];
        foreach ($upsert[0] as $key => $v) {
            $fields[] = $key;
        }

        $data = [];
        foreach ($upsert as $i) {
            $data[] = array_values($i);
        }

        $this->request->set('columns', $fields);
        $this->request->set('key', $primaryKey);

        $count = 0;

        foreach (array_chunk($data, 1000) as $d) {
            $this->request->set('data', $d);
            $res = $this->requestBuilder(
                $xSpiralApiHeader,
                $this->request
            );
            $count = $count + count($res['results']);
        }

        return $count;
    }

    public function schema()
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject('database', 'get');
        $response = $this->requestBuilder(
            $xSpiralApiHeader,
            $this->request
        );
        return new Collection($response['schema']);
    }

    public function registedRecordCount()
    {
        $db = \SpiralDB::title($this->request->get('db_title'))->paginate(1);
        return $db->getTotal();
    }

    public function reInstance()
    {
        return (new self($this->connection))->setTitle(
            $this->request->get('db_title')
        );
    }

    public function nextId()
    {
        $db = \SpiralDB::title($this->request->get('db_title'))
            ->orderBy('id', 'desc')
            ->paginate(1);
        return $db->getData()[0]->id + 1;
    }
}

class SearchCondition
{
    private string $field;
    private string $value;
    private string $operator;
    private bool $isAnd;

    public function __construct(
        string $field,
        string $value,
        string $operator = '=',
        bool $isAnd = true
    ) {
        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;
        $this->isAnd = $isAnd;
    }

    public function getRequestParam()
    {
        return [
            'name' => $this->field,
            'value' => $this->value,
            'operator' => $this->operator,
            'logical_connection' => $this->isAnd ? 'and' : 'or',
        ];
    }
}
