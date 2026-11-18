#!/usr/bin/env bash
# Regenerates architecture.png and architecture.svg from architecture.dot.
# Requires Graphviz (e.g. `sudo apt-get install graphviz`).
set -euo pipefail
cd "$(dirname "$0")"
dot -Tpng -Gdpi=110 architecture.dot -o architecture.png
dot -Tsvg architecture.dot -o architecture.svg
