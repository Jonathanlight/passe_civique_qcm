# passe_civique_qcm

### Requirements
---

- PHP 8.3
- Symfony 7.4
- Apache 2.4
- MySQL 5.7
- Composer 2

### Usage
---

### Installation
---

```
git clone git@github.com:Jonathanlight/passe_civique_qcm.git
$ cd passe_civique_qcm

# or start docker containers
$ make docker-run

# install dependencies
$ make docker-exec apache bash
$ composer install

# create migrations
$ make migrate

# load fixtures
$ make fixtures

server running on http://localhost:8000
```

### Installation SSl
---
```
cd docker/etc/apache/ssl/

openssl req -x509 -out server.crt -keyout server.key \
-newkey rsa:2048 -nodes -sha256 \
-subj '/CN=localhost' -extensions EXT -config <( \
printf "[dn]\nCN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth")
```

### Configuration
---

### Pipeline
---

```yaml
make pre-push # load all test quality
make fixtures # load fixtures
make quality # run quality checks
make translations-lint # check translations
make phpunit # run unit tests
make format-twig # reindent template twig
```

### Authors
---

- Jonathan 