<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

function print_r_pre($res){
    echo "<pre>";
    print_r($res);
    echo "</pre>";
}


Route::controllers([
    'auth' => 'Auth\AuthController',
    'password' => 'Auth\PasswordController',
]);


Route::get('/home',function(){
   return redirect('/');
});

Route::get('/',function(Request $request){
    if (Auth::guest())
    {
        if ($request::ajax())
        {
            return response('Unauthorized.', 401);
        }
        else
        {
            return redirect()->guest('auth/login');
        }
    }elseif(Auth::user()->role()->first()->code=="client")
    {
        return  redirect(url('/claim'));
    }else
    {
       return  redirect(url('/project'));
    }
});

Route::resource('project','ProjectController');

Route::resource('claim','ClaimController');
Route::post('claim/statuschange','ClaimController@postStatuschange');

Route::post('user/{id}/projects','UserController@postProjects');
Route::delete('user/{id}/projects','UserController@deleteProjects');
Route::resource('user','UserController');

Route::resource('property','PropertyController');

Route::resource('destination','DestinationController');
Route::resource('typicalDescription','TypicalDescriptionController');

Route::resource('claimType','ClaimTypeController');

Route::get('api/claims/','ApiController@getClaims');

Route::resource('callback/client','Callback\ClientController');
Route::resource('callback/logs','Callback\LogsController');
Route::resource('callback/settings','Callback\SettingsController');
Route::resource('callback/blacklist','Callback\BlacklistController');
Route::resource('callback','Callback\CallbackController');


/**
 * Подгрузка значка и формы обратного звонка
 **/
Route::get('externform',function(){
    \Debugbar::disable();

    $key = Request::input('key',null);

    if(is_null($key)) return;

    $client = \App\ACME\Model\Callback\Client::where('key','=',$key)->where('active','=',true)->first();

    if(empty($client)) return;

    $dt = new DateTime();
    if( !empty($client->sip) && (int)$dt->format('H')<9 || (int)$dt->format('H')>=21){
        $result = App()->CallbackHelper->getSendBackForm($client);
        return response ($result)->header('Content-Type','text/javascript');
    }else{
        $result = App()->CallbackHelper->getCallBackForm($client);
        return response ($result)->header('Content-Type','text/javascript');
    }
});

/**
 *  Запуск звонока
 **/
Route::get('externcall',function(){

    \Debugbar::disable();

    $key = Request::input('key',null);

    if(is_null($key)) return response()->json(['error'=>1,'message'=>'Key not found'])->header('Access-Control-Allow-Origin', '*');

    $client = \App\ACME\Model\Callback\Client::where('key','=',$key)->first();

    $sitename = $client->title;
    $name = Request::input('name',null);
    $email = Request::input('email',null);
    $text = Request::input('message',null);

    $phone = trim(Request::input('phone'));
    $sip   = Request::input('sip',$client->sip);
    $initiator = Request::input('initiator');
    $ip  = $_SERVER["REMOTE_ADDR"];


    $phoneLog = new \App\ACME\Model\Callback\PhoneLog();
    $phoneLog->client_id = $client->id;
    $phoneLog->phone = $phone;
    $phoneLog->ip = $ip;
    $phoneLog->initiator = $initiator;
    $phoneLog->save();

    $blacklists  = \App\ACME\Model\Callback\Blacklist::where('phone','=',$phone)->get()->count();
    if($blacklists>0) return response()->json(['success'=>'n','error'=>1,'message'=>'You are on blacklist'])->header('Access-Control-Allow-Origin', '*');

    if(!empty($phone))
    {
        if( empty($client->sip) ){
            $resCall =  App\ACME\Helpers\MttAPI::makeCall($client,$phone,$phoneLog);
            return response()->json($resCall)->header('Access-Control-Allow-Origin', '*');
        }else{
            $callerId = $sip;
            $chanel   = "SIP/".$sip;
            $oSocket = fsockopen(env('Asterisk_host'), env('Asterisk_port'), $errnum, $errdesc,50) or die("Connection to host failed");
            fputs($oSocket, "Action: Login\r\n");
            fputs($oSocket, "Username: ".env('Asterisk_user')."\r\n");
            fputs($oSocket, "Secret: ".env('Asterisk_secret')."\r\n\r\n");
            fputs($oSocket, "Action: originate\r\n");
            fputs($oSocket, "Channel: ".$chanel."\r\n");
            fputs($oSocket, "Timeout: ".env('Asterisk_timeout')."\r\n");
            fputs($oSocket, "CallerId: ".$callerId."\r\n");
            fputs($oSocket, "Exten: ".$phone."\r\n");
            fputs($oSocket, "Context: ".env('Asterisk_context')."\r\n");fputs($oSocket, "Priority: ".env('Asterisk_priority')."\r\n\r\n");
            fputs($oSocket, "Action: Logoff\r\n\r\n");
            sleep (1);
            fclose($oSocket);
            return response()->json(["success"=>"y"])->header('Access-Control-Allow-Origin', '*');
        }
    }else{
        return response('Не введен номер')->header('Access-Control-Allow-Origin', 'all');
    }

});

