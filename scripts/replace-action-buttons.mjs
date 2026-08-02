import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';

const ROOT = join(process.cwd(), 'resources', 'js');

function walk(dir, files = []) {
  for (const name of readdirSync(dir)) {
    const path = join(dir, name);
    if (statSync(path).isDirectory()) walk(path, files);
    else if (name.endsWith('.vue')) files.push(path);
  }
  return files;
}

function replaceActionButtons(content) {
  let next = content;

  const patterns = [
    [/<button\s+((?:v-if="[^"]+"\s+)*)@click="([^"]+)"\s+class="text-indigo-600[^"]*"[^>]*>\s*Editar\s*<\/button>/g, '<IconActionButton $1variant="edit" @click="$2" />'],
    [/<button\s+@click="([^"]+)"\s+((?:v-if="[^"]+"\s+)*)class="text-indigo-600[^"]*"[^>]*>\s*Editar\s*<\/button>/g, '<IconActionButton $2variant="edit" @click="$1" />'],
    [/<button\s+((?:v-if="[^"]+"\s+)*)@click="([^"]+)"\s+class="text-rose-600[^"]*"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton $1variant="inactivate" @click="$2" />'],
    [/<button\s+@click="([^"]+)"\s+(v-if="[^"]+"\s+)class="text-rose-600[^"]*"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton $2variant="inactivate" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-rose-600[^"]*"[^>]*>\s*Eliminar\s*<\/button>/g, '<IconActionButton variant="delete" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-rose-600[^"]*"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton variant="inactivate" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-indigo-600[^"]*"[^>]*>\s*Editar\s*<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-indigo-600 dark:text-indigo-400[^"]*"[^>]*>\s*Editar\s*<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button\s+v-if="([^"]+)"\s+@click="([^"]+)"\s+class="text-rose-600 dark:text-rose-400[^"]*"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton v-if="$1" variant="inactivate" @click="$2" />'],
    [/<button\s+@click="([^"]+)"\s+v-if="([^"]+)"\s+class="text-rose-600[^"]*"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton v-if="$2" variant="inactivate" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-indigo-600 font-semibold text-xs">Editar<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button\s+v-if="([^"]+)"\s+@click="([^"]+)"\s+class="text-rose-600 font-semibold text-xs">Inactivar<\/button>/g, '<IconActionButton v-if="$1" variant="inactivate" @click="$2" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-rose-600 font-semibold text-xs">Inactivar<\/button>/g, '<IconActionButton variant="inactivate" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-indigo-600 font-semibold text-xs hover:underline">Editar<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button\s+@click="([^"]+)"\s+class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-xs transition-colors"\s*>\s*Editar\s*<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button\s+v-if="([^"]+)"\s+@click="([^"]+)"\s+class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 font-semibold text-xs transition-colors"\s*>\s*Inactivar\s*<\/button>/g, '<IconActionButton v-if="$1" variant="inactivate" @click="$2" />'],
    [/<button[^>]*@click="([^"]+)"[^>]*>\s*Editar\s*<\/button>/g, '<IconActionButton variant="edit" @click="$1" />'],
    [/<button[^>]*@click="([^"]+)"[^>]*>\s*Inactivar\s*<\/button>/g, '<IconActionButton variant="inactivate" @click="$1" />'],
    [/<button[^>]*@click="([^"]+)"[^>]*>\s*Eliminar\s*<\/button>/g, '<IconActionButton variant="delete" @click="$1" />'],
  ];

  for (const [pattern, replacement] of patterns) {
    next = next.replace(pattern, replacement);
  }

  return next;
}

let changed = 0;
for (const file of walk(ROOT)) {
  const original = readFileSync(file, 'utf8');
  const updated = replaceActionButtons(original);
  if (updated !== original) {
    writeFileSync(file, updated, 'utf8');
    changed += 1;
    console.log(file.replace(process.cwd() + '\\', ''));
  }
}

console.log(`Updated ${changed} files.`);
