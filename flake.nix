{
  description = "WordPress Cache Benchmarks";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-25.05";
    finefile = {
      url = "github:john-shaffer/finefile";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    flake-utils.url = "github:numtide/flake-utils";
    snapcache = {
      url = "github:staticweb-io/snapcache";
      inputs.flake-utils.follows = "flake-utils";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs =
    inputs:
    inputs.flake-utils.lib.eachDefaultSystem (
      system: with import inputs.nixpkgs { inherit system; }; {
        devShells.default = pkgs.mkShell {
          buildInputs = with pkgs; [
            fd
            hey
            inputs.finefile.packages.${system}.default
            jsonfmt
            just
            nixfmt-rfc-style
            omnix
            parallel
            siege
            wp-cli
          ];
          shellHook = ''
            echo
            echo -e "Run '\033[1mjust <recipe>\033[0m' to get started"
            just --list
          '';
        };
        packages = {
          litespeed-cache = fetchurl {
            url = "https://downloads.wordpress.org/plugin/litespeed-cache.7.6.2.zip";
            hash = "sha256-BVkpCYn+4+8z5QLnmeFsVS5zHWo/aG4eWmTC5mdxgnk=";
          };
          redis-cache = fetchurl {
            url = "https://downloads.wordpress.org/plugin/redis-cache.2.7.0.zip";
            hash = "sha256-DLxB3qNRaIaTuOfbEWW12StsjvcjGsD/Wr7HuD1ixjU=";
          };
          snapcache = inputs.snapcache.packages.${system}.pluginWpOrg;
          wp-redis = fetchurl {
            url = "https://downloads.wordpress.org/plugin/wp-redis.1.4.6.zip";
            hash = "sha256-Cuw8ofc215EUMnVRVBxfwWxIg22cx8zKuJDgiKkZvLc=";
          };
        };
      }
    );
}
