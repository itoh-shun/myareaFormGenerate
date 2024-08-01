<?php

namespace SiLibrary\SpiralConnecter;

use LogicException;

class SpiralWebManager
{
    private $connection;
    private ?HttpRequestParameter $request = null;
    private string $jsessionid = '';
    private string $autoLoginCookie = '';
    private string $myAreaTitle = '';
    private string $searchTitle = '';
    private array $searchCondition = [];

    private ?OrderBy $orderBy;
    private int $page = 1;
    private string $mstFilterTitle = '';
    private string $mstFilterValue = '';
    private string $terminalIpAddress = '';
    public function __construct(?SpiralConnecterInterface $connector = null)
    {
        if (is_null($connector)) {
            $this->connection = SpiralWeb::getConnection();
        } else {
            $this->connection = $connector;
        }
        $this->request = new HttpRequestParameter();
        $this->page(1);
        $this->jsessionid = $_COOKIE['JSESSIONID'];
    }

    public function page(int $page)
    {
        if ($page < 1) {
            throw new LogicException(
                'page must be greater than or equal to 1',
                501
            );
        }
        $this->page = $page;
        return $this;
    }

    public function setMyAreaTitle(string $myAreaTitle)
    {
        $this->myAreaTitle = $myAreaTitle;
        return $this;
    }
    public function setSearchTitle(string $searchTitle)
    {
        $this->searchTitle = $searchTitle;
        return $this;
    }
    public function orderBy(string $field, string $ascOrDesc)
    {
        $this->orderBy = new OrderBy($field, $ascOrDesc);
        return $this;
    }
    public function where(string $field, string $value, int $exists = 0, string $exType = '', int $action = 0)
    {
        $this->searchCondition[] = [
            'name' => $field,
            'value' => $value,
            'exists' => $exists,
            'ex_type' => $exType,
            'action' => $action,
        ];


        return $this;
    }
    public function getTable(int $limit)
    {
        $this->request->set('lines_per_page', $limit);
        $this->request->set('search_title', $this->searchTitle);
        $this->request->set('my_area_title', $this->myAreaTitle);
        if(!empty($this->orderBy)) {
            $this->request->set('sort', [$this->orderBy->getRequestParam()]);
        }
        $this->request->set(
            'search_condition',
            $this->searchCondition
        );
        $this->request->set('page', $this->page);
        $this->request->set('jsessionid', $this->jsessionid);

        $xSpiralApiHeader = new XSpiralApiHeaderObject('table', 'data');

        return $this->connection->request(
            $xSpiralApiHeader,
            $this->request
        );
    }
}
