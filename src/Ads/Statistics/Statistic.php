<?php namespace Ads\Statistics;

use \Auth;
use \Config;
use \Input;
use \Session;

class Statistic extends \Eloquent {

	// Don't forget to fill this array
	protected $fillable = [];
	
	public function logStatistics($route, $request)
	{
		$parameters = $request->server->all();
		
		$statistic = new Statistic;
		$statistic->http_code = $parameters['REDIRECT_STATUS'];
		$statistic->ip_address = $parameters['REMOTE_ADDR'];
		$statistic->destination_url = $parameters['REQUEST_URI'];
		$statistic->target_url = $route->uri();
		$statistic->destination_name = $route->getName();
		if (!empty($parameters['HTTP_REFERER']))
			$statistic->referer_url = $parameters['HTTP_REFERER'];
		$statistic->method = $route->methods()[0];
		
		if (Auth::check()) {
			$userid = Config::get('statistics::settings.user_id');
			$firstname = Config::get('statistics::settings.first_name');
			$lastname = Config::get('statistics::settings.last_name');
			
			if (!empty($userid))
				$statistic->userid = Auth::user()->$userid;
			if (!empty($firstname))
				$statistic->firstname = Auth::user()->$firstname;
			if (!empty($lastname))
				$statistic->lastname = Auth::user()->$lastname;
		}
		$statistic->input = json_encode(Input::all());
		$statistic->save();
		
		Session::flash('statistic_id', $statistic->id);
	}
}
