<?php namespace App;

use App\Http\Requests;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Project extends Model {

    protected $fillable=[
        'title',
        'status',
        'text',
        'note',
        'client_id',
        'manager_id',
        'update_by',
        'reports_type',
        'hour_start',
        'sort'
    ];

    public function setHourStartAttribute($value)
    {
        if($this->attributes["reports_type"]!='daily')
        {
            $this->attributes["hour_start"] = null;
        }else{
            $this->attributes["hour_start"] = $value;
        }
    }


    public function scopeOperator($query,$request)
    {
        if(!empty($request['created_at_from']) && !empty($request['created_at_to']))
        {

            $dtFrom = new \DateTime($request['created_at_from']);
            $dtTo = new \DateTime($request['created_at_to']);
            $query->whereBetween('created_at',[$dtFrom->format('Y-m-d 00:00:00'),$dtTo->format('Y-m-d 23:59:59')]);
        }



        if(!empty($request['id']))
        {
            $query->where('id',(int)$request['id']);
        }

        if(!empty($request['title']))
        {
            $query->where('title','like',"%".$request['title']."%");
        }


        $query->where('status','D');


        if(!empty($request['manager_id']))
        {
            $query->where('manager_id',(int)$request['manager_id']);
        }


        if(!empty($request['client_id']))
        {
            $query->where('client_id',(int)$request['client_id']);
        }

        return $query;
    }


    public function scopeSearch($query,$request)
    {

        if(!empty($request['created_at_from']) && !empty($request['created_at_to']))
        {

            $dtFrom = new \DateTime($request['created_at_from']);
            $dtTo = new \DateTime($request['created_at_to']);
            $query->whereBetween('created_at',[$dtFrom->format('Y-m-d 00:00:00'),$dtTo->format('Y-m-d 23:59:59')]);
        }



        if(!empty($request['id']))
        {
            $query->where('id',(int)$request['id']);
        }

        if(!empty($request['title']))
        {
            $query->where('title','like',"%".$request['title']."%");
        }


        if(!empty($request['status']))
        {
            $query->where('status',$request['status']);
        }

        if(!empty($request['manager_id']))
        {
            $query->where('manager_id',(int)$request['manager_id']);
        }


        if(!empty($request['client_id']))
        {
            $query->where('client_id',(int)$request['client_id']);
        }

        return $query;

    }


    public function client()
    {
        return $this->belongsTo('App\User','client_id','id');
    }

    public function manager()
    {
        return $this->belongsTo('App\User','manager_id','id');
    }

    public function statusT()
    {
        return $this->belongsTo('App\Status','status','code');
    }

    public function updateby()
    {
        return $this->belongsTo('App\User','update_by','id');
    }

    public function typicalDescriptions()
    {
        return $this->hasMany('App\TypicalDescription');
    }

    public function claims()
    {
        return $this->hasMany('App\Claim');
    }
}
