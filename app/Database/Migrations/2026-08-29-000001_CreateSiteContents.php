<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteContents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'page' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'content_key' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'label' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'content_type' => ['type' => 'ENUM', 'constraint' => ['text','textarea','image'], 'default' => 'text'],
            'text_value' => ['type' => 'TEXT', 'null' => true],
            'image_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'sort_order' => ['type' => 'INT', 'null' => false, 'default' => 0],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['page','content_key']);
        $this->forge->addKey('page');
        $this->forge->createTable('site_contents', true);

        // Seed default customer-facing contents
        $data = [
            ['page'=>'home','content_key'=>'hero_badge','label'=>'Hero Badge','content_type'=>'text','text_value'=>'Seasonal Event','sort_order'=>1],
            ['page'=>'home','content_key'=>'hero_title','label'=>'Hero Title','content_type'=>'text','text_value'=>'The Ultimate Merchandise Selection','sort_order'=>2],
            ['page'=>'home','content_key'=>'hero_subtitle','label'=>'Hero Subtitle','content_type'=>'textarea','text_value'=>'Discover premium goods, exclusive deals, and top-tier printing services all in one place from our network of verified elite shops.','sort_order'=>3],
            ['page'=>'home','content_key'=>'hero_image','label'=>'Hero Image','content_type'=>'image','image_url'=>'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80','sort_order'=>4],
            ['page'=>'home','content_key'=>'catalog_title','label'=>'Catalog Title','content_type'=>'text','text_value'=>'Global Product Catalog','sort_order'=>5],
            ['page'=>'home','content_key'=>'catalog_subtitle','label'=>'Catalog Subtitle','content_type'=>'textarea','text_value'=>'Aggregation of all products currently available across the entire RHK network.','sort_order'=>6],
            ['page'=>'printing_services','content_key'=>'hero_title','label'=>'Hero Title','content_type'=>'text','text_value'=>'Professional Printing, Everywhere You Are','sort_order'=>1],
            ['page'=>'printing_services','content_key'=>'hero_subtitle','label'=>'Hero Subtitle','content_type'=>'textarea','text_value'=>'Connect with certified printing partners in Polomolok for your business and creative needs.','sort_order'=>2],
            ['page'=>'printing_services','content_key'=>'hero_image','label'=>'Hero Image','content_type'=>'image','image_url'=>'','sort_order'=>3],
            ['page'=>'categories','content_key'=>'hero_title','label'=>'Hero Title','content_type'=>'text','text_value'=>'Explore Categories','sort_order'=>1],
            ['page'=>'categories','content_key'=>'hero_subtitle','label'=>'Hero Subtitle','content_type'=>'textarea','text_value'=>'Browse all product categories available in the RHK marketplace.','sort_order'=>2],
        ];
        $db = \Config\Database::connect();
        foreach ($data as $row) {
            $exists = $db->table('site_contents')->where('page',$row['page'])->where('content_key',$row['content_key'])->get()->getRow();
            if (!$exists) $db->table('site_contents')->insert($row);
        }
    }

    public function down()
    {
        $this->forge->dropTable('site_contents', true);
    }
}
