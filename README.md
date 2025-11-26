# WordPress Cache Benchmarks

## Running Benchmarks

Running the benchmarks requires installing
[Nix](https://docs.determinate.systems/determinate-nix/#getting-started).
You can enter a development shell via `nix develop`,
or automatically using [direnv](https://direnv.net/).

You should run the benchmarks in a disposable VM with no access
to secrets.

After starting a development shell,
you can run the benchmarks via `just bench`.
This runs a series of 500 random requests using each
plugin and each backend that that plugin supports.

You can run specific benchmarks via `just bench -c <name>`.
See `finefile --help` for a full list of options.