/**
 * Отправка письма
 */
Route::post('externcall',function(){
    $key = Request::input('key',null);

    if(is_null($key)) return response()->json(['error'=>1,'message'=>'Key not found'])->header('Access-Control-Allow-Origin', '*');

    $client = \App\ACME\Model\Callback\Client::where('key','=',$key)->first();

    $sitename = $client->title;


    $request = Request::instance();
    $content = $request->getContent();

    parse_str($content);


    if(empty($name) || empty($email) || empty($text)) {
        return response()->json(["success"=>"n","message"=>"Заполнены не все поля."])->header('Access-Control-Allow-Origin', '*');
    }

    if(!empty($client->sip) && $client->sip==101 )
    {
        $text  = strip_tags($text);
        $name  = strip_tags($name);
        $email = strip_tags($email);
        $out = App\ACME\Helpers\CRMHelper::sendClaim(445,12,2,
            [
                "comment"=>"Сайт:".$client->title.". ".$text,
                "fio"=>$name,
                "email"=>$email
            ]
        );
        return response()->json(["success"=>"y"])->header('Access-Control-Allow-Origin', '*');
    }

    \Mail::send('emails.callbackmessage',compact('name','email','sitename','text'), function($message) use ($client)
    {
        $emails = json_decode($client->settings->emails);
        $message->to($emails, 'Callcenter №1')->subject('Call-центр №1');
    });
    return response()->json(["success"=>"y"])->header('Access-Control-Allow-Origin', '*');

});

/**
 * Форма в CRM
 **/
Route::get('formback',function(){

    $key = Request::input('key',null);

    if(is_null($key)) return response()->json(['error'=>1,'message'=>'Key not found'])->header('Access-Control-Allow-Origin', '*');

    $client = \App\ACME\Model\Callback\Client::where('key','=',$key)->first();

    $phone = trim(Request::input('phone'));
    $blacklists  = \App\ACME\Model\Callback\Blacklist::where('phone','=',$phone)->get()->count();
    if($blacklists>0) return response()->json(['error'=>1,'message'=>'You are on blacklist'])->header('Access-Control-Allow-Origin', '*');


    $time  = Request::input('time');
    $timeSelect = array(1=>"9:00-12:00",2=>"12:00-16:00",3=>"16:00-19:00",4=>"19:00-21:00");

    $result = new stdClass();
    $result->error = 0;

    $out = App\ACME\Helpers\CRMHelper::sendClaim(445,12,2,
                [
                    "comment"=>$timeSelect[$time],
                    "fio"=>$client->title,
                    "phone"=>$phone
                ]
            );

    if ($out!='0'){
        $result->id=$out;
        return response()->json($result)->header('Access-Control-Allow-Origin', '*');
    }else{
        $result->error= 1;
        return response()->json($result)->header('Access-Control-Allow-Origin', '*');
    }
});

/**
 * Тестовая форма
 **/
Route::get('getform',function(){
    $client = App\ACME\Model\Callback\Client::first();
    return view('home')->with(compact('client'));
});