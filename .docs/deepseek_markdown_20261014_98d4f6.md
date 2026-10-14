# 🎟️ Módulo de Cupons de Desconto

> Documentação técnica do módulo financeiro de cupons de desconto,
> integrado ao sistema de vendas de planos e página de parceiros.

---

## 📑 Índice

1. [Visão Geral](#1-visão-geral)
2. [Modelagem de Dados](#2-modelagem-de-dados)
3. [Máquina de Estados](#3-máquina-de-estados)
4. [Regras de Validação](#4-regras-de-validação)
5. [Fluxo de Criação](#5-fluxo-de-criação)
6. [Fluxo de Aplicação](#6-fluxo-de-aplicação)
7. [Expiração de Cupons](#7-expiração-de-cupons)
8. [Histórico e Auditoria](#8-histórico-e-auditoria)
9. [Anti-Fraude](#9-anti-fraude)
10. [API Endpoints](#10-api-endpoints)
11. [Melhorias Futuras](#11-melhorias-futuras)
12. [Decisões de Negócio](#12-decisões-de-negócio)
13. [Checklist de Implementação](#13-checklist-de-implementação)

---

## 1. Visão Geral

O módulo de cupons permite:

- ✅ Criação de cupons por administradores e parceiros
- ✅ Aplicação de desconto em vendas de planos
- ✅ Controle de validade (data de expiração)
- ✅ Controle de uso (limite total e por cliente)
- ✅ Histórico completo de criação e uso
- ✅ Bloqueio de reutilização após expiração ou esgotamento
- ✅ Auditoria de todas as operações

### Tipos de Cupom Suportados

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| `percentual` | Desconto em % | 15% off |
| `valor_fixo` | Desconto em R$ | R$ 50,00 off |
| `trial_estendido` | Dias extras de trial | +14 dias grátis |

---

## 2. Modelagem de Dados

### 2.1 Entidade `CUPOM`

```sql
CREATE TABLE cupom (
    id                      BIGSERIAL PRIMARY KEY,
    codigo                  VARCHAR(50) UNIQUE NOT NULL,
    tipo                    VARCHAR(20) NOT NULL, -- percentual | valor_fixo | trial_estendido
    valor                   DECIMAL(10,2) NOT NULL,
    parceiro_id             BIGINT REFERENCES parceiro(id),
    criado_por              BIGINT NOT NULL REFERENCES usuario(id),
    origem                  VARCHAR(30) NOT NULL, -- manual | campanha | indicacao | suporte
    data_criacao            TIMESTAMP NOT NULL DEFAULT NOW(),
    data_inicio_validade    TIMESTAMP NOT NULL,
    data_expiracao          TIMESTAMP NOT NULL,
    limite_uso_total        INT,                   -- NULL = ilimitado
    limite_uso_por_cliente  INT DEFAULT 1,
    uso_atual               INT NOT NULL DEFAULT 0,
    status                  VARCHAR(20) NOT NULL DEFAULT 'ativo',
    valor_minimo_compra     DECIMAL(10,2) DEFAULT 0,
    aplicavel_a             VARCHAR(30) DEFAULT 'todos', -- todos | planos_especificos | primeiro_contrato
    acumulavel              BOOLEAN DEFAULT FALSE,
    observacoes             TEXT,
    
    CONSTRAINT chk_validade CHECK (data_expiracao > data_inicio_validade),
    CONSTRAINT chk_valor CHECK (valor > 0)
);

CREATE INDEX idx_cupom_codigo ON cupom(codigo);
CREATE INDEX idx_cupom_status ON cupom(status);
CREATE INDEX idx_cupom_parceiro ON cupom(parceiro_id);
CREATE INDEX idx_cupom_expiracao ON cupom(data_expiracao);