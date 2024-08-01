<?php

namespace SiLibrary\SpiralConnecter;

use LogicException;

class SpiralExpressManager
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

    public function mailField($mailaddress)
    {
        $this->request->set('mail_field_title', $mailaddress);
        return $this;
    }

    public function reserveDate($time = 'now')
    {
        if ($time === 'now') {
            $this->request->set('reserve_date', $time);
            return $this;
        }

        if (
            $time === date('Y/m/d H:00', strtotime($time)) ||
            $time === date('Y/m/d H:30', strtotime($time))
        ) {
            $this->request->set('reserve_date', $time);
        } else {
            throw new LogicException(
                'reserveDate format is YYYY/MM/DD HH:(00 | 30)'
            );
        }

        return $this;
    }

    public function subject($subject)
    {
        $this->request->set('subject', $subject);
        return $this;
    }

    public function mailType($type)
    {
        if ($type === 'text' || $type === 'html' || $type === 'multipart') {
            $this->request->set('mail_type', $type);
            return $this;
        }

        throw new LogicException('text or html or multipart');
    }

    public function bodyText($text)
    {
        $this->request->set('body_text', $text);
        return $this;
    }

    public function bodyHtml($html)
    {
        $this->request->set('body_html', $html);
        return $this;
    }

    public function formAddress($address)
    {
        $this->request->set('from_address', $address);
        return $this;
    }

    public function formName($name)
    {
        $this->request->set('from_name', $name);
        return $this;
    }

    public function replyTo($to)
    {
        $this->request->set('reply_to', $to);
        return $this;
    }

    public function selectName($selectName)
    {
        $this->request->set('select_name', $selectName);
        return $this;
    }

    public function ruleId($ruleId)
    {
        $this->request->set('rule_id', $ruleId);
        return $this;
    }

    public function standby(bool $standby)
    {
        $this->request->set('standby', $standby ? 't' : 'f');
        return $this;
    }

    public function regist()
    {
        $xSpiralApiHeader = new XSpiralApiHeaderObject(
            'deliver_express2',
            'regist'
        );
        $request = $this->connection->request(
            $xSpiralApiHeader,
            $this->request
        );
        return $request['rule_id'];
    }

    public function sampling(array $ids)
    {
        foreach (array_chunk($ids, 100) as $id) {
            $this->request->set('ids', $id);
            $xSpiralApiHeader = new XSpiralApiHeaderObject(
                'deliver_express2',
                'sampling'
            );
            $this->connection->request($xSpiralApiHeader, $this->request);
        }
    }
}
