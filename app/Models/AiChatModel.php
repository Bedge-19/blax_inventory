<?php

namespace App\Models;

use CodeIgniter\Model;

class AiChatModel extends Model
{
    protected $table            = 'ai_chat_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'sender',
        'message',
        'created_at',
    ];
}
