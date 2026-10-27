#!/usr/bin/env node
'use strict';
// Verify the shipped SQL and, before a push, only the exact Git index contents.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const cp = require('node:child_process');
const root = path.resolve(__dirname, '..');
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'database/manifest.json'), 'utf8'));
const fail = [];
for (const [file, expected] of Object.entries(manifest.sha256)) {
  const content = fs.readFileSync(path.join(root, 'database', file));
  if (crypto.createHash('sha256').update(content).digest('hex') !== expected) fail.push(`database/${file}: checksum mismatch`);
}
const data = fs.readFileSync(path.join(root, 'database/data.sql'), 'utf8');
const forbiddenRows = /INSERT INTO `[^`]*(?:common_member|common_session|common_admincp_session|common_log|ucenter_members|ucenter_admins|flipbook_order|flipbook_identity|flipbook_sync_key|flipbook_sync_source|flipbook_history|flipbook_favorite)`/g;
if (forbiddenRows.test(data)) fail.push('database/data.sql: private account/activity/credential/transaction rows');
const git = (...args) => cp.execFileSync('git', args, {cwd: root, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024});
if (!process.argv.includes('--files')) {
  // Installer placeholders are ordinary source vocabulary, not an issued credential.
  const placeholders = new Set(['password', 'username', 'example', 'changeme']);
  const publicInstaller = fs.readFileSync(path.join(root, 'app/install/sql/sql_uc.php'), 'utf8');
  const knownSecrets = process.argv.includes('--secrets-stdin') ? JSON.parse(fs.readFileSync(0, 'utf8')).filter(v => typeof v === 'string' && v.length >= 8 && !placeholders.has(v.toLowerCase()) && !/@example\.(?:com|net|org|invalid)$/i.test(v) && !publicInstaller.includes(`'${v}'`)) : [];
  const files = git('diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z').split('\0').filter(Boolean);
  if (files.some(file => /[\r\n]/.test(file))) throw new Error('Unsupported newline in staged path');
  const blobs = files.length ? cp.execFileSync('git', ['cat-file', '--batch'], {cwd: root, input: files.map(file => `:${file}\n`).join(''), maxBuffer: 256 * 1024 * 1024}) : Buffer.alloc(0);
  let offset = 0;
  const forbiddenPath = /(?:^|\/)(?:\.env(?:\.|$)|node_modules\/|\.preview\/)|^app\/config\/config_(?:global|ucenter)\.php$|\.(?:pem|key|p12|pfx)$|project\.private\.config\.json$/i;
  const patterns = [
    ['private key body', /-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----\s+[A-Za-z0-9+/=]{32,}/],
    ['GitHub token', /\b(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{40,})\b/],
    ['cloud access key', /\b(?:AKIA[0-9A-Z]{16}|AKID[A-Za-z0-9]{28,36})\b/],
  ];
  for (const file of files) {
    const end = blobs.indexOf(10, offset);
    const header = blobs.subarray(offset, end).toString('utf8').split(' ');
    if (end < 0 || header[1] !== 'blob') throw new Error(`Cannot read staged file: ${file}`);
    const size = Number(header[2]);
    const bytes = blobs.subarray(end + 1, end + 1 + size);
    offset = end + 1 + size + 1;
    if (forbiddenPath.test(file) && file !== '.env.example') { fail.push(`${file}: private/runtime path`); continue; }
    if (bytes.length > 95 * 1024 * 1024) fail.push(`${file}: exceeds normal GitHub file-size limit`);
    if (bytes.includes(0)) continue;
    const text = bytes.toString('utf8');
    for (const [label, pattern] of patterns) if (pattern.test(text)) fail.push(`${file}: ${label}`);
    if (knownSecrets.some(secret => text.includes(secret))) fail.push(`${file}: matches a local private credential (value withheld)`);
  }
  console.log(`Inspected ${files.length} staged files; private values are never printed.`);
}
if (fail.length) { console.error(fail.slice(0, 20).join('\n')); if (fail.length > 20) console.error(`Additional findings: ${fail.length - 20}`); process.exit(1); }
console.log(`Release valid: ${manifest.schemaTables.length} table structures; ${Object.values(manifest.dataRows).reduce((a,b) => a+b,0)} selected data rows; SQL checksums match.`);
