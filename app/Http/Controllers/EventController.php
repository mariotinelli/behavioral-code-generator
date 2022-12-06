<?php

namespace App\Http\Controllers;



use App\Models\Event;



class EventController extends Controller
{
    

    public function post(){
        Event::create([ ['id', 'title', 'description', 'participants'] ]);

    }


}
