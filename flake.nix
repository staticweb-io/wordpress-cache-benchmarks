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
      system:
      with import inputs.nixpkgs { inherit system; };
      let
        # Fetch zips but take a stable hash of its contents, not the zip itself.
        # Sometimes zip files change.
        fetchStableZip =
          { url, hash }:
          let
            unpacked = pkgs.fetchzip {
              inherit url hash;
              recursiveHash = true;
              stripRoot = false;
            };
          in
          pkgs.runCommand ((lib.nameFromURL url ".zip") + ".zip") { buildInputs = [ pkgs.zip ]; } ''
            cd ${unpacked}
            zip -r $out .
          '';
      in
      {
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
          litespeed-cache = fetchStableZip {
            url = "https://downloads.wordpress.org/plugin/litespeed-cache.7.6.2.zip";
            hash = "sha256-AduSCRqawoBvb0kVBDiM90mZ9naLhUcbPt3kcHoqlKU=";
          };
          redis-cache = fetchStableZip {
            url = "https://downloads.wordpress.org/plugin/redis-cache.2.7.0.zip";
            hash = "sha256-+BlAD31TBABJOeFvF7j7uelFO8nYfIkoKyhtIk38KpE=";
          };
          snapcache = inputs.snapcache.packages.${system}.pluginWpOrg;
          wp-redis = fetchStableZip {
            url = "https://downloads.wordpress.org/plugin/wp-redis.1.4.6.zip";
            hash = "sha256-bB2xEF+w7o6mb1OzBPJPfnR1EFNNBfT/MkV6l0MrtA8=";
          };
        };
      }
    );
}
