<?php namespace CryptoPolice\Bitcointalk\Classes;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

use Xparse\CssExpressionTranslator\CssExpressionTranslator;
use Xparse\ElementFinder\ElementFinder;

use CryptoPolice\Bitcointalk\Models\Topic as Model;
use CryptoPolice\Bitcointalk\Models\Settings;

class Topic
{
    private $models;
    private $hash;
    private $user_agent;
    private $cf_clearance;

    public function __construct($ids = null)
    {
        if(empty($ids)) {
            $this->models = Model::get();
        } else {
            $this->models = Model::findOrFail($ids);
        }

        $settings = Settings::instance();

        $this->user_agent = $settings->user_agent;
        $this->cf_clearance = $settings->cf_clearance;
    }

    public function grabbing()
    {
        foreach ($this->models as $model) {

            $num = 0;

            $page = $this->get($this->getUrl($model), ['topic' => (string)$model->bitcointalk_id . '.' . $num]);

            $last_link= $this->getlastLik($page);

            if(!$model->title){
                 $this->setTitleTopic($model, $page);
            }

            $curent_last_num =  empty($last_link) ? $num : $this->getNumPage($last_link);

            if($cache_last_page = $model->pages()->latest()->first()){

                $cache_last_num = $this->getNumPage($cache_last_page->full_url);

                $num = $cache_last_num;
            }

            while ($num <= $curent_last_num) {
                sleep(rand (2 , 4));
                $this->setPage($model, ['topic' => (string)$model->bitcointalk_id . '.' . $num]);
                $num = $num + 20;
            }
        }

        return $this->models;
    }

    private function setPage($model, $query)
    {
        $page = $this->get($this->getUrl($model), $query);

        return $model->pages()->updateOrCreate([
            'full_url'  => $this->getFullUrl($model, $query)
            ],[
            'meta'      => $query['topic'],
            'html'      => $page,
        ]);
    }

    private function getFinger($page)
    {
        $finder = new ElementFinder($page);

        $finder->setExpressionTranslator(new CssExpressionTranslator());

        return $finder;
    }

    private function setTitleTopic($model, $page)
    {
        $finder = $this->getFinger($page);
        $model->title = $finder->value('title')->getFirst();
        return $model->save();
    }

    public function getlastLik($page)
    {
        $finder = $this->getFinger($page);

        $links = $finder->value('a.navPages @href')->getItems();

        if($links){

            $links = array_unique($links);

            ksort($links);

            return array_pop($links);
        }

        return $links;
    }

    private function get($url, $query = [])
    {
        $headers = ['User-Agent' => $this->user_agent];

        $jar = CookieJar::fromArray([
            'cf_clearance'  => $this->cf_clearance,
            ], '.bitcointalk.org');

        $opt = [
            'headers'   => $headers,
            'query'     => $query,
            'cookies'   => $jar
        ];

        $response = $this->request($url, $opt);

        $body = $response->getBody();

        return $body->getContents();
    }

    private function request($url, $opt = [], $method = 'GET')
    {
        $client = new Client();

        return $client->request($method, $url, $opt);
    }

    private function getUrl($model)
    {
        return 'https://' . $model->host . '/index.php';
    }

    private function getFullUrl($model, $query = [])
    {
        return empty($query) ? $this->getUrl($model) : $this->getUrl($model) . '?'. http_build_query($query);
    }

    private function getNumPage($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $output);

        if(array_key_exists('topic', $output)) {
            $result = explode( '.', $output['topic']);

            if((int)count($result) === (int)2) {
                return (int)array_pop($result);
            }
        }

        return 0;
    }
}