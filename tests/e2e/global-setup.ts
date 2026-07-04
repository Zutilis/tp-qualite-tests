import { execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export default function globalSetup() {
    const currentDir = path.dirname(fileURLToPath(import.meta.url));
    const projectRoot = path.resolve(currentDir, '..', '..');

    execSync('php artisan migrate:fresh --force', {
        cwd: projectRoot,
        stdio: 'inherit',
    });
}
