<?php

namespace Doitonlinemedia\SimpleFacebook;

class SimpleFacebook {

    public $client_id = null;
    public $client_secret = null;
    public $page_id = null;

    public $access_token = null;

    public $endpoint = 'https://graph.facebook.com/';

    public $fields = [
        'created_time',
        'description',
        'caption',
        'link',
        'message',
        'name',
        'message_tags',
        'permalink_url',
        'picture',
        'attachments',
    ];

    public $cachePath = __DIR__.'/cache/';
    public $useCache = true;
    public $cacheTime = 60; // in minutes

    public function __construct($client_id, $client_secret, $page_id, $extra_fields = [])
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->page_id = $page_id;
        $this->fields = array_merge($this->fields, $extra_fields);
    }

    public function token()
    {
        $response = file_get_contents($this->endpoint.'oauth/access_token?client_id='.$this->client_id.'&client_secret='.$this->client_secret.'&grant_type=client_credentials');
        $response = json_decode($response);
        $this->access_token = $response->access_token;
        return $response->access_token;
    }

    public function posts()
    {
        $file = $this->cachePath.$this->page_id.'.json';
        if($this->useCache && file_exists($file)) {
            $response = file_get_contents($file);
            $response = json_decode($response);
            $date = date('Y-m-d H:i:s', strtotime('-'.$this->cacheTime.' minutes'));
            if($response->last_updated < $date) {
                $this->token();
                $response = file_get_contents($this->endpoint.$this->page_id.'/feed/?fields='.implode(',', $this->fields).'&access_token='.$this->access_token);
                $response = json_decode($response);
            }
        }else {
            $this->token();
            $response = file_get_contents($this->endpoint . $this->page_id . '/feed/?fields=' . implode(',', $this->fields) . '&access_token=' . $this->access_token);
            $response = json_decode($response);
        }

        if($this->useCache && $response->last_updated < $date) {
            $response->last_updated = date('Y-m-d H:i:s');
            if (!is_dir($this->cachePath)) mkdir($this->cachePath);
            file_put_contents($file, json_encode($response));
        }
        return collect($response->data);
    }
}