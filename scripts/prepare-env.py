#!/usr/bin/env python3
"""Create new deployment credentials privately; never overwrite an existing .env."""
import argparse
import os
from pathlib import Path
import secrets
from urllib.parse import urlsplit

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--site-url', required=True)
args = parser.parse_args()
url = urlsplit(args.site_url)
if url.scheme not in ('http', 'https') or not url.netloc or url.username or url.password or url.query or url.fragment:
    parser.error('--site-url must be an HTTP(S) site origin without credentials, query or fragment')
root = Path(__file__).resolve().parent.parent
target = root / '.env'
values = {
    'SITE_URL': args.site_url.rstrip('/'),
    'HTTP_BIND': '127.0.0.1',
    'HTTP_PORT': '8088',
    'MYSQL_DATABASE': 'discuz',
    'MYSQL_USER': 'discuz',
    'MYSQL_PASSWORD': secrets.token_hex(24),
    'MYSQL_ROOT_PASSWORD': secrets.token_hex(24),
    'BOOTSTRAP_ADMIN_USERNAME': 'admin',
    'BOOTSTRAP_ADMIN_EMAIL': 'admin@example.invalid',
    'BOOTSTRAP_ADMIN_PASSWORD': secrets.token_hex(16),
}
fd = os.open(target, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'w') as stream:
    stream.write('\n'.join(f'{key}={value}' for key, value in values.items()) + '\n')
print('Created private .env (0600). Read the administrator password there; it is not printed or committed.')
