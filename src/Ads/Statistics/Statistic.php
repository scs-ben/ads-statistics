<?php namespace Ads\Statistics;

use \Auth;
use \Config;
use \Exception;
use \Input;
use \Request;
use \Route;
use \View;

class Statistic extends \Eloquent {

	// Don't forget to fill this array
	protected $fillable = [];
	
	public static function httpError($request, Exception $e)
	{
		$statistic = Statistic::findOrNew($request->session()->get('statistic_id'));
				
		$statistic->http_code = http_response_code();
		$statistic->errorFile = $e->getFile();
		$statistic->errorLine = $e->getLine();
		$statistic->errorMessage = $e->getMessage() . '\r\nTRACE\r\n' . $e->getTraceAsString();
		
		$statistic->save();
	}
	
	public static function fatalError($request, Exception $e)
	{
		$statistic = Statistic::findOrNew($request->session()->get('statistic_id'));
		
		$statistic->http_code = http_response_code();
		$statistic->errorFile = $e->getFile();
		$statistic->errorLine = $e->getLine();
		$statistic->errorMessage = $e->getMessage() . '\r\nTRACE\r\n' . $e->getTraceAsString();
		
		$statistic->save();
	}
	
	public function logStatistics($route, $request)
	{
		$statistic = new Statistic;
		
		$statistic->ip_address = $request->ip();
		$statistic->destination_url = $request->server('REQUEST_URI');
		$statistic->referer_url = $request->server('HTTP_REFERER');
 		
		$statistic->http_code = http_response_code();
		$statistic->target_url = $route->uri();//$request->path();
		$statistic->destination_name = $request->route()->getName();
		$statistic->method = $request->route()->methods()[0];
		
		if (Auth::check()) {
			$userid = Config::get('statistics.user_id');
			$firstname = Config::get('statistics.first_name');
			$lastname = Config::get('statistics.last_name');
			
			if (!empty($userid))
				$statistic->userid = Auth::user()->$userid;
			if (!empty($firstname))
				$statistic->firstname = Auth::user()->$firstname;
			if (!empty($lastname))
				$statistic->lastname = Auth::user()->$lastname;
		}
		
		$inputs = Input::all();
		
		if (count($inputs) > 0) {
			$restrictedFields = Config::get('statistics.protected_fields');
			
			foreach ($restrictedFields as $restrictedField) {
				if (isset($inputs[$restrictedField]))
					unset($inputs[$restrictedField]);
			}
		}
		
		$statistic->input = json_encode($inputs);
		
		try {
			$statistic->save();
			
			$request->session()->put('statistic_id', $statistic->id);
			
		}
		catch( PDOException $Exception ) {
			Log::error($Exception);
		}
	}

}
