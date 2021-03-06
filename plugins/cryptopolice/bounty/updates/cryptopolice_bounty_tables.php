<?php namespace CryptoPolice\Bounty\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CryptoPolicebountyBountyTables extends Migration
{

    /*
     * CryptoPolice Bounty Tables
     */

    public function up()
    {

        Schema::table('cryptopolice_bounty_campaigns', function ($table) {
            $table->float('percentage')->default(0)->change();
        });

        Schema::table('cryptopolice_bounty_user_registration', function ($table) {
            $table->string('btc_code', 38)->default(0)->change();
        });

        if (!Schema::hasTable('cryptopolice_bounty_user_registration')) {

            Schema::create('cryptopolice_bounty_user_registration', function ($table) {

                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('bounty_campaigns_id')->default(0);
                $table->integer('user_id')->default(0);
                $table->boolean('status')->default(1);
                $table->boolean('approval_type')->default(0);
                $table->text('fields_data')->nullable();
                $table->string('btc_code', 15)->default(0);
                $table->string('btc_username', 255)->nullable();
                $table->boolean('btc_status')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();

            });
        }

        if (!Schema::hasColumns('cryptopolice_bounty_user_registration', ['message'])) {
            Schema::table('cryptopolice_bounty_user_registration', function ($table) {
                $table->string('message', 1000)->nullable();
            });
        }

        if (!Schema::hasColumns('cryptopolice_bounty_user_registration', ['reverified'])) {
            Schema::table('cryptopolice_bounty_user_registration', function ($table) {
                $table->boolean('reverified')->default(0);
            });
        }

        if (!Schema::hasColumns('cryptopolice_bounty_user_reports', ['report_list'])) {
            Schema::table('cryptopolice_bounty_user_reports', function ($table) {
                $table->string('report_list', 2500)->nullable();
            });
        }

        if (!Schema::hasTable('cryptopolice_bounty_user_reports')) {

            Schema::create('cryptopolice_bounty_user_reports', function ($table) {

                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('user_id')->default(0);
                $table->integer('reward_id')->default(1);
                $table->integer('bounty_campaigns_id')->default(0);
                $table->integer('bounty_user_registration_id')->default(0);
                $table->integer('given_reward')->default(0);
                $table->boolean('report_status')->default(0);
                $table->text('description')->nullable();
                $table->string('title', 255)->nullable();
                $table->string('comment', 1000)->nullable();
                $table->text('fields_data')->nullable();
                $table->text('report_files')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();

            });
        }

        if (!Schema::hasTable('cryptopolice_bounty_campaigns')) {

            Schema::create('cryptopolice_bounty_campaigns', function ($table) {

                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->string('title', 255)->nullable();
                $table->string('slug', 255)->nullable();
                $table->text('description')->nullable();
                $table->boolean('status')->default(0);
                $table->integer('sort_order')->default(0);
                $table->text('fields')->nullable();
                $table->string('icon', 25)->nullable();
                $table->tinyInteger('percentage')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();

            });
        }

        if (!Schema::hasTable('cryptopolice_bounty_rewards')) {

            Schema::create('cryptopolice_bounty_rewards', function ($table) {

                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('bounty_campaigns_id')->default(0);
                $table->string('reward_title', 255)->nullable();
                $table->string('reward_description', 255)->nullable();
                $table->boolean('reward_type')->default(0);
                $table->integer('reward_amount_min')->default(0);
                $table->integer('reward_amount_max')->default(0);
                $table->boolean('status')->default(0);
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
