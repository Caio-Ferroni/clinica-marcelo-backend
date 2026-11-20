const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const source = path.join(root, 'node_modules', 'toastify-js');
const metadata = JSON.parse(fs.readFileSync(path.join(source, 'package.json'), 'utf8'));
if (metadata.version !== '1.12.0') throw new Error('Versão inesperada do Toastify.js.');
const script = fs.readFileSync(path.join(source, 'src', 'toastify.js'), 'utf8')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/(^|\s)\/\/[^\r\n]*/g, '$1');
new vm.Script(script);
const stylesheet = fs.readFileSync(path.join(source, 'src', 'toastify.css'), 'utf8')
    .replace(/\/\*[\s\S]*?\*\//g, '');
for (const directory of ['js/vendor', 'css/vendor']) fs.mkdirSync(path.join(root, directory), { recursive: true });
fs.writeFileSync(path.join(root, 'js/vendor/toastify.js'), script);
fs.writeFileSync(path.join(root, 'css/vendor/toastify.css'), stylesheet);
fs.copyFileSync(path.join(source, 'LICENSE'), path.join(root, 'js/vendor/toastify.LICENSE.txt'));
