<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Página Inicial" />

    <main class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header class="border-b border-border bg-background/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white font-bold">
                        ID
                    </div>

                    <div>
                        <h1 class="text-lg font-bold">
                            Integra Digital
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            Gestão inteligente para sua instituição
                        </p>
                    </div>
                </div>

                <nav class="hidden items-center gap-6 md:flex">
                    <a href="#recursos" class="text-sm text-muted-foreground hover:text-primary">
                        Recursos
                    </a>
                    <a href="#beneficios" class="text-sm text-muted-foreground hover:text-primary">
                        Benefícios
                    </a>
                    <a href="#contato" class="text-sm text-muted-foreground hover:text-primary">
                        Contato
                    </a>
                </nav>

                <Link
                    :href="route('login')"
                    class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover"
                >
                    Entrar
                </Link>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="mb-4 inline-flex rounded-full border border-primary/30 bg-primary/10 px-4 py-1 text-sm font-medium text-primary">
                        Plataforma SaaS moderna
                    </span>

                    <h2 class="max-w-2xl text-4xl font-extrabold tracking-tight md:text-6xl">
                        Simplifique a gestão com uma plataforma feita para escalar.
                    </h2>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-muted-foreground">
                        Centralize formulários, tenants, usuários e serviços em um ambiente
                        seguro, responsivo e preparado para múltiplas instituições.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="route('login')"
                            class="rounded-xl bg-primary px-6 py-3 text-center text-sm font-semibold text-white shadow-md transition hover:bg-primary-hover"
                        >
                            Acessar sistema
                        </Link>

                        <a
                            href="#recursos"
                            class="rounded-xl border border-border bg-card px-6 py-3 text-center text-sm font-semibold hover:bg-muted"
                        >
                            Conhecer recursos
                        </a>
                    </div>
                </div>

                <!-- Card visual -->
                <div class="rounded-3xl border border-border bg-card p-6 shadow-xl">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Visão geral
                            </p>
                            <h3 class="text-2xl font-bold">
                                Painel Administrativo
                            </h3>
                        </div>

                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            Online
                        </span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-border bg-background p-5">
                            <p class="text-sm text-muted-foreground">
                                Tenants ativos
                            </p>
                            <strong class="mt-2 block text-3xl">24</strong>
                        </div>

                        <div class="rounded-2xl border border-border bg-background p-5">
                            <p class="text-sm text-muted-foreground">
                                Formulários
                            </p>
                            <strong class="mt-2 block text-3xl">128</strong>
                        </div>

                        <div class="rounded-2xl border border-border bg-background p-5">
                            <p class="text-sm text-muted-foreground">
                                Respostas
                            </p>
                            <strong class="mt-2 block text-3xl">3.4k</strong>
                        </div>

                        <div class="rounded-2xl border border-border bg-background p-5">
                            <p class="text-sm text-muted-foreground">
                                Disponibilidade
                            </p>
                            <strong class="mt-2 block text-3xl">99%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recursos -->
        <section id="recursos" class="border-t border-border bg-muted/30 py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-12 max-w-2xl">
                    <h2 class="text-3xl font-bold">
                        Recursos principais
                    </h2>
                    <p class="mt-3 text-muted-foreground">
                        Tudo que você precisa para administrar clientes, formulários e acessos.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="mb-4 h-12 w-12 rounded-xl bg-primary/10"></div>
                        <h3 class="text-lg font-bold">
                            Multi-tenant
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Separe clientes, domínios e configurações de forma organizada e segura.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="mb-4 h-12 w-12 rounded-xl bg-primary/10"></div>
                        <h3 class="text-lg font-bold">
                            Formulários dinâmicos
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Crie, gerencie e disponibilize formulários personalizados para cada tenant.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="mb-4 h-12 w-12 rounded-xl bg-primary/10"></div>
                        <h3 class="text-lg font-bold">
                            Painel responsivo
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Interface moderna, limpa e adaptada para desktop, tablet e celular.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Benefícios -->
        <section id="beneficios" class="py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold">
                        Mais controle, menos complexidade.
                    </h2>

                    <p class="mt-4 text-muted-foreground">
                        A plataforma ajuda sua equipe a reduzir processos manuais,
                        organizar dados e entregar uma experiência melhor para os usuários.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-border bg-card p-5">
                        <h3 class="font-semibold">✔ Gestão centralizada</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Administre clientes, usuários e permissões em um só lugar.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-5">
                        <h3 class="font-semibold">✔ Segurança e organização</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Estrutura preparada para separar dados por instituição.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-5">
                        <h3 class="font-semibold">✔ Experiência moderna</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Visual limpo, navegação simples e foco em produtividade.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="contato" class="bg-primary py-16 text-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 text-center md:flex-row md:text-left">
                <div>
                    <h2 class="text-3xl font-bold">
                        Pronto para começar?
                    </h2>
                    <p class="mt-2 text-white/80">
                        Acesse o painel e gerencie sua plataforma com praticidade.
                    </p>
                </div>

                <Link
                    :href="route('login')"
                    class="rounded-xl bg-white px-6 py-3 text-sm font-bold text-primary shadow-md transition hover:opacity-90"
                >
                    Entrar agora
                </Link>
            </div>
        </section>
    </main>
</template>
