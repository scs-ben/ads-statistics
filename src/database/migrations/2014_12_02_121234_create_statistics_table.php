<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatisticsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('statistics', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('http_code', 16)->nullable();
			$table->string('ip_address', 32)->nullable();
			$table->string('destination_url', 200)->nullable();
			$table->string('target_url', 200)->nullable();
			$table->string('destination_name', 64)->nullable();
			$table->string('referer_url', 200)->nullable();
			$table->string('method', 16)->nullable();
			$table->string('userid', 50)->nullable();
			$table->string('firstname', 50)->nullable();
			$table->string('lastname', 50)->nullable();
			$table->string('errorFile', 128)->nullable();
			$table->string('errorLine', 64)->nullable();
			$table->text('errorMessage')->nullable();
			$table->text('input')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('statistics');
	}

}
