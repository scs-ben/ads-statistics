<?php

App::error(function(Exception $exception, $code)
{
	switch ($code)
	{
		case 500: {
			if (!empty(Session::get('statistic_id')))
				$statistic = Statistic::find(Session::get('statistic_id'));
			else
				$statistic = new Statistic;
			
			$statistic->http_code = $code;
			$statistic->errorFile = $exception->getFile();
			$statistic->errorLine = $exception->getLine();
			$statistic->errorMessage = $exception->getMessage();
			$statistic->save();
			break;
		}
	}
});