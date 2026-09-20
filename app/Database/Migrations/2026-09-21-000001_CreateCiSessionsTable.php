<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Session table required by CodeIgniter\Session\Handlers\DatabaseHandler.
 * Needed for Vercel (and any other stateless/serverless host) because
 * FileHandler sessions don't survive across separate function instances.
 */
class CreateCiSessionsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ci_sessions')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'timestamp'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'data'       => ['type' => 'BLOB', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
