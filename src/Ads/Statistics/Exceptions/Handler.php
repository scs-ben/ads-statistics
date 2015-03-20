<?php namespace App\Exceptions;

use Exception;
use Session;
use Statistic;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler {
	
	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		try {
			if (!empty(Session::get('statistic_id')))
				$statistic = Statistic::find(Session::get('statistic_id'));
			else
				$statistic = new Statistic;
		
			$statistic->http_code = $e->getCode();
			$statistic->errorFile = $e->getFile();
			$statistic->errorLine = $e->getLine();
			$statistic->errorMessage = $e->getMessage();
			$statistic->save();
		}
		catch( PDOException $Exception ) {
			Log::error($Exception);
		}
		
		return parent::report($e);
	}

}
