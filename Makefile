SHELL := /bin/bash

export UID := $(shell id -u)
export GID := $(shell id -g)

PHP := docker compose run --rm php

.PHONY: docker.build install update shell \
	code.fix code.check code.cs.fix code.cs.check code.rector.fix code.rector.check code.stan code.yaml.check code.lint \
	test test.coverage test.mutate ready

# ─────────────────────────────── Docker ───────────────────────────────
docker.build:
	docker compose build

shell:
	$(PHP) sh

# ────────────────────────────── Composer ──────────────────────────────
install:
	$(PHP) composer install

update:
	$(PHP) composer update

# ─────────────────────────────── Quality ──────────────────────────────
code.fix:
	$(PHP) composer code.fix

code.check:
	$(PHP) composer code.check

code.cs.fix:
	$(PHP) composer code.cs.fix

code.cs.check:
	$(PHP) composer code.cs.check

code.rector.fix:
	$(PHP) composer code.rector.fix

code.rector.check:
	$(PHP) composer code.rector.check

code.stan:
	$(PHP) composer code.stan

code.yaml.check:
	$(PHP) composer code.yaml.check

code.lint:
	$(PHP) composer code.lint

# ──────────────────────────────── Tests ───────────────────────────────
test:
	$(PHP) composer test

test.coverage:
	$(PHP) composer test.coverage

test.mutate:
	$(PHP) composer test.mutate

# ───────────────────────────── Everything ─────────────────────────────
ready:
	$(PHP) composer ready
