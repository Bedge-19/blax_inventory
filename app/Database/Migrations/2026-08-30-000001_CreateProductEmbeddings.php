<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductEmbeddings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'model'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'embed-v4.0'],
            'dimensions'   => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false, 'default' => 1024],
            // Packed little-endian float32 vector, L2-normalised at write time.
            'embedding'    => ['type' => 'BLOB', 'null' => false],
            // sha1 of the generated embedding document; lets us skip re-embedding
            // when only price/stock changed.
            'content_hash' => ['type' => 'CHAR', 'constraint' => 40, 'null' => false],
            'source_text'  => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'   => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('product_id');
        $this->forge->addKey('content_hash');
        $this->forge->createTable('product_embeddings', true);
    }

    public function down()
    {
        $this->forge->dropTable('product_embeddings', true);
    }
}
