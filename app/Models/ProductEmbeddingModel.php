<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistent storage for Cohere product embeddings.
 *
 * Vectors are stored in a BLOB as packed little-endian float32 values.
 * Reads/writes always travel over the wire as hex (HEX()/UNHEX()) so binary
 * payloads never pass through the connection's charset-aware string escaper.
 */
class ProductEmbeddingModel extends Model
{
    protected $table            = 'product_embeddings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'model',
        'dimensions',
        'embedding',
        'content_hash',
        'source_text',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Pack a float vector into the stored binary representation.
     * Vectors are L2-normalised so cosine similarity reduces to a dot product.
     *
     * @param list<float> $vector
     */
    public static function pack(array $vector): string
    {
        $norm = 0.0;
        foreach ($vector as $v) {
            $norm += $v * $v;
        }
        $norm = sqrt($norm);

        if ($norm > 0.0) {
            foreach ($vector as $i => $v) {
                $vector[$i] = $v / $norm;
            }
        }

        return pack('g*', ...array_map('floatval', array_values($vector)));
    }

    /**
     * Unpack a stored binary vector. Returns [] when the payload is malformed.
     *
     * @return list<float>
     */
    public static function unpack(string $binary, int $expectedDimensions = 0): array
    {
        if ($binary === '' || strlen($binary) % 4 !== 0) {
            return [];
        }

        $values = @\unpack('g*', $binary);
        if (! is_array($values) || $values === []) {
            return [];
        }

        $values = array_values($values);

        if ($expectedDimensions > 0 && count($values) !== $expectedDimensions) {
            return [];
        }

        return $values;
    }

    /**
     * Insert or update the embedding row for one product.
     */
    public function upsertEmbedding(
        int $productId,
        string $model,
        int $dimensions,
        string $packed,
        string $contentHash,
        ?string $sourceText = null
    ): void {
        $sql = 'INSERT INTO ' . $this->db->prefixTable($this->table) . '
                    (product_id, model, dimensions, embedding, content_hash, source_text, created_at, updated_at)
                VALUES (?, ?, ?, UNHEX(?), ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    model        = VALUES(model),
                    dimensions   = VALUES(dimensions),
                    embedding    = VALUES(embedding),
                    content_hash = VALUES(content_hash),
                    source_text  = VALUES(source_text),
                    updated_at   = NOW()';

        $this->db->query($sql, [
            $productId,
            $model,
            $dimensions,
            bin2hex($packed),
            $contentHash,
            $sourceText,
        ]);
    }

    /**
     * Candidate vectors for a semantic search, restricted by the same
     * deterministic product rules used by the normal catalog query.
     *
     * @return list<array{product_id:int, vector:list<float>}>
     */
    public function getSearchCandidates(string $model, int $dimensions, ?int $categoryId = null): array
    {
        $builder = $this->db->table($this->table . ' pe')
            ->select('pe.product_id, pe.dimensions, HEX(pe.embedding) AS embedding_hex')
            ->join('products p', 'p.id = pe.product_id', 'inner')
            ->where('p.deleted_at', null)
            ->where('pe.model', $model)
            ->where('pe.dimensions', $dimensions);

        if ($categoryId) {
            $builder->where('p.category_id', $categoryId);
        }

        $rows = $builder->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $binary = @hex2bin((string) $row['embedding_hex']);
            if ($binary === false) {
                continue;
            }

            $vector = self::unpack($binary, (int) $row['dimensions']);
            if ($vector === []) {
                // Malformed stored embedding: skip it rather than fail the search.
                log_message('warning', 'Skipping malformed embedding for product #' . $row['product_id']);
                continue;
            }

            $out[] = ['product_id' => (int) $row['product_id'], 'vector' => $vector];
        }

        return $out;
    }

    /**
     * product_id => content_hash for rows that are actually usable.
     *
     * Rows produced by a different model/dimension, or whose BLOB is not
     * exactly `dimensions * 4` bytes, are omitted so the caller treats them as
     * stale and regenerates them instead of trusting a corrupt vector.
     *
     * @param list<int> $productIds
     *
     * @return array<int, string>
     */
    public function getContentHashes(array $productIds, string $model, int $dimensions): array
    {
        $builder = $this->db->table($this->table)
            ->select('product_id, content_hash')
            ->where('model', $model)
            ->where('dimensions', $dimensions)
            ->where('LENGTH(embedding) = dimensions * 4', null, false);

        if ($productIds !== []) {
            $builder->whereIn('product_id', array_map('intval', $productIds));
        }

        $map = [];
        foreach ($builder->get()->getResultArray() as $row) {
            $map[(int) $row['product_id']] = (string) $row['content_hash'];
        }

        return $map;
    }

    /**
     * Drop rows produced by a different model/dimension combination so a
     * config change cannot mix incompatible vectors.
     */
    public function purgeIncompatible(string $model, int $dimensions): int
    {
        $this->db->table($this->table)
            ->groupStart()
            ->where('model !=', $model)
            ->orWhere('dimensions !=', $dimensions)
            ->groupEnd()
            ->delete();

        return $this->db->affectedRows();
    }

    public function countEmbedded(string $model, int $dimensions): int
    {
        return (int) $this->db->table($this->table)
            ->where('model', $model)
            ->where('dimensions', $dimensions)
            ->countAllResults();
    }
}
