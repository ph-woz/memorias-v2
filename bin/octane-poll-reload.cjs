const { spawn } = require('child_process');
const path = require('path');

const chokidar = require('chokidar');

const root = path.resolve(__dirname, '..');

const watchedPaths = [
    'app',
    'bootstrap',
    'config',
    'database',
    'routes',
    'resources/views',
    'resources/js',
    'resources/css',
    'composer.lock',
    '.env',
].map((relativePath) => path.join(root, relativePath));

let reloadTimer = null;
let reloading = false;
let pendingReload = false;

const shouldIgnore = (filePath) => {
    const relative = path.relative(root, filePath).replaceAll('\\', '/');

    if (
        relative.startsWith('config/caddy/')
        || relative.startsWith('config/psysh/')
        || relative.startsWith('config/composer/')
        || relative.startsWith('data/')
        || relative.startsWith('storage/')
        || relative.startsWith('vendor/')
        || relative.startsWith('node_modules/')
        || relative.startsWith('public/build/')
        || relative.startsWith('bootstrap/cache/')
    ) {
        return true;
    }

    if (relative.startsWith('config/')) {
        return ! relative.endsWith('.php');
    }

    return false;
};

const runReload = () => {
    if (reloading) {
        pendingReload = true;

        return;
    }

    reloading = true;
    pendingReload = false;

    console.log('[octane-watch] Mudança detectada. Recarregando workers…');

    const child = spawn(
        'php',
        ['artisan', 'octane:reload', '--server=frankenphp'],
        {
            cwd: root,
            stdio: 'inherit',
            env: process.env,
        },
    );

    child.on('exit', (code) => {
        // Evita loop com artefatos gerados durante o reload.
        setTimeout(() => {
            reloading = false;

            if (code !== 0) {
                console.error(`[octane-watch] Falha ao recarregar (código ${code}).`);
            }

            if (pendingReload) {
                pendingReload = false;
                runReload();
            }
        }, 1000);
    });
};

const scheduleReload = (event, filePath) => {
    if (shouldIgnore(filePath)) {
        return;
    }

    clearTimeout(reloadTimer);
    reloadTimer = setTimeout(runReload, 500);
};

chokidar
    .watch(watchedPaths, {
        ignoreInitial: true,
        usePolling: true,
        interval: 1000,
        binaryInterval: 1500,
        awaitWriteFinish: {
            stabilityThreshold: 300,
            pollInterval: 100,
        },
        ignored: shouldIgnore,
    })
    .on('all', scheduleReload)
    .on('error', (error) => {
        console.error('[octane-watch] Erro no watcher:', error);
    });

console.log('[octane-watch] Observando alterações com polling…');
