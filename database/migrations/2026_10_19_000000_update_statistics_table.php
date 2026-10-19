<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatisticsTable extends Migration
{
	/**
	 * Widen ip_address for IPv6 and index the columns reports filter on.
	 * Laravel 7-9 need doctrine/dbal installed for ->change().
	 */
	public function up()
	{
		Schema::table('statistics', function (Blueprint $table) {
			$table->string('ip_address', 45)->nullable()->change();

			$table->index('created_at');
			$table->index('userid');
			$table->index('http_code');
		});
	}

	public function down()
	{
		Schema::table('statistics', function (Blueprint $table) {
			$table->dropIndex(['created_at']);
			$table->dropIndex(['userid']);
			$table->dropIndex(['http_code']);

			$table->string('ip_address', 32)->nullable()->change();
		});
	}
}
