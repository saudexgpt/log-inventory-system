<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('warehouse_id');
            $table->string('name');
            $table->string('address');
            $table->boolean('enabled')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('site_users', function (Blueprint $table) {
            $table->unsignedInteger('site_id');
            $table->unsignedInteger('user_id');

            $table->foreign('site_id')->references('id')->on('sites')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['user_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites');
        Schema::dropIfExists('site_users');
    }
}
