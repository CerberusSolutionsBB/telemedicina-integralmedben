<?php
namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;

class RouteModuleServiceProvider extends ServiceProvider
{
    /**
     * Módulos de rotas configuráveis
     */
    protected array $modules = [
        'web'          => [
            'middleware' => 'web',
            'prefix'     => '',
            'name'       => '',
            'file'       => 'web.php',
        ],
        'form'         => [
            'middleware' => ['web'],
            'prefix'     => 'forms',
            'name'       => 'forms.',
            'file'       => 'form.php',
        ],
        'lei'          => [
            'middleware' => ['web'],
            'prefix'     => 'leis',
            'name'       => 'leis.',
            'file'       => 'lei.php',
        ],
        'configuracao' => [
            'middleware' => ['web'],
            'prefix'     => 'configuracoes',
            'name'       => 'configuracoes.',
            'file'       => 'configuracao.php',
        ],
        'admin'        => [
            'middleware' => ['web', 'auth', 'can:admin'],
            'prefix'     => 'admin',
            'name'       => 'admin.',
            'file'       => 'admin.php',
        ],
        // 'api-custom' => [
        //     'middleware' => 'api',
        //     'prefix'     => 'v2',
        //     'name'       => 'api.v2.',
        //     'file'       => 'api-custom.php',
        // ],
    ];

    /**
     * Registrar serviços
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializar serviços
     */
    public function boot(): void
    {
        $this->registerRouteModules();

        // Ou use auto-discovery:
        // $this->registerAutoDiscoveredRoutes();
    }

    /**
     * Registrar módulos de rotas configurados
     */
    protected function registerRouteModules(): void
    {
        foreach ($this->modules as $name => $config) {
            $filePath = base_path("routes/{$config['file']}");

            if (! file_exists($filePath)) {
                $this->app['log']->warning("Arquivo de rotas não encontrado: {$config['file']}");
                continue;
            }

            $route = Route::middleware($config['middleware']);

            if (! empty($config['prefix'])) {
                $route->prefix($config['prefix']);
            }

            if (! empty($config['name'])) {
                $route->name($config['name']);
            }

            $route->group($filePath);
        }
    }

    /**
     * Auto-discover: carrega todos os .php de routes/ exceto api/console
     */
    protected function registerAutoDiscoveredRoutes(): void
    {
        $routesPath = base_path('routes');

        if (! is_dir($routesPath)) {
            return;
        }

        $excluded = ['api.php', 'console.php', 'channels.php'];
        $files    = Finder::create()
            ->files()
            ->in($routesPath)
            ->name('*.php')
            ->sortByName();

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (in_array($filename, $excluded)) {
                continue;
            }

            $middleware = str_starts_with($filename, 'api') ? 'api' : 'web';
            $name       = str_replace('.php', '', $filename);
            $prefix     = $name === 'web' ? '' : $name;

            $route = Route::middleware($middleware);

            if ($prefix && $prefix !== 'web') {
                $route->prefix($prefix);
            }

            if ($prefix && $prefix !== 'web') {
                $route->name("{$prefix}.");
            }

            $route->group($file->getRealPath());
        }
    }

    /**
     * Adicionar módulo dinamicamente (útil para packages)
     */
    public function addModule(string $name, array $config): void
    {
        $this->modules[$name] = $config;
    }
}