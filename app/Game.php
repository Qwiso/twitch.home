<?php

namespace App;

use Eloquent;

class Game extends Eloquent {

    protected $guarded = [''];
    public $timestamps = false;

    public function tags(){ return $this->morphToMany('App\Tag', 'taggable'); }
    public function snaps(){ return $this->hasMany('App\GameSnap'); }

}