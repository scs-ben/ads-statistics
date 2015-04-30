<?php namespace Ads\Statistics;

use \Auth;
use \Config;
use \Exception;
use \Input;
use \Request;
use \Route;
use \Session;
use \View;

class Statistic extends \Eloquent {

	// Don't forget to fill this array
	protected $fillable = [];
	
	public static function httpError($request, Exception $e)
	{
		$statistic = Statistic::find(Session::get('statistic_id'));
		
		if (!is_object($statistic))
			$statistic = new Statistic;
		
		$parameters = $request->server->all();
		
		if (!empty($parameters['REDIRECT_STATUS']))
			$statistic->http_code = $parameters['REDIRECT_STATUS'];
		if (!empty($parameters['REMOTE_ADDR']))
			$statistic->ip_address = $parameters['REMOTE_ADDR'];
		if (!empty($parameters['REQUEST_URI']))
			$statistic->destination_url = $parameters['REQUEST_URI'];
		if (!empty($parameters['HTTP_REFERER']))
			$statistic->referer_url = $parameters['HTTP_REFERER'];

		$statistic->method = $request->method;
		
		$statistic->errorFile = $e->getFile();
		$statistic->errorLine = $e->getLine();
		$statistic->errorMessage = $e->getMessage();
		
		$statistic->save();
		
		Session::flash('error_statistic_id', $statistic->id);
	}
	
	public static function fatalError($request, Exception $e)
	{
		$statistic = Statistic::find(Session::get('statistic_id'));
		
		if (!is_object($statistic))
			$statistic = new Statistic;
		
		$parameters = $request->server->all();
		
		if (!empty($parameters['REDIRECT_STATUS']))
			$statistic->http_code = $parameters['REDIRECT_STATUS'];
		if (!empty($parameters['REMOTE_ADDR']))
			$statistic->ip_address = $parameters['REMOTE_ADDR'];
		if (!empty($parameters['REQUEST_URI']))
			$statistic->destination_url = $parameters['REQUEST_URI'];
		if (!empty($parameters['HTTP_REFERER']))
			$statistic->referer_url = $parameters['HTTP_REFERER'];

		$statistic->method = $request->method;
		
		$statistic->http_code = '500';
		$statistic->errorFile = $e->getFile();
		$statistic->errorLine = $e->getLine();
		$statistic->errorMessage = $e->getMessage();
		
		$statistic->save();
		
		Session::flash('error_statistic_id', $statistic->id);
	}
	
	public function logStatistics($route, $request, $id = null)
	{
		$parameters = $request->server->all();
		
		$statistic = Statistic::find($id);
		
		if (!is_object($statistic))
			$statistic = new Statistic;
		
		if (empty($id)) {
			if (!empty($parameters['REDIRECT_STATUS']))
				$statistic->http_code = $parameters['REDIRECT_STATUS'];
			else
				$statistic->http_code = 200;
			if (!empty($parameters['REMOTE_ADDR']))
				$statistic->ip_address = $parameters['REMOTE_ADDR'];
			if (!empty($parameters['REQUEST_URI']))
				$statistic->destination_url = $parameters['REQUEST_URI'];
			if (!empty($parameters['HTTP_REFERER']))
				$statistic->referer_url = $parameters['HTTP_REFERER'];
		}
		
		if (is_object($route)) {
			$statistic->target_url = $route->uri();
			$statistic->destination_name = $route->getName();
			$statistic->method = $route->methods()[0];
		}
		
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
		}
		catch( PDOException $Exception ) {
			Log::error($Exception);
		}
		
		
		Session::flash('statistic_id', $statistic->id);
	}
}
