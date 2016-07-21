<?php

namespace App\Http\Controllers;

use App\ChannelSnap;
use App\Game;
use App\GameSnap;
use Illuminate\Support\Collection;

class GameController extends Controller {

    protected $g;

    public function __construct()
    {
        $this->g = new Game();
    }

    public function getIndex()
    {
        $r = request();

        $id         = $r->get('id') ?: null;
        $name       = $r->get('name') ?: null;

        $ids        = $r->get('id_list') ?: null;
        $names      = $r->get('name_list') ?: null;

        if($id)     return $this->g->find($id)->toJson();
        if($ids)    return $this->g->whereIn('id', str_getcsv($ids))->get()->toJson();
        if($name)   return $this->g->where('name', $name)->get()->toJson();
        if($names)  return $this->g->whereIn('name', str_getcsv($names))->get()->toJson();

        return response('', 400);
    }

    public function getLive()
    {
        $page = request()->get('page');
        $glimit = request()->get('glimit', 25);
        $climit = request()->get('climit', 25);
        $now = ChannelSnap::max('timestamp');
        $gdata = GameSnap::where('timestamp', $now)->orderBy('viewers', 'desc')->offset($page*$glimit)->take($glimit)->get();
        $gids = $gdata->pluck('game_id')->toArray();
        $gdata = $gdata->load('game')->groupBy('game_id')->toArray();
        $cdata = ChannelSnap::whereIn('game_id', $gids)->where('timestamp', $now)->where('viewers', '>', 10)->get()->groupBy('game_id');
        /**
         * @var \Illuminate\Database\Eloquent\Collection $gset
         * @var array $gdata
         */
        foreach($cdata as $gid => $gset)
        {
            $foo = $gset->splice(0, $climit)->load('channel');
            $gdata[$gid]['channels'] = $foo;
        }
        return json_encode($gdata);
    }
}