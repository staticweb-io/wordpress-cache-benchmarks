repo_root := `pwd`
wordpress_dir := "./dev/data/wordpress1"

alias fmt := format
alias u := update-deps

[private]
list:
    @# First command in the file is invoked by default
    @just --list

# Run benchmarks
bench *args:
    finefile bench -f finefile.toml {{ args }}

bench-posts *args:
    just bench -f finefile-posts.toml {{ args }}

bench-products *args:
    just bench -f finefile-products.toml {{ args }}

# Run development server
[working-directory('dev')]
dev BENCHMARK_NAME:
    just bench --step setup -c {{ BENCHMARK_NAME }}
    nix run . -- attach

# Format source and then check for unfixable issues
format:
    just --fmt --unstable
    fd -e json -x jsonfmt -w
    fd -e nix -x nixfmt
    fd "finefile\.toml" -x finefile format

# Upgrade dependencies
update-deps: _update-flakes

_update-flakes:
    nix flake update
    fd flake.nix -j 4 -x bash -c 'echo "Updating flake inputs in {//}"; cd "{//}" && nix flake update --inputs-from "$0"' "{{ repo_root }}"
