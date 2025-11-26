# WordPress Cache Benchmarks

Benchmarks for object cache plugins.
For plugins that have other options like page caches and acceleration,
those are disabled in the benchmarks.
We benchmark only the plugin with its object cache.

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

## Running Dev Environment

You can start a development server with the same setup as
a benchmark by running `just dev <name>`.
WordPress will be available at http://locahost:8888/wp-admin/

## Editing Benchmarks

Benchmarks are defined in [finefile.toml](./finefile.toml).

## Benchmarks

The default configuration performs 500 requests to a random selection
of pages on a default install of WordPress.
This is chosen as a neutral baseline and a reality check,
but note that it is not representative of real usage.
It gives an abnormal advantage to the `no-plugins` benchmark,
and heavily punishes the overhead of the plugins.

The available benchmarks are:

[WordPress](https://wordpress.org)'s built-in object cache:
- `no-plugins`

[LiteSpeed Cache](https://wordpress.org/plugins/litespeed-cache/), an all-in-one acceleration and caching plugin. The page cache is disabled so that we test only the object cache:
- `litespeed-memcached` configured with memcached backend
- `litespeed-redis` configured with redis backend

[Redis Cache](https://wordpress.org/plugins/redis-cache/):
- `redis-cache`

[SnapCache](https://github.com/staticweb-io/snapcache/):
- `snapcache-memcached` configured with memcached backend

[SQLite Object Cache](https://wordpress.org/plugins/sqlite-object-cache/):
- `sqlite-object-cache` default configuration without APCu cache
- `sqlite-object-cache-apcu` with APCu cache

[WP Redis](https://wordpress.org/plugins/wp-redis/):
- `wp-redis`
