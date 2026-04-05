<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormArquivo extends Model
{
    use HasFactory, SoftDeletes;
    protected $table    = 'form_arquivos';
    protected $fillable = [
        'arquivo_id',
        'form_id',
        'tipo',
        'posicao',
    ];
    public const TIPO_LOGO        = 'logo';
    public const TIPO_ANEXO       = 'anexo';
    public const TIPO_IMAGEM      = 'imagem';
    public const POSICAO_ESQUERDA = 'esquerda';
    public const POSICAO_CENTRO   = 'centro';
    public const POSICAO_DIREITA  = 'direita';
    public const TIPOS_VALIDOS    = [
        self::TIPO_LOGO,
        self::TIPO_ANEXO,
        self::TIPO_IMAGEM,
    ];
    public const POSICOES_VALIDAS = [
        self::POSICAO_ESQUERDA,
        self::POSICAO_CENTRO,
        self::POSICAO_DIREITA,
    ];
    public function arquivo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class);
    }
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
    public static function tipoValido(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_VALIDOS, true);
    }
    public static function posicaoValida(string $posicao): bool
    {
        return in_array($posicao, self::POSICOES_VALIDAS, true);
    }
}