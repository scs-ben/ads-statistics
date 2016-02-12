<?php namespace Ads\Statistics;

use \Auth;
use \Config;
use \Exception;
use Illuminate\Support\Facades\Input;
use \Request;
use \Route;
use \View;

class Statistic extends \Eloquent {

	// Don't forget to fill this array
	protected $fillable = [];
	
	public static function error(Exception $e)
	{
		if (request()->hasSession()) {
			$statistic = Statistic::findOrNew(request()->session()->get('statistic_id'));
		} else {
			$statistic = new Statistic;
		}
		
		$statistic->http_code = http_response_code();
		$statistic->errorFile = $e->getFile();
		$statistic->errorLine = $e->getLine();
		$statistic->errorMessage = $e->getMessage() . PHP_EOL . 'TRACE' . PHP_EOL . $e->__toString();
		
		$statistic->save();
		
		self::emailError($e);	
	}
	
	public static function httpError($request, Exception $e)
	{
		self::error($e);
	}
	
	public static function fatalError($request, Exception $e)
	{
		self::error($e);
	}
	
	private static function emailError(Exception $e)
	{
		if (! $e instanceOf NotFoundHttpException ) {
			if (!empty(Config::get('statistics.mandrill_secret')) && !empty(Config::get('statistics.error_email'))) {
				$mandrill = new \Mandrill(config('statistics.mandrill_secret'));
				$html = 'File: ' . $e->getFile()  . ' Line: ' . $e->getLine() . PHP_EOL . 'TRACE' . PHP_EOL . $e->getMessage() . PHP_EOL . 'TRACE' . PHP_EOL . $e->__toString();
				$html = nl2br($html);
				$message = [
					'html' => $html,
					'text' => htmlentities($html),
					'subject' => "Server Error for " . Request::url(),
					'from_email' => Config::get('statistics.error_email'),
					'from_name'  => "Server Error",
					'to' => [
								[
									'email' => Config::get('statistics.error_email'),
									'type' => 'to'
								]
							],
					'track_opens' => true
				];
				$async = false;
	    		$ip_pool = 'Main Pool';
				$response = $mandrill->messages->send($message, $async, $ip_pool);
			}
		}
	}
	
	public function logStatistics($route, $request)
	{
		$statistic = new Statistic;
		
		$statistic->ip_address = $request->ip();
		$statistic->destination_url = $request->server('REQUEST_URI');
		$statistic->referer_url = $request->server('HTTP_REFERER');
 		
		$statistic->http_code = http_response_code();
		$statistic->target_url = $request->path();
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
			
			if ($request->hasSession()) {
				$request->session()->put('statistic_id', $statistic->id);
			}
			
		}
		catch( PDOException $Exception ) {
			Log::error($Exception);
		}
	}

}
