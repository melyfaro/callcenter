<?php namespace App\Http\Controllers;

use App\Claim;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Property;
use App\ACME\Model\PropertyTypes;
use Auth;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support;
use Request;

class ClaimController extends Controller {

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
        $fluid = true;
        if(Auth::user()->checkRole('client')){
            $claims = Claim::client(Request::all())->get();
        }else{
            $claims = Claim::search(Request::all())->get();
        }

		return view('claim.index',compact('claims','fluid'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$claim = new Claim();
        $properties = [];
        if(Request::get('project_id'))
        {
            $claim->project_id = (int)Request::get('project_id');
            $properties  = Property::getPropertyByModel($claim);
        }

        return view('claim.create',compact('claim','properties'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Requests\CreateClaimRequest $request)
	{
        $request = $request->all();
        $request['operator_id'] = Auth::user()->id;
        $request['status']='N';
        $claim = new Claim($request);
        $propertyList = array();
        $errors =new Support\MessageBag();
        if(!empty($request["property"]))
        {
            $properties  = Property::getPropertyByModel($claim);

            foreach($properties as $property)
            {
                try{
                    if(isset($request["property"][$property->id]))
                    {
                        $attributes = [
                            'value'=>$request["property"][$property->id],
                            'property_id'=>$property->id
                        ];
                        if($property->type=='date')
                        {
                            $pr = new PropertyTypes\DateProperty($attributes,$property->title);
                        }elseif($property->type=='number'){
                            $pr = new PropertyTypes\NumberProperty($attributes,$property->title);
                        }
                        else{
                            $pr = new PropertyTypes\TextProperty();
                            $pr->value = $request["property"][$property->id];
                            $pr->property_id = $property->id;
                        }
                    }
                    $propertyList[] = $pr;
                }catch(ValidationException $e){
                    $errors->merge($e->errors());
                }
            }
        }
        if($errors->count()>0)
        {
            return \Redirect::back()->withInput($request)->withErrors($errors);
        }
        $claim->save($request);
        foreach($propertyList as $pr){
            $pr->element_id = $claim->id;
            $pr->save();
        }
        return redirect('claim');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{

		$claim  = Claim::findOrFail($id);

        if(Auth::user()->checkRole('client') && Auth::user()->id != $claim->project->client_id) abort(404);

        return view('claim.show',compact('claim'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
        $claim = Claim::findOrFail($id);

        $properties  = Property::getPropertyByModel($claim);

        return view('claim.edit',compact('claim','properties'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id,Requests\CreateClaimRequest $request)
	{
        $request = $request->all();
        $claim =Claim::findOrFail($id);
        $request['update_by'] = Auth::user()->id;
        $claim->update($request);
        return redirect("claim/$id");
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
        $claim =Claim::findOrFail($id);
        $claim->delete();
        return redirect('/claim');
	}

}
