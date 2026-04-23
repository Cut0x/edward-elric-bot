const { spawn } = require('child_process');
const path = require('path');

const bots = [
    ['edward', path.join(__dirname, 'edward', 'index.js')],
    ['alphonse', path.join(__dirname, 'alphonse', 'index.js')],
];

for (const [name, file] of bots) {
    const child = spawn(process.execPath, [file], {
        stdio: 'inherit',
        env: process.env,
    });

    child.on('exit', code => {
        console.error(`[${name}] process exited with code ${code}`);
    });
}
