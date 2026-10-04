import { ref, watch, unref } from 'vue';

// Recortes já calculados nesta aba (a sidebar remonta a cada visita Inertia).
const cache = new Map();

const ANALISE_MAX = 600; // lado máximo usado para achar o conteúdo
const SAIDA_MAX = 800;   // largura máxima da imagem recortada
const TOLERANCIA = 40;   // distância de cor para considerar "fundo"
const MARGEM = 0.02;     // respiro em volta do conteúdo

const carregar = (url) => new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = url;
});

// Fundo = cor do canto superior esquerdo (branco, preto, cor sólida) ou transparência.
const ehFundo = (d, i, fundo) => {
    if (d[i + 3] < 16) return true;
    if (fundo[3] < 200) return false;
    return Math.abs(d[i] - fundo[0]) + Math.abs(d[i + 1] - fundo[1]) + Math.abs(d[i + 2] - fundo[2]) < TOLERANCIA;
};

const recortar = async (url) => {
    const img = await carregar(url);
    const { naturalWidth: w, naturalHeight: h } = img;

    const escala = Math.min(1, ANALISE_MAX / Math.max(w, h));
    const aw = Math.max(1, Math.round(w * escala));
    const ah = Math.max(1, Math.round(h * escala));
    const analise = document.createElement('canvas');
    analise.width = aw;
    analise.height = ah;
    const actx = analise.getContext('2d', { willReadFrequently: true });
    actx.drawImage(img, 0, 0, aw, ah);
    const d = actx.getImageData(0, 0, aw, ah).data;
    const fundo = [d[0], d[1], d[2], d[3]];

    let x0 = aw, y0 = ah, x1 = -1, y1 = -1;
    for (let y = 0; y < ah; y++) {
        for (let x = 0; x < aw; x++) {
            if (ehFundo(d, (y * aw + x) * 4, fundo)) continue;
            if (x < x0) x0 = x;
            if (x > x1) x1 = x;
            if (y < y0) y0 = y;
            if (y > y1) y1 = y;
        }
    }

    // Imagem vazia ou já sem margem: usa a original.
    if (x1 < 0) return url;
    const larguraConteudo = (x1 - x0 + 1) / aw;
    const alturaConteudo = (y1 - y0 + 1) / ah;
    if (larguraConteudo > 0.95 && alturaConteudo > 0.95) return url;

    const respiro = Math.round(Math.max(aw, ah) * MARGEM);
    const sx = Math.max(0, x0 - respiro) / escala;
    const sy = Math.max(0, y0 - respiro) / escala;
    const sw = (Math.min(aw, x1 + 1 + respiro) / escala) - sx;
    const sh = (Math.min(ah, y1 + 1 + respiro) / escala) - sy;

    const fator = Math.min(1, SAIDA_MAX / sw);
    const saida = document.createElement('canvas');
    saida.width = Math.round(sw * fator);
    saida.height = Math.round(sh * fator);
    saida.getContext('2d').drawImage(img, sx, sy, sw, sh, 0, 0, saida.width, saida.height);

    return saida.toDataURL('image/png');
};

/**
 * Logo sem as margens vazias do arquivo (fundo transparente ou de cor sólida),
 * para ocupar de verdade o espaço disponível. Enquanto calcula, ou se falhar
 * (ex.: imagem de outro domínio sem CORS), devolve a URL original.
 *
 * @param {import('vue').Ref<string|null>|string|null} url
 */
export function useLogoRecortado(url) {
    const src = ref(unref(url) || null);

    watch(() => unref(url), async (atual) => {
        src.value = atual || null;
        if (!atual) return;

        if (!cache.has(atual)) {
            cache.set(atual, recortar(atual).catch(() => atual));
        }

        const recortada = await cache.get(atual);
        if (unref(url) === atual) src.value = recortada;
    }, { immediate: true });

    return src;
}
