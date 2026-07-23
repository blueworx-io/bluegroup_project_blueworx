#!/usr/bin/env node
// Builds the deployment artifact: dist/bluegroup-project-blueworx.zip containing a single
// top-level `bluegroup-project-blueworx/` folder, with forward-slash entry paths so it
// installs correctly on Linux WordPress hosts.
//
// Dependency-free: stages the runtime files into dist/bluegroup-project-blueworx/ and zips
// with bsdtar (System32 tar.exe on Windows, `tar` elsewhere) rather than
// PowerShell Compress-Archive, which writes backslash entries that mis-extract
// on the server ("Plugin file does not exist.").
import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { execFileSync } from 'node:child_process';

const SLUG = 'bluegroup-project-blueworx';
const DIST = 'dist';
const STAGE = join(DIST, SLUG);

// Runtime files/dirs that ship inside the plugin folder.
const REQUIRED = ['bluegroup-project-blueworx.php', 'uninstall.php', 'readme.txt', 'includes', 'assets', 'templates'];
const OPTIONAL = ['languages'];

// bsdtar: on Windows the System32 tar.exe is bsdtar; GNU tar in Git Bash cannot
// write zip, so call it explicitly rather than whatever `tar` resolves to first.
const TAR = 'win32' === process.platform ? join(process.env.WINDIR || 'C:\\Windows', 'System32', 'tar.exe') : 'tar';

rmSync(DIST, { recursive: true, force: true });
mkdirSync(STAGE, { recursive: true });

for (const entry of REQUIRED) {
  if (!existsSync(entry)) {
    throw new Error(`Required plugin entry missing from build: ${entry}`);
  }
  cpSync(entry, join(STAGE, entry), { recursive: true });
}
for (const entry of OPTIONAL) {
  if (existsSync(entry)) {
    cpSync(entry, join(STAGE, entry), { recursive: true });
  }
}

const zipPath = join(DIST, `${SLUG}.zip`);
execFileSync(TAR, ['-a', '-c', '-f', zipPath, '-C', DIST, SLUG], { stdio: 'inherit' });
console.log(`Built ${zipPath}`);

// Verify every entry is nested one level under `${SLUG}/` with forward slashes.
const listing = execFileSync(TAR, ['-tf', zipPath], { encoding: 'utf8' });
const bad = listing.split(/\r?\n/).filter((l) => l && (l.includes('\\') || !l.startsWith(`${SLUG}/`)));
if (bad.length) {
  throw new Error(`Malformed zip entries (expected "${SLUG}/..." with forward slashes):\n${bad.join('\n')}`);
}
console.log(`Verified ${listing.split(/\r?\n/).filter(Boolean).length} entries, all under ${SLUG}/`);
