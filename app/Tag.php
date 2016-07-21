<?php

namespace App;

use Eloquent;

class Tag extends Eloquent {

    protected $guarded = [''];
    public $timestamps = false;

    public function games(){ return $this->morphedByMany('App\Game', 'taggable'); }
    public function channels(){ return $this->morphedByMany('App\Channel', 'taggable'); }

}