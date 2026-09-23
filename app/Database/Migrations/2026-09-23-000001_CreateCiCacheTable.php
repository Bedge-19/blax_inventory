<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the ci_cache table for CodeIgniter 4's database cache handler.
 *
 * Required for serverless deployments (Vercel) where the file-based cache
 * is ephemeral and not shared across function instances.
 */
class CreateCiCacheTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'TEXT',
            ],
            'ttl' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 60,
            ],
            'created_at' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('ci_cache', true);
    }

    public function down()
    {
        $this->forge->dropTable('ci_cache', true);
    }
}
