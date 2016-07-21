<?php

namespace App\Workers;

use App\Channel;
use App\ChannelSnap;
use App\ChannelSession;
use App\Game;
use App\GameSnap;
use Carbon\Carbon;
use DB;
use File;

class TwitchAPIWorker
{

    protected $timestamp;
    protected $data;
    protected $start;

    public function __construct()
    {
        $this->timestamp = Carbon::createFromFormat('H:i', date('H:i', floor(time() / (5 * 60)) * (5 * 60)));
    }

    private function log($msg)
    {
        File::append('/var/www/html/ggvods/storage/logs/crawler.log', $msg."\n");
    }

    /**
     * @param $data
     * @param $start
     * @return void
     */
    public function importBlob($data, $start)
    {
        $this->start = $start;
        $this->log('*+ import started -- '.date('i:s', time() - $this->start));
        $this->data = (array)$data;
        $this->newGames();
    }

    private function newGames()
    {
        $live_game_names = array_keys($this->data);
        $known_game_names = Game::pluck('name')->toArray();
        $new_game_names = array_diff($live_game_names, $known_game_names);
        $new_games = [];
        foreach ($new_game_names as $name) {
            $game = [
                'name' => $name,
                'slug' => str_slug($name),
                'image' => $this->data[$name]['summary']['game']['box']['large']
            ];
            array_push($new_games, $game);
        }
        Game::insert($new_games);
        $this->gameSnaps($live_game_names);
    }

    private function gameSnaps($live_game_names)
    {
        $known_games = Game::whereIn('name', $live_game_names)->get()->groupBy('name')->toArray();
        $gamesnaps = [];
        $channelsnaps = [];
        $live_channels = [];
        foreach ($this->data as $name => $gamedata) {
            $gamesnap = [
                'game_id' => $known_games[$name][0]['id'],
                'viewers' => $gamedata['summary']['viewers'],
                'channels' => $gamedata['summary']['channels'],
                'timestamp' => $this->timestamp->toDateTimeString()
            ];
            array_push($gamesnaps, $gamesnap);

            if (isset($gamedata['streams'])) {
                foreach ($gamedata['streams'] as $streamdata) {
                    $channel_name = $streamdata['channel']['name'];
                    $channel = [
                        'name' => $channel_name,
                        'display_name' => $streamdata['channel']['display_name'],
                        'established' => $streamdata['channel']['created_at'],
                        'language' => $streamdata['channel']['language'],
                        'partner' => $streamdata['channel']['partner'] ? 1 : 0
                    ];
                    $live_channels[$channel_name] = $channel;

                    $channel_snap = [
                        'game_id' => $known_games[$name][0]['id'],
                        'viewers' => $streamdata['viewers'],
                        'followers' => $streamdata['channel']['followers'],
                        'start_time' => $streamdata['created_at'],
                        'timestamp' => $this->timestamp
                    ];
                    $channelsnaps[$channel_name] = $channel_snap;
                }
            }
        }
        GameSnap::insert($gamesnaps);
        $this->newChannels($live_channels, $channelsnaps);
    }

    private function newChannels($live_channels, $channelsnaps)
    {
        $known_channel_names = Channel::pluck('name')->toArray();
        $live_channel_names = array_keys($live_channels);
        $new_channel_names = array_diff($live_channel_names, $known_channel_names);
        $new_channels = [];
        foreach ($new_channel_names as $new_channel_name) {
            array_push($new_channels, $live_channels[$new_channel_name]);
        }
        $cchunks = array_chunk($new_channels, count($new_channels) / 10);
        foreach ($cchunks as $chunk) {
            Channel::insert($chunk);
        }
        $this->channelSnaps($live_channel_names, $channelsnaps);
    }

    private function channelSnaps($live_channel_names, $channelsnaps)
    {
        $known_channels = Channel::whereIn('name', $live_channel_names)->get()->groupBy('name')->toArray();
        $new_channel_snaps = [];
        foreach($channelsnaps as $name => $snap)
        {
            $snap['channel_id'] = $known_channels[$name][0]['id'];
            array_push($new_channel_snaps, $snap);
        }
        $csschunks = array_chunk($new_channel_snaps, count($new_channel_snaps)/10);
        foreach($csschunks as $chunk)
        {
            ChannelSnap::insert($chunk);
        }
        $this->log('*+ import done, calling proc -- '.date('i:s', time() - $this->start));
//        DB::select("call processSnapsToBlocks()");
        $this->log('***+++ complete -- '.date('i:s', time() - $this->start).' +++***');
    }
}