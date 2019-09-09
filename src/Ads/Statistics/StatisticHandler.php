<?php 

namespace Ads\Statistics;

use Exception;
use \Illuminate\Foundation\Exceptions\Handler;
use Statistic;

class StatisticHandler extends Handler {
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
		return parent::report($e);
	}
	
	public function render($request, Exception $e)
	{
		if ($this->isHttpException($e)) {
			Statistic::httpError($request, $e);
		} else {
			Statistic::fatalError($request, $e);
		}
	
		return parent::render($request, $e);
	}
}
