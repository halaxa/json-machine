# AGENTS.md

## Main points
- Read full `README.md` to understand public API, and real world usage of this library.
- Always check `make performance-tests` when updating hot paths. One of the main goals is to be as performant as possible.
- Edit `README.md` accordingly when making changes facing public API.
- Edit `CHANGELOG.md` accordingly when making notable updates.
- Run `make tests` regularly during a feature development
- When finished, always run `make build`.
- If `php ... ` command is not available, run `make docker-run CMD="php ..."` To run arbitrary PHP command
- Run `make` to show help with other useful build targets.
- Periodically check for new patch versions of supported PHP versions and for its compatible version of XDebug and edit Makefile header accordingly. Xdebug is not required if not available.
