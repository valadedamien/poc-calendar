# Setup ———————————————————————————————————————————————————————————————————————
GIT				= git
DOCKER			= docker-compose

EXEC_PHP		= $(DOCKER) exec php

SYMFONY_LOCALE	= php bin/console
SYMFONY			= $(EXEC_PHP) bin/console
COMPOSER		= composer
YARN			= $(DOCKER) exec php yarn

SENTRY			= sentry-cli
SENTRY_ORG		= odandb
SENTRY_PROJECT	= learning-boost

DISABLE_XDEBUG=XDEBUG_MODE=off

OS := $(shell uname)

.DEFAULT_GOAL = help

## —— Makefile 🍺 ——————————————————————————————————————————————————————————————
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'

install: docker-build start dev db-reset jwt-generate-keys stop ## Installe le projet

.PHONY: help install

## —— Copy 📋 ——————————————————————————————————————————————————————————————————
jwt-generate-keys: ## Initialisation de JWT
	$(SYMFONY) lexik:jwt:generate-keypair --overwrite --no-interaction

.PHONY: jwt-generate-keys

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
docker-build: docker-compose.yml ## Build les images du projet
	$(DOCKER) pull --parallel --quiet --ignore-pull-failures 2> /dev/null
	$(DOCKER) -f docker-compose.yml build --pull

start: ## Démarre le projet
	$(DOCKER) -f docker-compose.yml up -d --remove-orphans --no-recreate

stop: ## Stop le projet
	$(DOCKER) down --volumes --remove-orphans

bash: ## Entrer dans le container php
	$(DOCKER) exec php sh

.PHONY: start stop bash

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
cc: ## Vide le cache
	$(SYMFONY) c:c --no-warmup || rm -rf var/cache/*

warmup: cc ## Warmup the cache
	$(SYMFONY) cache:warmup

.PHONY: cc warmup

## —— MySQL 💾 —————————————————————————————————————————————————————————————————
db-cache: vendor ## Vide le cache de doctrine
	$(SYMFONY) doctrine:cache:clear-metadata
	$(SYMFONY) doctrine:cache:clear-query
	$(SYMFONY) doctrine:cache:clear-result

db-reset: vendor ## Reset de la base de donnée
	$(SYMFONY) doctrine:database:drop --if-exists --force
	$(SYMFONY) doctrine:database:create --if-not-exists
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(SYMFONY) doctrine:fixtures:load --no-interaction --group=BaseFixtures --group=AppFixtures

db-update: vendor ## Met à jour le schema de la base de donnée
	$(SYMFONY) doctrine:schema:update --force

.PHONY: db-cache db-reset db-update

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer.lock: composer.json ## Met à jour les vendors selon le fichier composer.json
	$(COMPOSER) update

vendor: composer.lock ## Installe les vendors en fonction du fichier composer.lock actuel
	$(COMPOSER) install --no-progress --prefer-dist --optimize-autoloader

## —— Yarn 🐱 / JavaScript —————————————————————————————————————————————————————
yarn.lock: package.json ## Met à jour les node_modules selon le fichier package.json
	$(YARN) upgrade

yarn_install: yarn.lock ## Installe les node_modules en fonction du fichier yarn.lock
	$(YARN) install

dev: yarn_install ## Build les assets en mode dev
	$(YARN) run dev

prod: yarn_install ## Build les assets en mode prod (compresser)
	$(YARN) run build

watch: yarn_install ## Watch les fichiers et build les assets quand c'est nécessaire en mode dev
	$(YARN) run watch

.PHONY: dev prod watch

## —— Coding standards ✨  —————————————————————————————————————————————————————
lint: lcontainer ldeprecations lyaml cs-fix phpstan ## Lance tout les linters

lcontainer: ## Garantit que les arguments injectés dans les services correspondent aux déclarations de type
	$(SYMFONY) lint:container

ldeprecations: ## Vérifie les déprécations
	$(SYMFONY) debug:container --deprecations

lyaml: ## Lint les fichiers YAML
	$(SYMFONY) lint:yaml config --parse-tags

cs-fix: ## Lint les fichiers PHP
	php-cs-fixer fix --config=.php-cs-fixer.php --verbose

phpstan: ## Execute PHPStan
	vendor/bin/phpstan analyse --memory-limit 1G -c phpstan.neon

rector: ## Execute Rector
	vendor/bin/rector process src --clear-cache

.PHONY: lint lcontainer ldeprecations lyaml cs-fix phpstan

## —— Tests ✅  ————————————————————————————————————————————————————————————————
reset-test: ## Réinitialise tout les éléments concernant les tests
	$(SYMFONY_LOCALE) c:c --no-warmup --env test || rm -rf var/cache/test/*
	$(SYMFONY_LOCALE) doctrine:database:drop --if-exists --force --env test
	$(SYMFONY_LOCALE) doctrine:database:create --if-not-exists  --env test
	$(SYMFONY_LOCALE) doctrine:migrations:migrate --no-interaction --allow-no-migration --env test

test: ## Lance tout les tests
	$(DISABLE_XDEBUG) php -d memory_limit=1G bin/phpunit --stop-on-failure

testu: ## Lance les tests unitaire
	$(DISABLE_XDEBUG) php bin/phpunit tests/Unit --stop-on-failure --bootstrap vendor/autoload.php

testf: ## Lance les tests fonctionnel
	$(DISABLE_XDEBUG) php bin/phpunit tests/Functional --stop-on-failure

.PHONY: reset-test test testu testf

## —— Check PR 🔃  —————————————————————————————————————————————————————————————
check-pr: cs-fix phpstan test ## À exécuter avant de créer une PR

.PHONY: check-pr

## —— Release 📜 ———————————————————————————————————————————————————————————————
release: ## Créer une release du projet
	$(GIT) tag -a $(APP_VERSION)
	$(GIT) push --tags
	$(SENTRY) releases -o $(SENTRY_ORG) new -p $(SENTRY_PROJECT) "$(APP_VERSION)"
	$(SENTRY) releases -o $(SENTRY_ORG) -p $(SENTRY_PROJECT) set-commits "$(APP_VERSION)" --auto --ignore-missing
	$(SENTRY) releases -o $(SENTRY_ORG) -p $(SENTRY_PROJECT) finalize "$(APP_VERSION)"

.PHONY: release

