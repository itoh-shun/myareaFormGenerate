<?php

namespace SiLibrary\SpiralConnecter;
class HttpRequest
{
    protected $url = '';
    protected $httpHeader = [];

    public function __construct()
    {
    }
    public function setUrl(string $url = '')
    {
        $this->url = $url;
    }

    public function setHeader(array $header = [])
    {
        $this->httpHeader = $header;
    }

    public function get(HttpRequestParameter $param)
    {
        $curl = curl_init();
        curl_setopt(
            $curl,
            CURLOPT_URL,
            $this->url . '?' . http_build_query($param)
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->httpHeader);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public function post(HttpRequestParameter $param, $files = [])
    {
        if(!empty($files) || count($files) > 0){
            return $this->multiPartPost($param, $files);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param->toJson());
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->httpHeader);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public function multiPartPost(HttpRequestParameter $param, $files = [])
    {
        $boundary = '----' . md5(time());
        $delimiter = "--" . $boundary . "\r\n";
        $data = '';

        // パラメータの追加
        $data .= $delimiter;
        $data .= "Content-Type: application/json; charset='UTF-8';\r\n";
        $data .= 'Content-Disposition: form-data; name="json'."\"\r\n\r\n" . $param->toJson();
        $data .= "\r\n\r\n";
        // ファイルの追加
        foreach ($files as $file) {
            $data .= $delimiter;
            $data .= "Content-Type: application/octet-stream;\r\n";
            $data .= 'Content-Disposition: form-data; name="' . $file['field'] . '"; filename="' . $file['name'] . "\"\r\n\r\n";
            $data .= $file['binary'];
            $data .= "\r\n\r\n";
        }

        // 最後のデリミタを追加
        $data .= "--" . $boundary . "--\r\n";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            $this->httpHeader[0],
            'Content-Type: multipart/form-data; boundary=' . $boundary
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    public function postBulk(array $params)
    {
        //[参考]　https://qiita.com/Hiraku/items/1c67b51040246efb4254
        $params = array_map(function (HttpRequestParameter $param) {
            return $param;
        }, $params);

        $mh = curl_multi_init();
        $urls = [];
        foreach ($params as $param) {
            $urls[] = $this->url;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param->toJson());
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->httpHeader);
            curl_multi_add_handle($mh, $curl);
        }

        do {
            $stat = curl_multi_exec($mh, $running);
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        if (!$running || $stat !== CURLM_OK) {
            throw new \RuntimeException('リクエストが開始出来なかった。');
        }
        $responses = [];
        do {
            switch (curl_multi_select($mh, 30)) {
                case -1:
                    usleep(10);
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);
                    continue 2;

                case 0:
                    continue 2;

                default:
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);

                    do {
                        if ($raised = curl_multi_info_read($mh, $remains)) {
                            $response = curl_multi_getcontent(
                                $raised['handle']
                            );

                            if ($response === false) {
                                throw new \RuntimeException('リクエストエラー');
                            }
                            //$info = curl_getinfo($raised['handle']);
                            //$url = parse_url($info['url']);
                            //parse_str($url['query'], $query);
                            //$res['queryParam'] = $query;
                            //$res['result'] = $response;
                            $responses[] = json_decode($response);
                            curl_multi_remove_handle($mh, $raised['handle']);
                            curl_close($raised['handle']);
                        }
                    } while ($remains);
            };
        } while ($running);
        return $responses;
    }

    public function getBulk(array $params)
    {
        //[参考]　https://qiita.com/Hiraku/items/1c67b51040246efb4254
        $params = array_map(function (HttpRequestParameter $param) {
            return $param;
        }, $params);

        $mh = curl_multi_init();
        $urls = [];
        foreach ($params as $param) {
            $urls[] = $this->url . '?' . http_build_query($param);
            $curl = curl_init();
            curl_setopt(
                $curl,
                CURLOPT_URL,
                $this->url . '?' . http_build_query($param)
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->httpHeader);
            curl_multi_add_handle($mh, $curl);
        }

        do {
            $stat = curl_multi_exec($mh, $running);
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        if (!$running || $stat !== CURLM_OK) {
            throw new \RuntimeException('リクエストが開始出来なかった。');
        }
        $responses = [];
        do {
            switch (curl_multi_select($mh, 30)) {
                case -1:
                    usleep(10);
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);
                    continue 2;

                case 0:
                    continue 2;

                default:
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);

                    do {
                        if ($raised = curl_multi_info_read($mh, $remains)) {
                            $response = curl_multi_getcontent(
                                $raised['handle']
                            );

                            if ($response === false) {
                                throw new \RuntimeException('リクエストエラー');
                            }
                            $info = curl_getinfo($raised['handle']);
                            $url = parse_url($info['url']);
                            parse_str($url['query'], $query);
                            $res['queryParam'] = $query;
                            $res['result'] = $response;
                            $responses[] = $res;
                            curl_multi_remove_handle($mh, $raised['handle']);
                            curl_close($raised['handle']);
                        }
                    } while ($remains);
            };
        } while ($running);
        return $responses;
    }
}
