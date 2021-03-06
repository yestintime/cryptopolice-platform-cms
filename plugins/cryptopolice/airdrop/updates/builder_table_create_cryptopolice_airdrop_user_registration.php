<?php namespace cryptopolice\airdrop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCryptopoliceAirdropUserRegistration extends Migration
{
    public function up()
    {
        if (!Schema::hasColumns('cryptopolice_airdrop_user_registration', ['message'])) {
            Schema::table('cryptopolice_airdrop_user_registration', function ($table) {
                $table->string('message', 500)->nullable();
            });
        }

        if (!Schema::hasTable('cryptopolice_airdrop_user_registration')) {
            Schema::create('cryptopolice_airdrop_user_registration', function ($table) {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('user_id')->default(0);
                $table->integer('airdrop_id')->default(0);
                $table->boolean('approval_type')->default(0);
                $table->text('fields_data')->nullable();
                $table->string('airdrop_code')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }
    
    public function down()
    {
    }
}
