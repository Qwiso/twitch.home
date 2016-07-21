<?php

namespace App\Http\Controllers;

use App\ChannelSnap;

class ChannelSnapController extends Controller {

    protected $gs;
    protected $r;

    public function __construct()
    {
        $this->r = request();
        $this->gs = ChannelSnap::query();
    }

    public function getIndex()
    {
        $r = $this->r;

        $limit      = $r->get('limit', null);
        $id         = $r->get('id', null);
        $ids        = $r->get('id_list', null);
        $timestamp  = $r->get('timestamp', null);
        $start_time = $r->get('start_time', null);
        $end_time   = $r->get('end_time', null);

        if($id && $ids) return response('', 400);
        if($timestamp && $start_time && $end_time) return response('', 400);

        if($id) {
            $this->gs->where('channel_id', $id);
        } elseif($ids) {
            $this->gs->whereIn('channel_id', str_getcsv($ids));
        } else {
            return response('', 400);
        }

        $limit ? $limit > 1000 ? $limit = 1000 : null : $limit = 100;

        if($timestamp) {
            $this->gs->where('timestamp', $timestamp);
        } elseif($start_time && $end_time) {
            $this->gs->whereBetween('timestamp',[$start_time, $end_time]);
        } elseif($start_time) {
            $this->gs->where('timestamp', '>', $start_time);
        } elseif($end_time) {
            $this->gs->where('timestamp', '<', $end_time);
        } else {
            $this->gs->orderBy('timestamp', 'desc');
        }

//        return $this->gs->toSql();
        return $this->gs->take($limit)->get()->toJson();
    }

}