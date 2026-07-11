#!/bin/bash

set -e

echo "==========================================="
echo "  Instalador de LaTeX (pdflatex) - TELEMEDBEM"
echo "==========================================="
echo ""

if command -v pdflatex &> /dev/null; then
    echo "[OK] pdflatex ja esta instalado:"
    pdflatex --version | head -1
    echo ""
    echo "Verificando pacotes necessarios..."
    MISSING=()
    for pkg in tikz xcolor ifthen pgfmath graphicx; do
        kpsewhich "${pkg}.sty" > /dev/null 2>&1 || MISSING+=("$pkg")
    done
    if [ ${#MISSING[@]} -eq 0 ]; then
        echo "[OK] Todos os pacotes necessarios estao instalados."
        exit 0
    else
        echo "[AVISO] Pacotes faltando: ${MISSING[*]}"
        echo "Instalando pacotes adicionais..."
    fi
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "[ERRO] Este script precisa ser executado como root (sudo)."
    exit 1
fi

OS=""
if [ -f /etc/debian_version ]; then
    OS="debian"
elif [ -f /etc/redhat-release ]; then
    OS="redhat"
elif [ -f /etc/alpine-release ]; then
    OS="alpine"
elif command -v brew &> /dev/null; then
    OS="macos"
else
    echo "[ERRO] Sistema operacional nao suportado."
    exit 1
fi

echo "[INFO] Sistema detectado: $OS"
echo ""

case $OS in
    debian)
        apt-get update -qq
        apt-get install -y -qq \
            texlive-base \
            texlive-latex-recommended \
            texlive-latex-extra \
            texlive-fonts-recommended \
            texlive-fonts-extra \
            texlive-lang-portuguese
        ;;
    redhat)
        yum install -y -q \
            texlive-base \
            texlive-latex \
            texlive-collection-latexrecommended \
            texlive-collection-fontsrecommended \
            texlive-helvet
        ;;
    alpine)
        apk add --no-cache \
            texlive \
            texlive-xetex \
            texmf-dist-latexextra \
            texmf-dist-fontsrecommended
        ;;
    macos)
        brew install --cask mactex-no-gui
        ;;
esac

echo ""
echo "==========================================="
echo "  Verificando instalacao"
echo "==========================================="

if command -v pdflatex &> /dev/null; then
    echo "[OK] pdflatex instalado com sucesso:"
    pdflatex --version | head -1
else
    echo "[ERRO] pdflatex nao encontrado apos instalacao."
    exit 1
fi

echo ""
echo "[OK] Instalacao concluida!"
echo "O sistema de geracao de cartoes SIPROV esta pronto para uso."
