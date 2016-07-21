<?php

namespace App\Http\Controllers;

use App\Channel;

class ChannelController extends Controller {

    protected $g;

    public function __construct()
    {
        $this->g = new Channel();
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

    public function getQuick($name = null)
    {
        if(!$name) return response('', 400);
        if(!$chan = Channel::where('name', $name)->first()) return response('', 404);
        return $chan->toJson();
    }

}