<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Event;



class EventController extends Controller
{
    

    public function addEvent(Request $request){
        Event::create([ 'title' => $request->title, 
            'description' => $request->description, 
            'participants' => $request->participants
        ]);


        return response()->json('Evento criado com sucesso',  200);

    }


}
