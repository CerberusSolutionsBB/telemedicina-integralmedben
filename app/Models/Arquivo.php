<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Arquivo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arquivos';

    protected $fillable = [
        'nome_original',
        'nome_armazenado',
        'caminho',
        'extensao',
        'mime_type',
        'tamanho',
        'disk',
        'user_id',
    ];

    /**
     * ⭐ Adiciona 'url' ao JSON automaticamente
     */
    protected $appends = ['url', 'data_br'];

    /**
     * ⭐ Accessor: Gera URL pública do arquivo
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->caminho);
    }
    public function getDataBrAttribute()
    {
        return $this->created_at?->format('d/m/Y H:i');
    }

    /**
     * ⭐ Accessor: Tamanho formatado (KB, MB, etc)
     */
    protected function tamanhoFormatado(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (): string {
                $bytes = $this->tamanho;

                if ($bytes >= 1073741824) {
                    return number_format($bytes / 1073741824, 2) . ' GB';
                } elseif ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 2) . ' MB';
                } elseif ($bytes >= 1024) {
                    return number_format($bytes / 1024, 2) . ' KB';
                } elseif ($bytes > 1) {
                    return $bytes . ' bytes';
                } elseif ($bytes == 1) {
                    return '1 byte';
                } else {
                    return '0 bytes';
                }
            },
        );
    }

    /**
     * ⭐ Accessor: Ícone baseado na extensão
     */
    protected function icone(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (): string {
                $icones = [
                    'pdf'  => 'file-text',
                    'doc'  => 'file-text',
                    'docx' => 'file-text',
                    'xls'  => 'table',
                    'xlsx' => 'table',
                    'jpg'  => 'image',
                    'jpeg' => 'image',
                    'png'  => 'image',
                    'gif'  => 'image',
                    'webp' => 'image',
                    'svg'  => 'image',
                    'mp4'  => 'video',
                    'mp3'  => 'audio',
                    'zip'  => 'archive',
                    'rar'  => 'archive',
                ];

                return $icones[strtolower($this->extensao)] ?? 'file';
            },
        );
    }

    /**
     * ⭐ Verifica se é uma imagem
     */
    public function ehImagem(): bool
    {
        return in_array(strtolower($this->extensao), [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico',
        ]);
    }

    /**
     * ⭐ Verifica se é um documento
     */
    public function ehDocumento(): bool
    {
        return in_array(strtolower($this->extensao), [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        ]);
    }

    /**
     * Usuário que enviou o arquivo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Forms associados a este arquivo
     */

    public function forms()
    {
        return $this->belongsToMany(Form::class, 'form_arquivos', 'arquivo_id', 'form_id');
    }
}