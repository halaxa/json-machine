.DEFAULT_GOAL := help
.PHONY: *

LATEST_PHP := 8.0 3.1.1
COVERAGE_PHP := 7.4 3.1.1

define PHP_VERSIONS
"7.0 2.7.2"\
"7.1 2.9.8"\
"7.2 3.1.1"\
"7.3 3.1.1"\
"7.4 3.1.1"\
"8.0 3.1.1"\
"8.1 3.1.1"\
"8.2-rc 3.2.0alpha3"
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


build: tests-all cs-check ## Run all necessary stuff before commit.


tests: CMD=composer tests -- $(ARGS)
tests: docker-run ## Run tests on recent PHP version. Pass args to phpunit via ARGS=""


tests-coverage: CMD=composer tests-coverage -- $(ARGS)
tests-coverage: ## Runs tests and creates ./clover.xml. Pass args to phpunit via ARGS=""
	@$(call DOCKER_RUN,$(COVERAGE_PHP),$(CMD))


tests-all: ## Run tests on all supported PHP versions. Pass args to phpunit via ARGS=""
	@for version in $(PHP_VERSIONS); do \
		set -e; \
		printf "PHP %s%.s\n" $$version; \
		printf "=======\n"; \
		$(call DOCKER_RUN,$$version,composer tests -- --colors=always $(ARGS)); \
		printf "\n\n\n"; \
	done


cs-check: CMD=composer cs-check
cs-check: docker-run ## Check code style


cs-fix: CMD=composer cs-fix
cs-fix: docker-run ## Fix code style


performance-tests: CMD=composer performance-tests
performance-tests: docker-run ## Run performance tests


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
