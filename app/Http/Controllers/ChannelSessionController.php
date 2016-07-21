<?php

namespace App\Http\Controllers;

use App\ChannelBlock;

class ChannelSessionController extends Controller {

    protected $gs;
    protected $r;

    public function __construct()
    {
        $this->r = request();
        $this->gs = ChannelBlock::query();
    }

    public function getIndex()
    {
        $r = $this->r;

        $id         = $r->get('id', null);
        $ids        = $r->get('id_list', null);
        $start_time = $r->get('start_time', null);
        $live       = $r->get('live', true);

        if($id && $ids) return response('', 400);

        if($id) {
            $this->gs->where('channel_id', $id);
        } elseif($ids) {
            $this->gs->whereIn('channel_id', str_getcsv($ids));
        } else {
            return response('', 400);
        }

        if($start_time) $this->gs->where('start_time', '>', $start_time);

        if(!$live) $this->gs->whereNotNull('end_time');

        return $this->gs->get()->toJson();
    }

}