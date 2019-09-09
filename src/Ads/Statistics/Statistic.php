<?php 

namespace Ads\Statistics;


use Closure;
use Config;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request as HttpRequest;
use Request;

class Statistic extends Model {

	// Don't forget to fill this array
	protected $fillable = [];
	
	public function handle(HttpRequest $request, Closure $next)
    {
    	if (empty($request)) {
			$request = request();
		}
		
		$user = null;

		if (Auth::check()) {
			$user = Auth::user();
		}

		$this->logStatistics($request->route(), $request, $user);

		return $next($request);
    }

	public static function error(Exception $e, $user = null)
	{
		// $statistic = new Statistic;

		// self::logDetails($statistic, request(), $user);

		// $statistic->http_code = '500';//http_response_code();
		// $statistic->errorFile = $e->getFile();
		// $statistic->errorLine = $e->getLine();
		// $statistic->errorMessage = $e->getMessage() . PHP_EOL . 'TRACE' . PHP_EOL . $e->__toString();
		
		// try {
		// 	$statistic->save();
		// } catch (Exception $e) {
		// 	\Log::error($e->getMessage());
		// }
	}
	
	public static function httpError($request, Exception $e)
	{
		self::error($e);
	}
	
	public static function fatalError($request, Exception $e)
	{
		self::error($e);
	}
	
	public function logStatistics($route, $request, $user)
	{
		$statistic = new Statistic;
		
		$this->logDetails($statistic, $request, $user);
		
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

	private static function logDetails(&$statistic, $request, $user)
	{
		$statistic->ip_address = $request->ip();
		$statistic->destination_url = $request->server('REQUEST_URI');
		$statistic->referer_url = $request->server('HTTP_REFERER');
 		
		$statistic->http_code = http_response_code();
		$statistic->target_url = $request->path();

		if (!empty($request->route())) {
			$statistic->destination_name = $request->route()->getName();
			$statistic->method = $request->route()->methods()[0];
		}
		
		if (!empty($user)) {
			$userid = config('statistics.user_id');
			$firstname = config('statistics.first_name');
			$lastname = config('statistics.last_name');

			if (!empty($userid))
				$statistic->userid = $user->$userid;
			if (!empty($firstname))
				$statistic->firstname = $user->$firstname;
			if (!empty($lastname))
				$statistic->lastname = $user->$lastname;
		}
		
		$inputs = $request->all();
		
		if (count($inputs) > 0) {
			$restrictedFields = config('statistics.protected_fields');
			
			foreach ($restrictedFields as $restrictedField) {
				if (isset($inputs[$restrictedField]))
					unset($inputs[$restrictedField]);
			}
		}
		
		$statistic->input = json_encode($inputs);
	}

}
