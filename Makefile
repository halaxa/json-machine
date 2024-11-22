.DEFAULT_GOAL := help
.PHONY: *

LATEST_PHP := 8.0 3.1.1
COVERAGE_PHP := 7.4 3.1.1

define PHP_VERSIONS
"7.2 3.1.1"\
"7.3 3.1.1"\
"7.4 3.1.1"\
"8.0 3.1.1"\
"8.1 3.1.1"\
"8.2 3.2.0"\
"8.3 3.3.2"\
"8.4 3.4.0beta1"
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


build: composer-update cs-check phpstan tests-all ## Run all necessary stuff before commit.


tests: ## Run tests on recent PHP version. Pass args to phpunit via ARGS=""
	@$(call DOCKER_RUN,$(COVERAGE_PHP),composer tests -- $(ARGS))


tests-coverage: ## Runs tests and creates ./clover.xml. Pass args to phpunit via ARGS=""
	@$(call DOCKER_RUN,$(COVERAGE_PHP),composer tests-coverage -- $(ARGS))


tests-all: ## Run tests on all supported PHP versions. Pass args to phpunit via ARGS=""
	@for version in $(PHP_VERSIONS); do \
		set -e; \
		printf "PHP %s%.s\n" $$version; \
		printf "=======\n"; \
		$(call DOCKER_RUN,$$version,composer tests -- --colors=always $(ARGS)); \
		printf "\n\n\n"; \
	done


cs-check: ## Check code style
	@$(call DOCKER_RUN,$(LATEST_PHP),composer cs-check)


phpstan: ## Run phpstan
	@$(call DOCKER_RUN,$(LATEST_PHP),composer phpstan)


cs-fix: ## Fix code style
	@$(call DOCKER_RUN,$(LATEST_PHP),composer cs-fix)


performance-tests: ## Run performance tests
	@$(call DOCKER_RUN,$(LATEST_PHP),composer performance-tests)


composer-update: ## Validate composer.json contents
	@$(call DOCKER_RUN,$(LATEST_PHP),composer update)


release: .env build
	@\
	branch=$$(git branch --show-current); \
	\
	echo "Creating release from '$$branch'"; \
	git diff --quiet --exit-code && git diff --quiet --cached --exit-code \
		|| { echo "There are uncommited changes. Stopping"; exit 1; }; \
	\
	echo "Type the release version:"; \
	read version; \
	\
	echo "Is README updated accordingly? [ENTER to continue]"; \
	read pass; \
	\
	echo "Updating CHANGELOG.md"; \
	$(call DOCKER_RUN,$(LATEST_PHP),php build/update-changelog.php $$version CHANGELOG.md); \
	\
	git diff; \
	echo "Commit and tag this? [ENTER to continue]"; \
	read pass; \
	\
	set -x; \
	git commit -am "Release $$version"; \
	git tag -a "$$version" -m "Release $$version"; \
	set +x; \
	\
	echo "Push? [ENTER to continue]"; \
	read pass; \
	set -x; git push --follow-tags; set +x; \
	\
	echo "Publish '$$version' as a Github release? [ENTER to continue]"; \
	read pass; \
	. ./.env; \
	curl \
	  --user "$$GITHUB_USER:$$GITHUB_TOKEN" \
	  --request POST \
	  --header "Accept: application/vnd.github.v3+json" \
	  --data "{\"tag_name\":\"$$version\", \"target_commitish\": \"$$branch\", \"name\": \"$$version\", \"body\": \"See [CHANGELOG](CHANGELOG.md) for changes and release notes.\"}" \
	  https://api.github.com/repos/halaxa/json-machine/releases \
	;\


docker-run: ## Run a command in a latest JSON Machine PHP docker container. Ex.: make docker-run CMD="php -v"
	@$(call DOCKER_RUN,$(LATEST_PHP),$(CMD))
