<?php

use SiLibrary\SpiralConnecter\SpiralConnecterInterface;
use SiLibrary\SpiralConnecter\SpiralFilterManager;
use SiLibrary\SpiralConnecter\SpiralExpressManager;
use SiLibrary\SpiralConnecter\SpiralManager;
use SiLibrary\SpiralConnecter\SpiralConnecter;
use SiLibrary\SpiralConnecter\SpiralApiConnecter;
use SiLibrary\SpiralConnecter\SpiralRedis;

class SpiralDB
{
    protected string $title = '';

    protected array $fields = [];

    private static string $token = '';

    private static string $secret = '';

    private static ?SpiralConnecterInterface $connecter = null;

    private static ?SpiralRedis $cache = null;

    public static function setConnecter(SpiralConnecterInterface $connecter): void
    {
        if(!class_exists('SiLibrary\Collection')){
            throw new \LogicException('Not Collection Library');
        }

        self::$connecter = $connecter;
    }

    public static function setCache(SpiralRedis $cache)
    {
        self::$cache = $cache;
    }

    public static function cacheFor(int $timeout = 900){
        if($timeout > 900 || $timeout < 1){
            throw new \LogicException('Invalid timeout value. It should be between 1 and 900.',500);
        }

        if(!class_exists('SpiralRedis')){
            throw new \LogicException('Not SpiralRedis Library');
        }

        self::$cache && self::$cache->setTimeout($timeout);
    }

    public static function setToken(string $token, string $secret): void
    {
        self::$token = $token;
        self::$secret = $secret;
    }

    public static function filter($title)
    {
        return (new SpiralFilterManager(self::$connecter))->setTitle(
            $title
        );
    }

    public static function mail($title)
    {
        return (new SpiralExpressManager(self::$connecter))->setTitle(
            $title
        );
    }

    public static function title($title)
    {
        return (new SpiralManager(self::$connecter , self::$cache))->setTitle($title);
    }

    public static function getConnection()
    {
        if (class_exists('Spiral') && (self::$token == '' && self::$secret == '')) {
            return new SpiralConnecter(spiral());
        }

        return new SpiralApiConnecter(self::$token, self::$secret);
    }
}

abstract class SpiralModel
{
    protected string $primaryKey = 'id';

    protected array $fields = [];

    protected string $db_title = '';

    protected $manager = null;

    public function __construct(){}

    public function __get($name)
    {
        if (in_array($name, $this->fields) || $name === 'id') {
            return $this->$name ?? null;
        }
        throw new \Exception("Property {$name} does not exist.");
    }

    public function __set($name, $value): void
    {
        if (in_array($name, $this->fields) || $name === 'id') {
            $this->$name = $value;
        } else {
            throw new \Exception("Property {$name} cannot be set.");
        }
    }

    protected function init()
    {
        $this->manager = SpiralDB::title($this->db_title)
            ->fields($this->fields);
    }

    protected function getManager()
    {
        if (!$this->manager) {
            $this->init();
        }
        return $this->manager;
    }

    public static function instance(){
        /** @phpstan-ignore-next-line */
        $instance = new static();
        return $instance->getManager();
    }

    // 主キーによるレコードの取得
    public static function find($value)
    {
        /** @phpstan-ignore-next-line */
        $instance = new static();
        $data = $instance->getManager()->where($instance->primaryKey, $value)->get();  // ここを修正
        if ($data->first()) {
            // データベースから取得したデータを使用して新しいインスタンスを作成
            /** @phpstan-ignore-next-line */
            $modelInstance = new static();

            // 新しいインスタンスの各プロパティにデータを設定
            foreach ($data->first() as $key => $value) {
                $modelInstance->$key = $value;
            }
            return $modelInstance;
        }

        return null;  // データが見つからない場合はnullを返す
    }

    // すべてのレコードを取得
    public static function all()
    {
        /** @phpstan-ignore-next-line */
        $instance = new static();
        $data = $instance->getManager()->get();

        $models = [];

        if ($data) {
            foreach ($data as $d) {
                // データベースから取得したデータを使用して新しいインスタンスを作
                /** @phpstan-ignore-next-line */
                $modelInstance = new static();

                // 新しいインスタンスの各プロパティにデータを設定
                foreach ($d as $key => $value) {
                    $modelInstance->$key = $value;
                }

                $models[] = $modelInstance;
            }
        }

        return $models;
    }
    

    // レコードの保存 (新規作成または更新)
    public function save()
    {
        // モデルのプロパティを連想配列として取得
        $allProperties = get_object_vars($this);

        $data = array_filter($allProperties, function ($key) {
            return in_array($key, $this->fields);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($data as $key => $value) {
            if ($value === null) {
                // nullの場合、空文字列で上書き
                $data[$key] = '';
            }
        }
        
        if($this->primaryKey !== 'id'){
            unset($data['id']);
        }

        // 主キーの値を取得
        $primaryKeyValue = $data[$this->primaryKey] ?? null;
        
        if ($primaryKeyValue) {
            // 主キーの値が存在する場合、更新または挿入を行う
            $this->getManager()->upsert($this->primaryKey, $data);
        } else {
            // 主キーの値が存在しない場合、新規挿入のみを行う
            unset($data[$this->primaryKey]);
            $data = $this->getManager()->create($data);
            $this->id = (int) $data->id;
        }
    }

    // レコードの削除
    public function delete(): void
    {
        // モデルの主キーの値を取得
        $primaryKeyValue = $this->{$this->primaryKey} ?? null;

        if ($primaryKeyValue) {
            // 主キーの値が存在する場合、該当するレコードを削除
            $this->getManager()->where($this->primaryKey, $primaryKeyValue)->delete();
        }
    }
}
