<?php

namespace SiLibrary\SpiralConnecter;

use SiLibrary\Collection;
use stdClass;

class Paginator extends stdClass
{
    private Collection $data ;
    private int $currentPage = 1;
    private int $from = 1;
    private int $lastPage = 0;
    private int $limit = 0;
    private int $total = 0;
    private OrderBy $orderBy;

    public static array $sortSymbol = ['asc' => '▲', 'desc' => '▼'];

    public function __construct(
        Collection $data,
        int $currentPage,
        int $from,
        int $lastPage,
        int $limit,
        int $total,
        OrderBy $orderBy = null
    ) {
        $this->data = $data;
        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }
        $this->currentPage = $currentPage;
        $this->from = $from;
        $this->lastPage = $lastPage;
        $this->limit = $limit;
        $this->total = $total;
        $this->orderBy = $orderBy;
    }

    public function getData()
    {
        return $this->data->all();
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getLastPage()
    {
        return $this->lastPage;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setData(Collection $data)
    {
        return new self($data , $this->currentPage ,  $this->from, $this->lastPage, $this->limit ,$this->total , $this->orderBy );
    }

    public function sortSymbol($key)
    {
        if ($this->orderBy->field === $key) {
            return self::$sortSymbol[$this->orderBy->ascOrDesc];
        }
        return '';
    }

    private function rangeWithDots()
    {
        $current = $this->currentPage;
        $last = $this->lastPage;
        $delta = 2;
        $left = $current - $delta;
        $right = $current + $delta + 1;
        $range = [];
        $rangeWithDots = [];
        $l = 0;

        for ($i = 1; $i <= $last; $i++) {
            if ($i == 1 || $i == $last || ($i >= $left && $i < $right)) {
                $range[] = $i;
            }
        }

        foreach ($range as $i) {
            if ($l) {
                if ($i - $l === 2) {
                    $rangeWithDots[] = $l + 1;
                } elseif ($i - $l !== 1) {
                    $rangeWithDots[] = '...';
                }
            }
            $rangeWithDots[] = $i;
            $l = $i;
        }

        return $rangeWithDots;
    }
}
