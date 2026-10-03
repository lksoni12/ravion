<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key_name';
    protected $returnType = 'array';
    protected $allowedFields = [
        'key_name',
        'value_text',
    ];

    /**
     * Get a setting value by key, with default fallback.
     */
    public function getValue(string $key, string $default = ''): string
    {
        $row = $this->where('key_name', $key)->first();
        return $row ? (string) $row['value_text'] : $default;
    }

    /**
     * Set/update a setting value by key.
     */
    public function setValue(string $key, string $value): bool
    {
        return $this->save([
            'key_name' => $key,
            'value_text' => $value,
        ]);
    }

    /**
     * Get all settings as a key => value associative array.
     */
    public function getKeyValuePairs(): array
    {
        $rows = $this->findAll();
        $res = [];
        foreach ($rows as $r) {
            $res[$r['key_name']] = $r['value_text'];
        }
        return $res;
    }
}
