<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStatisticsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::query("ALTER TABLE `statistics` CHANGE COLUMN `errorFile` `errorFile` text NOT NULL;");
		
		Schema::table('statistics', function(Blueprint $table)
		{
			$table->text('errorFile')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::query("ALTER TABLE `statistics` CHANGE COLUMN `errorFile` `errorFile` VARCHAR(128) NOT NULL;");
	}

}
