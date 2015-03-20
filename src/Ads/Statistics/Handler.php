<?php namespace Ads\Statistics;

use Exception;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler {
	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{		
		if ($this->isHttpException($e)) {
			\Statistic::httpError($request, $e);
		} else {
			\Statistic::fatalError($request, $e);
		}
		
		return parent::render($request, $e);
	}

}
