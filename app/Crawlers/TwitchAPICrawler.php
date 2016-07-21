<?php

namespace App\Crawlers;

use App\Channel;
use App\ChannelSnap;
use App\Game;
use App\GameSnap;
use Carbon\Carbon;
use DB;
use File;
use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Pool;

class TwitchAPICrawler {

    protected $timestamp;
    protected $start;

    protected $blob = [];

    protected $client;

    public function __construct()
    {
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '2048M');;
        $this->timestamp = Carbon::createFromFormat('H:i', date('H:i', floor(time()/(5*60)) * (5*60)));
        $this->start = time();
        $this->client = new Client;
    }

    private function log($msg)
    {
        File::append('/var/www/html/ggvods/storage/logs/crawler.log', $msg."\n");
    }

    public function bang()
    {
        $this->log('***+++ new crawl started @ '.$this->timestamp);
        $this->getGames();
    }

    private function getGames()
    {
        $game_url = "https://api.twitch.tv/kraken/games/top?limit=100&offset=";
        $offset = 0;

        $data = json_decode($this->client->get($game_url . $offset)->getBody()->getContents());

        $game_pool_urls = [];
        while($offset <= $data->_total)
        {
            array_push($game_pool_urls, $game_url . $offset);
            $offset += 100;
        }

        $game_pool_requests = [];
        foreach($game_pool_urls as $url)
        {
            $req = $this->client->createRequest("GET", $url);
            $req->getEmitter()->on('complete', function(CompleteEvent $event) {
                $data = json_decode($event->getResponse()->getBody()->getContents());
                foreach($data->top as $item)
                {
                    if($item->viewers >= 10)
                    {
                        $this->blob[$item->game->name]['details'] = $item;
                        $this->blob[$item->game->name]['streams'] = [];
                    }
                }
            });
            array_push($game_pool_requests, $req);
        }

        echo ".";
        $game_pool = new Pool($this->client, $game_pool_requests);
        $game_pool->wait();

        $this->getStreamsForGames();
    }

    private function getStreamsForGames()
    {
        $first_request_pool = [];
        $deep_urls = [];

        foreach($this->blob as $name => $item)
        {
            $stream_url = "https://api.twitch.tv/kraken/streams?limit=1&game=$name&offset=";
            $req = $this->client->createRequest("GET", $stream_url);
            $req->getEmitter()->on('complete', function(CompleteEvent $event) use(&$deep_urls) {
                $d = json_decode($event->getResponse()->getBody()->getContents());
                $offset = 0;
                while($offset <= $d->_total)
                {
                    $url = "https://api.twitch.tv/kraken/streams?limit=100&game=" . $d->streams[0]->game . "&offset=" . $offset;
                    array_push($deep_urls, $url);
                    $offset += 100;
                }
            });
            array_push($first_request_pool, $req);
        }

        echo ".";
        $pool2 = new Pool($this->client, $first_request_pool);
        $pool2->wait();

        $deep_request_pool = [];
        foreach($deep_urls as $url)
        {

            $req = $this->client->createRequest("GET", $url);
            $req->getEmitter()->on('complete', function(CompleteEvent $event) {

                $data = json_decode($event->getResponse()->getBody()->getContents());

                foreach($data->streams as $item)
                {
                    if($item->viewers >= 10)
                    {
                        array_push($this->blob[$item->game]['streams'], $item);
                    }
                }
            });
            array_push($deep_request_pool, $req);
        }

        echo ".";
        $pool3 = new Pool($this->client, $deep_request_pool);
        $pool3->wait();

        $this->insertData();
    }

    private function insertData()
    {
        $known_game_names = Game::pluck('name')->toArray();
        $live_game_names = array_keys($this->blob);
        $new_game_names = array_diff($live_game_names, $known_game_names);
        $new_games = [];

        foreach($new_game_names as $name)
        {
            if(isset($this->blob[$name]['details']))
            {
                $game = [
                    'name'  => $name,
                    'slug'  => str_slug($name),
                    'image' => $this->blob[$name]['details']->game->box->large
                ];
                array_push($new_games, $game);
            }
        }
        Game::insert($new_games);

        $existing_game_data = Game::whereIn('name', $live_game_names)->get()->groupBy('name')->toArray();
        $game_snaps = [];
        $channel_data = [];

        foreach($this->blob as $name => $item)
        {
            if(isset($existing_game_data[$name]))
            {
                $game_snap = [
                    'game_id'   => $existing_game_data[$name][0]['id'],
                    'viewers'   => $item['details']->viewers,
                    'channels'  => $item['details']->channels,
                    'timestamp' => $this->timestamp
                ];
                array_push($game_snaps, $game_snap);

                foreach($item['streams'] as $stream)
                {
                    $channel = [
                        'name' => $stream->channel->name,
                        'display_name' => $stream->channel->display_name,
                        'language' => $stream->channel->language,
                        'established' => $stream->channel->created_at,
                        'partner' => $stream->channel->partner
                    ];
                    $channel_data[$stream->channel->name]['data'] = $channel;

                    $channel_snap = [
                        'game_id'   => $existing_game_data[$name][0]['id'],
                        'viewers'   => $stream->viewers,
                        'followers' => $stream->channel->followers,
                        'start_time' => $stream->created_at,
                        'timestamp' => $this->timestamp
                    ];
                    $channel_data[$stream->channel->name]['snap'] = $channel_snap;
                }
            }
        }
        GameSnap::insert($game_snaps);

        $known_channel_names = Channel::pluck('name')->toArray();
        $live_channel_names = array_keys($channel_data);
        $new_channel_names = array_diff($live_channel_names, $known_channel_names);
        $new_channels = [];
        foreach($new_channel_names as $name)
        {
            array_push($new_channels, $channel_data[$name]['data']);
        }
        Channel::insert($new_channels);

        $known_channels = Channel::whereIn('name', $live_channel_names)->get()->groupBy('name')->toArray();
        $channel_snaps = [];
        foreach($channel_data as $name => $item)
        {
            $snap = $item['snap'];
            $snap['channel_id'] = $known_channels[$name][0]['id'];
            array_push($channel_snaps, $snap);
        }
        ChannelSnap::insert($channel_snaps);

        $this->log('*+ import done, calling proc -- '.date('i:s', time() - $this->start));
//        DB::select("call processSnapsToBlocks()");
        $this->log('***+++ complete -- '.date('i:s', time() - $this->start).' +++***');
    }
}