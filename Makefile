.DEFAULT_GOAL := help
.PHONY: *
LATEST_PHP := 8.1 3.1.1

define PHP_VERSIONS
"5.6 2.5.5"\
"7.0 2.7.2"\
"7.1 2.9.8"\
"7.2 3.1.1"\
"7.3 3.1.1"\
"7.4 3.1.1"\
"8.0 3.1.1"\
"8.1 3.1.1"
endef

define DOCKER_RUN
	./build/docker-run.sh \
		$$(./build/build-image.sh $(1)) \
		$$(pwd) \
		"$(2)"
endef


help:
	@printf "\033[33mJSON Machine's make usage:\033[0m\n  make [target] [args=\"val\"...]\n\n"
	@printf "\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m\t%s\n", $$1, $$2}'


build: tests-all cs ## Run all necessary stuff before commit.


tests: CMD=composer tests -- $(ARGS)
tests: docker-run ## Run tests on the latest supported PHP version. Pass args to phpunit via ARGS=""


tests-all: ## Run tests on all supported PHP versions. Pass args to phpunit via ARGS=""
	@for version in $(PHP_VERSIONS); do \
		printf "$$SEP"; \
		$(call DOCKER_RUN,$$version,composer tests -- $(ARGS)); \
		SEP="\n\n"; \
	done


cs: CMD=composer cs
cs: docker-run ## Check code style


cs-fix: CMD=composer cs-fix
cs-fix: docker-run ## Fix code style


performance-tests: CMD=composer performance-tests
performance-tests: docker-run ## Run performance tests


docker-run: ## Run a command in a latest JSON Machine PHP docker container. Ex.: make docker-run CMD="php -v"
	@$(call DOCKER_RUN,$(LATEST_PHP),$(CMD))


docker-pull: ## Pull the most recent vanilla images for all supported PHP versions
	@for version in $(PHP_VERSIONS); do \
		printf "$$SEP"; \
		build/build-image.sh $$(printf $$version | awk '{print $$1;}') --pull; \
		SEP="\n"; \
	done
