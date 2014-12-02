<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsernameToStatisticsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('statistics', function(Blueprint $table)
		{
			$table->string('firstname', 50)->nullable()->after('userid');
			$table->string('lastname', 50)->nullable()->after('firstname');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('statistics', function(Blueprint $table)
		{
			$table->dropColumn('firstname');
			$table->dropColumn('lastname');
		});
	}

}
